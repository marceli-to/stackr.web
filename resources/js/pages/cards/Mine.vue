<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { PhCheckSquare } from '@phosphor-icons/vue';
import { computed, ref } from 'vue';
import { dueState, formatDate } from '@/components/card/board';
import { Button } from '@/components/ui/button';
import { myCards } from '@/routes';
import { index, show } from '@/routes/boards';
import type { BoardColor, Label } from '@/types';

type MyCard = {
	id: number;
	title: string;
	start_at: string | null;
	due_at: string | null;
	completed_at: string | null;
	board: { key: string; name: string; color: BoardColor };
	list: string;
	labels: Label[];
	checklist: { done: number; total: number } | null;
};

type Group = {
	key: string;
	title: string;
	empty: string;
	cards: MyCard[];
};

defineOptions({
	layout: {
		breadcrumbs: [
			{ title: 'Boards', href: index() },
			{ title: 'My cards', href: myCards() },
		],
	},
});

const props = defineProps<{
	cards: MyCard[];
}>();

// One board at a time, or all of them.
const boardKey = ref<string | null>(null);
// Completed cards stay out of the way until asked for.
const showDone = ref(false);

const boards = computed(() => {
	const seen = new Map<string, MyCard['board'] & { open: number }>();

	for (const card of props.cards) {
		const board = seen.get(card.board.key) ?? { ...card.board, open: 0 };
		board.open += card.completed_at ? 0 : 1;
		seen.set(card.board.key, board);
	}

	return [...seen.values()].sort((a, b) =>
		a.name.localeCompare(b.name, undefined, { numeric: true }),
	);
});

const onBoard = computed(() =>
	boardKey.value === null
		? props.cards
		: props.cards.filter((card) => card.board.key === boardKey.value),
);
const open = computed(() => onBoard.value.filter((card) => !card.completed_at));
const done = computed(() => onBoard.value.filter((card) => card.completed_at));

const day = 24 * 60 * 60 * 1000;

function startOfDay(date: Date): number {
	return new Date(
		date.getFullYear(),
		date.getMonth(),
		date.getDate(),
	).getTime();
}

// Open cards by when they're due, in the viewer's time zone. The server
// already sorted them, so each group keeps that order.
const groups = computed<Group[]>(() => {
	const now = Date.now();
	const tomorrow = startOfDay(new Date()) + day;
	const week = tomorrow + 6 * day;

	const buckets: Group[] = [
		{
			key: 'overdue',
			title: 'Overdue',
			empty: 'Nothing overdue.',
			cards: [],
		},
		{
			key: 'today',
			title: 'Today',
			empty: 'Nothing due today.',
			cards: [],
		},
		{
			key: 'week',
			title: 'Next 7 days',
			empty: 'Nothing this week.',
			cards: [],
		},
		{ key: 'later', title: 'Later', empty: 'Nothing later.', cards: [] },
		{
			key: 'none',
			title: 'No due date',
			empty: 'Every card has a date.',
			cards: [],
		},
	];

	for (const card of open.value) {
		const due = card.due_at ? new Date(card.due_at).getTime() : null;
		const bucket =
			due === null
				? 4
				: due < now
					? 0
					: due < tomorrow
						? 1
						: due < week
							? 2
							: 3;

		buckets[bucket].cards.push(card);
	}

	if (showDone.value && done.value.length > 0) {
		buckets.push({
			key: 'done',
			title: 'Completed',
			empty: '',
			cards: done.value,
		});
	}

	// Overdue only shows up when something is.
	return buckets.filter(
		(group) => group.key !== 'overdue' || group.cards.length > 0,
	);
});

const overdue = computed(
	() =>
		groups.value.find((group) => group.key === 'overdue')?.cards.length ??
		0,
);

const dueClasses = {
	complete: 'bg-(--color-success) px-1.5 text-white',
	overdue: 'bg-destructive px-1.5 text-white',
	soon: 'bg-(--color-label-yellow) px-1.5 text-(--color-label-ink)',
	later: 'text-muted-foreground',
};

// The time only matters today; elsewhere the day is enough.
function due(card: MyCard, group: string) {
	if (!card.due_at) {
		return null;
	}

	return {
		label: card.start_at
			? `${formatDate(card.start_at)} to ${formatDate(card.due_at)}`
			: formatDate(card.due_at, group === 'today'),
		state: dueState(card.due_at, card.completed_at),
	};
}

function plural(count: number, word: string) {
	return `${count} ${word}${count === 1 ? '' : 's'}`;
}

const chipClass =
	'flex items-center gap-2 rounded-full px-3 py-1.5 text-sm transition-colors duration-(--dur-micro) focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ring focus-visible:outline-solid';
</script>

<template>
	<Head title="My cards" />

	<div class="flex h-full flex-1 flex-col gap-8 px-3 pt-8 pb-12 md:px-5">
		<div class="flex flex-wrap items-end justify-between gap-4">
			<div>
				<h1
					class="font-display text-4xl leading-[1.05] font-bold tracking-(--tracking-display) md:text-[2.75rem]"
				>
					My cards
				</h1>
				<p class="num mt-2 text-muted-foreground">
					{{ plural(open.length, 'open card') }}
					<template v-if="overdue > 0">
						·
						<span class="font-medium text-destructive"
							>{{ overdue }} overdue</span
						>
					</template>
				</p>
			</div>
			<Button
				v-if="done.length > 0"
				variant="secondary"
				size="sm"
				@click="showDone = !showDone"
			>
				{{
					showDone
						? 'Hide completed'
						: `Show completed (${done.length})`
				}}
			</Button>
		</div>

		<nav
			v-if="boards.length > 1"
			class="-mt-2 flex flex-wrap gap-1.5"
			aria-label="Filter by board"
		>
			<button
				type="button"
				:class="[
					chipClass,
					boardKey === null
						? 'bg-foreground text-background'
						: 'bg-(--color-paper-3) hover:bg-(--color-rule)',
				]"
				:aria-pressed="boardKey === null"
				@click="boardKey = null"
			>
				All boards
			</button>
			<button
				v-for="board in boards"
				:key="board.key"
				type="button"
				:class="[
					chipClass,
					boardKey === board.key
						? 'bg-foreground text-background'
						: 'bg-(--color-paper-3) hover:bg-(--color-rule)',
				]"
				:aria-pressed="boardKey === board.key"
				@click="boardKey = boardKey === board.key ? null : board.key"
			>
				<span
					:data-field="board.color"
					class="size-2.5 rounded-full field-fill"
					aria-hidden="true"
				/>
				{{ board.name }}
				<span class="num opacity-60">{{ board.open }}</span>
			</button>
		</nav>

		<div
			v-if="open.length === 0 && !showDone"
			class="rounded-[var(--radius-tile)] bg-(--color-paper-3) p-8 text-muted-foreground"
		>
			{{
				cards.length === 0
					? 'No cards yet. Join a card on a board and it shows up here.'
					: 'Nothing open. Everything you’re on is completed.'
			}}
		</div>

		<!-- Columns by when cards are due, like lists on a board; stacked on phones. -->
		<div
			v-else
			class="flex flex-col gap-5 md:-mx-5 md:flex-row md:items-start md:overflow-x-auto md:px-5 md:pb-4"
		>
			<section
				v-for="group in groups"
				:key="group.key"
				:aria-labelledby="`group-${group.key}`"
				class="flex shrink-0 flex-col rounded-[var(--radius-tile)] bg-(--color-paper-3) md:max-w-80 md:min-w-60 md:flex-1 md:basis-0"
			>
				<h2
					:id="`group-${group.key}`"
					class="flex items-baseline gap-2 px-3.5 pt-3 pb-2.5 font-display text-2xl leading-tight font-bold"
				>
					<span
						v-if="group.key === 'overdue' && group.cards.length > 0"
						class="size-2.5 self-center rounded-full bg-destructive"
						aria-hidden="true"
					/>
					{{ group.title }}
					<span
						class="num ml-auto font-sans text-sm font-normal text-muted-foreground"
						>{{ group.cards.length }}</span
					>
				</h2>

				<p
					v-if="group.cards.length === 0"
					class="px-3.5 pb-4 text-sm text-muted-foreground"
				>
					{{ group.empty }}
				</p>

				<ul v-else class="flex flex-col gap-2.5 px-2.5 pb-2.5">
					<li v-for="card in group.cards" :key="card.id">
						<Link
							:href="
								show(card.board.key, {
									query: { card: card.id },
								})
							"
							class="block overflow-hidden rounded-[var(--radius-sheet)] bg-sheet px-3 py-2.5 text-[0.9375rem] leading-snug text-foreground hover:outline-2 hover:outline-current/20 hover:outline-solid focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ring focus-visible:outline-solid"
						>
							<span
								class="mb-1.5 flex min-w-0 items-center gap-1.5 text-xs text-muted-foreground"
							>
								<span
									:data-field="card.board.color"
									class="size-2 shrink-0 rounded-full field-fill"
									aria-hidden="true"
								/>
								<span class="truncate"
									>{{ card.board.name }} ·
									{{ card.list }}</span
								>
							</span>

							<span
								v-if="card.labels.length > 0"
								class="mb-1.5 flex flex-wrap gap-1"
							>
								<span
									v-for="label in card.labels"
									:key="label.id"
									:data-label="label.color"
									class="rounded-[3px] bg-label text-xs leading-none font-medium text-label-ink"
									:class="
										label.name ? 'px-1.5 py-1' : 'h-2 w-10'
									"
									:title="label.name ?? undefined"
								>
									{{ label.name }}
								</span>
							</span>

							<span
								class="block break-words"
								:class="{
									'text-muted-foreground line-through':
										card.completed_at,
								}"
							>
								{{ card.title }}
							</span>

							<span
								v-if="due(card, group.key) || card.checklist"
								class="mt-2 flex flex-wrap items-center gap-x-3 gap-y-1 text-sm"
							>
								<span
									v-if="due(card, group.key)"
									class="num rounded-[3px] py-0.5"
									:class="
										dueClasses[due(card, group.key)!.state]
									"
								>
									{{ due(card, group.key)!.label }}
								</span>
								<span
									v-if="card.checklist"
									class="num flex items-center gap-1"
									:class="
										card.checklist.done ===
										card.checklist.total
											? 'text-(--color-success)'
											: 'text-muted-foreground'
									"
								>
									<PhCheckSquare class="size-4" />
									{{ card.checklist.done }}/{{
										card.checklist.total
									}}
								</span>
							</span>
						</Link>
					</li>
				</ul>
			</section>
		</div>
	</div>
</template>
