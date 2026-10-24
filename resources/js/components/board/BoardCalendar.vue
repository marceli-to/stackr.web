<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { PhCaretLeft, PhCaretRight } from '@phosphor-icons/vue';
import { computed, ref } from 'vue';
import CardController from '@/actions/App/Http/Controllers/CardController';
import { useFilter } from '@/components/board/filter';
import { dueState, keep, openCard, useBoard } from '@/components/card/board';
import { Button } from '@/components/ui/button';
import type { BoardCard, BoardStack } from '@/types';

const props = defineProps<{
	stacks: BoardStack[];
}>();

const { project, labels } = useBoard();
const { shows } = useFilter();

// The first of the month on show.
const month = ref(firstOfMonth(new Date()));

function firstOfMonth(date: Date) {
	return new Date(date.getFullYear(), date.getMonth(), 1);
}

function shift(by: number) {
	month.value = new Date(
		month.value.getFullYear(),
		month.value.getMonth() + by,
		1,
	);
}

const monthName = computed(() =>
	new Intl.DateTimeFormat(undefined, {
		month: 'long',
		year: 'numeric',
	}).format(month.value),
);

// Weeks start on Monday.
const weekdays = computed(() => {
	const format = new Intl.DateTimeFormat(undefined, { weekday: 'short' });

	return Array.from({ length: 7 }, (_, day) =>
		format.format(new Date(2024, 0, 1 + day)),
	);
});

const dayKey = (date: Date) =>
	`${date.getFullYear()}-${date.getMonth()}-${date.getDate()}`;

// Six weeks from the Monday on or before the first of the month.
const days = computed(() => {
	const first = month.value;
	const start = new Date(first);
	start.setDate(1 - ((first.getDay() + 6) % 7));

	return Array.from({ length: 42 }, (_, index) => {
		const date = new Date(start);
		date.setDate(start.getDate() + index);

		return date;
	});
});

const today = dayKey(new Date());

const cardsByDay = computed(() => {
	const byDay = new Map<string, BoardCard[]>();

	for (const card of props.stacks.flatMap((stack) => stack.cards)) {
		if (!card.due_at || !shows(card)) {
			continue;
		}

		const key = dayKey(new Date(card.due_at));
		byDay.set(key, [...(byDay.get(key) ?? []), card]);
	}

	for (const cards of byDay.values()) {
		cards.sort((a, b) => (a.due_at ?? '').localeCompare(b.due_at ?? ''));
	}

	return byDay;
});

const undated = computed(
	() =>
		props.stacks
			.flatMap((stack) => stack.cards)
			.filter((card) => !card.due_at && shows(card)).length,
);

function labelColor(card: BoardCard) {
	return labels.value.find((label) => card.labels.includes(label.id))?.color;
}

const dot = {
	complete: 'bg-(--color-success)',
	overdue: 'bg-destructive',
	soon: 'bg-(--color-label-yellow)',
	later: 'bg-transparent',
};

// Dragging a card to another day moves its due date, keeping the time.
const dragging = ref<BoardCard | null>(null);
const over = ref<string | null>(null);

function drop(date: Date) {
	const card = dragging.value;
	over.value = null;
	dragging.value = null;

	if (!card?.due_at) {
		return;
	}

	const due = new Date(card.due_at);
	const moved = new Date(date);
	moved.setHours(due.getHours(), due.getMinutes(), 0, 0);

	if (moved.getTime() !== due.getTime()) {
		router.patch(
			CardController.update(card.id).url,
			{ due_at: moved.toISOString() },
			keep,
		);
	}
}
</script>

<template>
	<section
		class="flex min-h-0 flex-1 flex-col gap-3 pb-6"
		aria-label="Calendar"
	>
		<div class="flex items-center gap-2">
			<Button
				variant="ghost"
				size="icon"
				class="text-inherit hover:bg-current/10 hover:text-inherit"
				aria-label="Previous month"
				@click="shift(-1)"
			>
				<PhCaretLeft />
			</Button>
			<Button
				variant="ghost"
				size="icon"
				class="text-inherit hover:bg-current/10 hover:text-inherit"
				aria-label="Next month"
				@click="shift(1)"
			>
				<PhCaretRight />
			</Button>
			<h2
				class="font-display text-2xl leading-tight font-bold first-letter:uppercase"
				aria-live="polite"
			>
				{{ monthName }}
			</h2>
			<Button
				variant="ghost"
				size="sm"
				class="ml-1 bg-current/10 text-inherit hover:bg-current/15 hover:text-inherit"
				@click="month = firstOfMonth(new Date())"
			>
				Today
			</Button>
			<p v-if="undated" class="ml-auto text-sm opacity-75">
				{{ undated }} {{ undated === 1 ? 'card has' : 'cards have' }}
				no due date
			</p>
		</div>

		<div class="min-h-0 flex-1 overflow-auto">
			<div
				class="grid min-w-[52rem] grid-cols-7 gap-1.5"
				role="grid"
				:aria-label="monthName"
			>
				<div
					v-for="weekday in weekdays"
					:key="weekday"
					class="px-2 pb-1 text-sm font-semibold opacity-75"
					role="columnheader"
				>
					{{ weekday }}
				</div>

				<div
					v-for="date in days"
					:key="dayKey(date)"
					role="gridcell"
					class="flex min-h-28 flex-col gap-1 rounded-[var(--radius-sheet)] p-1.5 transition-colors duration-(--dur-micro)"
					:class="[
						date.getMonth() === month.getMonth()
							? 'bg-field-well'
							: 'bg-field-well/45',
						over === dayKey(date)
							? 'bg-field-well/60 ring-2 ring-current/40 ring-inset'
							: '',
					]"
					@dragover.prevent="over = dayKey(date)"
					@dragleave="over === dayKey(date) && (over = null)"
					@drop.prevent="drop(date)"
				>
					<span
						class="num flex size-6 items-center justify-center self-start rounded-full text-sm"
						:class="[
							dayKey(date) === today
								? 'bg-field-ink font-semibold text-field'
								: '',
							date.getMonth() === month.getMonth()
								? ''
								: 'opacity-55',
						]"
					>
						{{ date.getDate() }}
					</span>
					<button
						v-for="card in cardsByDay.get(dayKey(date)) ?? []"
						:key="card.id"
						type="button"
						draggable="true"
						class="flex w-full items-center gap-1.5 overflow-hidden rounded-[3px] bg-sheet px-1.5 py-1 text-left text-xs leading-snug text-foreground hover:bg-(--color-paper-2) focus-visible:outline-2 focus-visible:outline-offset-1 focus-visible:outline-ring focus-visible:outline-solid"
						:class="
							card.completed_at ? 'text-muted-foreground' : ''
						"
						:title="card.title"
						@click="openCard(project.key, card.id)"
						@dragstart="dragging = card"
						@dragend="
							dragging = null;
							over = null;
						"
					>
						<span
							v-if="labelColor(card)"
							:data-label="labelColor(card)"
							class="h-3 w-1 shrink-0 rounded-full bg-label"
							aria-hidden="true"
						/>
						<span
							class="truncate"
							:class="card.completed_at ? 'line-through' : ''"
							>{{ card.title }}</span
						>
						<span
							class="ml-auto size-1.5 shrink-0 rounded-full"
							:class="
								dot[dueState(card.due_at!, card.completed_at)]
							"
							aria-hidden="true"
						/>
					</button>
				</div>
			</div>
		</div>
	</section>
</template>
