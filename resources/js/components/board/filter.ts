import type { InjectionKey, Ref } from 'vue';
import { computed, inject, ref } from 'vue';
import { colorNames } from '@/components/card/board';
import type { BoardCard, Label } from '@/types';

export type DueFilter = 'overdue' | 'day' | 'week' | 'none' | 'complete';

/** Members and labels hold ids; 0 stands for "no members" / "no labels". */
export type CardFilter = {
	text: string;
	members: number[];
	labels: number[];
	due: DueFilter[];
};

export const dueFilters: { value: DueFilter; label: string }[] = [
	{ value: 'overdue', label: 'Overdue' },
	{ value: 'day', label: 'Due in the next day' },
	{ value: 'week', label: 'Due in the next week' },
	{ value: 'none', label: 'No due date' },
	{ value: 'complete', label: 'Marked complete' },
];

const day = 24 * 60 * 60 * 1000;

function matchesDue(card: BoardCard, due: DueFilter, now: number): boolean {
	if (due === 'none') {
		return card.due_at === null;
	}

	if (due === 'complete') {
		return card.due_at !== null && card.completed_at !== null;
	}

	if (card.due_at === null || card.completed_at !== null) {
		return false;
	}

	const left = new Date(card.due_at).getTime() - now;

	if (due === 'overdue') {
		return left < 0;
	}

	return left >= 0 && left < (due === 'day' ? day : 7 * day);
}

/** Within a group any choice matches; across groups every group has to. */
function matches(
	card: BoardCard,
	filter: CardFilter,
	labels: Label[],
	now: number,
): boolean {
	const words = filter.text.toLowerCase().split(/\s+/).filter(Boolean);

	if (words.length > 0) {
		const haystack = [
			card.title,
			...labels
				.filter((label) => card.labels.includes(label.id))
				.map((label) => label.name ?? colorNames[label.color]),
		]
			.join(' ')
			.toLowerCase();

		if (!words.every((word) => haystack.includes(word))) {
			return false;
		}
	}

	if (
		filter.members.length > 0 &&
		!filter.members.some((id) =>
			id === 0 ? card.members.length === 0 : card.members.includes(id),
		)
	) {
		return false;
	}

	if (
		filter.labels.length > 0 &&
		!filter.labels.some((id) =>
			id === 0 ? card.labels.length === 0 : card.labels.includes(id),
		)
	) {
		return false;
	}

	if (
		filter.due.length > 0 &&
		!filter.due.some((due) => matchesDue(card, due, now))
	) {
		return false;
	}

	return true;
}

export type BoardFilter = ReturnType<typeof createFilter>;

export function createFilter(labels: Ref<Label[]>) {
	const filter = ref<CardFilter>({
		text: '',
		members: [],
		labels: [],
		due: [],
	});

	/** How many choices are made in the popover (the text field counts apart). */
	const count = computed(
		() =>
			filter.value.members.length +
			filter.value.labels.length +
			filter.value.due.length,
	);

	const active = computed(
		() => count.value > 0 || filter.value.text.trim() !== '',
	);

	function shows(card: BoardCard): boolean {
		return (
			!active.value ||
			matches(card, filter.value, labels.value, Date.now())
		);
	}

	function toggle<T>(list: T[], value: T) {
		const at = list.indexOf(value);

		if (at === -1) {
			list.push(value);
		} else {
			list.splice(at, 1);
		}
	}

	function clear() {
		filter.value = { text: '', members: [], labels: [], due: [] };
	}

	return { filter, count, active, shows, toggle, clear };
}

export const filterKey: InjectionKey<BoardFilter> = Symbol('filter');

export function useFilter(): BoardFilter {
	const filter = inject(filterKey);

	if (!filter) {
		throw new Error('useFilter() needs a board to provide its filter.');
	}

	return filter;
}
