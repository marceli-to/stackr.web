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

// Completed cards stay out of the way until asked for.
const showDone = ref(false);
const done = computed(() => props.cards.filter((card) => card.completed_at));
const shown = computed(() =>
	showDone.value
		? props.cards
		: props.cards.filter((card) => !card.completed_at),
);

const dueClasses = {
	complete: 'bg-(--color-success) px-1.5 text-white',
	overdue: 'bg-destructive px-1.5 text-white',
	soon: 'bg-(--color-label-yellow) px-1.5 text-(--color-label-ink)',
	later: 'text-muted-foreground',
};

function due(card: MyCard) {
	if (!card.due_at) {
		return null;
	}

	return {
		label: card.start_at
			? `${formatDate(card.start_at)} to ${formatDate(card.due_at)}`
			: formatDate(card.due_at),
		state: dueState(card.due_at, card.completed_at),
	};
}
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
				<p class="mt-2 text-muted-foreground">
					Cards you're on, across all your boards, soonest due first.
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

		<p v-if="shown.length === 0" class="text-muted-foreground">
			{{
				cards.length === 0
					? 'No cards yet. Join a card on a board and it shows up here.'
					: 'Nothing open. Everything you’re on is completed.'
			}}
		</p>

		<ul v-else class="max-w-3xl divide-y divide-border">
			<li v-for="card in shown" :key="card.id">
				<Link
					:href="show(card.board.key, { query: { card: card.id } })"
					class="-mx-2 flex items-start gap-3 rounded-[var(--radius-sheet)] px-2 py-3 hover:bg-(--color-paper-2) focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ring focus-visible:outline-solid"
				>
					<span
						:data-field="card.board.color"
						class="mt-1.5 size-3 shrink-0 rounded-full field-fill"
						aria-hidden="true"
					/>
					<span class="min-w-0 flex-1">
						<span
							class="block font-medium break-words"
							:class="{
								'text-muted-foreground line-through':
									card.completed_at,
							}"
						>
							{{ card.title }}
						</span>
						<span
							class="mt-0.5 block truncate text-sm text-muted-foreground"
						>
							{{ card.board.name }} · {{ card.list }}
						</span>
						<span
							v-if="card.labels.length > 0"
							class="mt-2 flex flex-wrap gap-1"
						>
							<span
								v-for="label in card.labels"
								:key="label.id"
								:data-label="label.color"
								class="rounded-[3px] bg-label text-xs leading-none font-medium text-label-ink"
								:class="label.name ? 'px-1.5 py-1' : 'h-2 w-10'"
								:title="label.name ?? undefined"
							>
								{{ label.name }}
							</span>
						</span>
					</span>
					<span
						class="flex shrink-0 flex-col items-end gap-1 text-sm"
					>
						<span
							v-if="due(card)"
							class="num rounded-[3px] py-0.5"
							:class="dueClasses[due(card)!.state]"
						>
							{{ due(card)!.label }}
						</span>
						<span
							v-if="card.checklist"
							class="num flex items-center gap-1 text-muted-foreground"
						>
							<PhCheckSquare class="size-4" />
							{{ card.checklist.done }}/{{ card.checklist.total }}
						</span>
					</span>
				</Link>
			</li>
		</ul>
	</div>
</template>
