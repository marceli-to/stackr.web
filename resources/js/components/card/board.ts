import { router } from '@inertiajs/vue3';
import type { InjectionKey, Ref } from 'vue';
import { inject } from 'vue';
import { show } from '@/routes/boards';
import type {
	BoardChecklist,
	CardActivity,
	Destination,
	Label,
	LabelColor,
	Member,
	Board,
} from '@/types';

/** What every card on a board can reach: the board, its labels and members. */
export type BoardContext = {
	board: Ref<Board>;
	labels: Ref<Label[]>;
	members: Ref<Member[]>;
	/** The user's boards, loaded when a card is moved or copied. */
	destinations: Ref<Destination[] | undefined>;
	/** The board's checklists, loaded when a checklist is added. */
	checklists: Ref<BoardChecklist[] | undefined>;
	/** The board's template cards. */
	templates: Ref<{ id: number; title: string }[]>;
};

export const boardKey: InjectionKey<BoardContext> = Symbol('board');

export function useBoard(): BoardContext {
	const context = inject(boardKey);

	if (!context) {
		throw new Error('useBoard() needs a board to provide its context.');
	}

	return context;
}

/** What a label without a name is called. */
export const colorNames: Record<LabelColor, string> = {
	green: 'Green',
	yellow: 'Yellow',
	orange: 'Orange',
	red: 'Red',
	purple: 'Purple',
	blue: 'Blue',
	sky: 'Sky',
	gray: 'Gray',
};

/** Opens a card by putting ?card=<id> in the URL; only the card reloads. */
export function openCard(key: string, cardId: number) {
	router.get(
		show.url(key, { query: { card: cardId } }),
		{},
		{ preserveScroll: true, preserveState: true, only: ['card'] },
	);
}

export function closeCard(key: string) {
	router.get(
		show.url(key),
		{},
		{ preserveScroll: true, preserveState: true, only: ['card'] },
	);
}

/** The emojis a comment can get, as in `Reaction::EMOJIS`. */
export const reactionEmojis = ['👍', '❤️', '😄', '🎉', '👀', '🙏'] as const;

/** Saves changes to a card and keeps the board and the open card in place. */
export const keep = { preserveScroll: true, preserveState: true } as const;

const dayMonth = new Intl.DateTimeFormat(undefined, {
	day: 'numeric',
	month: 'short',
});
const dayMonthYear = new Intl.DateTimeFormat(undefined, {
	day: 'numeric',
	month: 'short',
	year: 'numeric',
});
const time = new Intl.DateTimeFormat(undefined, {
	hour: '2-digit',
	minute: '2-digit',
});

/** "24 Oct", with the year only when it is not this year, and the time on request. */
export function formatDate(iso: string, withTime = false): string {
	const date = new Date(iso);
	const day =
		date.getFullYear() === new Date().getFullYear()
			? dayMonth.format(date)
			: dayMonthYear.format(date);

	return withTime ? `${day}, ${time.format(date)}` : day;
}

export type DueState = 'complete' | 'overdue' | 'soon' | 'later';

export function dueState(dueAt: string, completedAt: string | null): DueState {
	if (completedAt) {
		return 'complete';
	}

	const left = new Date(dueAt).getTime() - Date.now();

	if (left < 0) {
		return 'overdue';
	}

	return left < 24 * 60 * 60 * 1000 ? 'soon' : 'later';
}

const relative = new Intl.RelativeTimeFormat(undefined, { numeric: 'auto' });

/** "5 minutes ago", "yesterday", or the date once it is more than a week old. */
export function timeAgo(iso: string): string {
	const seconds = (new Date(iso).getTime() - Date.now()) / 1000;
	const steps: [Intl.RelativeTimeFormatUnit, number][] = [
		['second', 60],
		['minute', 60],
		['hour', 24],
		['day', 7],
	];
	let value = seconds;

	for (const [unit, size] of steps) {
		if (Math.abs(value) < size) {
			return relative.format(Math.round(value), unit);
		}

		value /= size;
	}

	return formatDate(iso, true);
}

export function fileSize(bytes: number): string {
	if (bytes < 1024) {
		return `${bytes} B`;
	}

	const units = ['KB', 'MB', 'GB'];
	let value = bytes / 1024;
	let unit = 0;

	while (value >= 1024 && unit < units.length - 1) {
		value /= 1024;
		unit++;
	}

	return `${value.toFixed(value < 10 ? 1 : 0)} ${units[unit]}`;
}

/** What an activity entry says after the person's name. */
export function describeActivity(activity: CardActivity): string {
	const data = activity.data;
	const self =
		data.user_id !== undefined && data.user_id === activity.user?.id;

	switch (activity.type) {
		case 'created':
			return `added this card to ${data.stack}`;
		case 'from_template':
			return `created this card from the template “${data.from}”`;
		case 'copied':
			return `copied this card from “${data.from}”`;
		case 'moved':
			return `moved this card from ${data.from} to ${data.to}`;
		case 'moved_board':
			return `moved this card from the board ${data.from} to ${data.to}`;
		case 'archived':
			return 'archived this card';
		case 'restored':
			return 'restored this card';
		case 'completed':
			return 'marked this card complete';
		case 'reopened':
			return 'marked this card incomplete';
		case 'due_set':
			return `set the due date to ${formatDate(String(data.due), true)}`;
		case 'due_removed':
			return 'removed the due date';
		case 'member_added':
			return self
				? 'joined this card'
				: `added ${data.name} to this card`;
		case 'member_removed':
			return self
				? 'left this card'
				: `removed ${data.name} from this card`;
		case 'attached':
			return `attached ${data.name}`;
		case 'checklist_added':
			return `added the checklist ${data.title}`;
		case 'item_completed':
			return `completed ${data.title}`;
		default:
			return 'changed this card';
	}
}
