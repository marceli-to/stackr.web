<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import CardController from '@/actions/App/Http/Controllers/CardController';
import StackController from '@/actions/App/Http/Controllers/StackController';
import { openCard, timeAgo, useBoard } from '@/components/card/board';
import { Button } from '@/components/ui/button';
import {
	Dialog,
	DialogContent,
	DialogDescription,
	DialogHeader,
	DialogTitle,
} from '@/components/ui/dialog';
import { Spinner } from '@/components/ui/spinner';
import type { BoardArchive } from '@/types';

const props = defineProps<{
	archive?: BoardArchive;
}>();

const { board } = useBoard();

// Opened from the board menu.
const open = defineModel<boolean>('open', { default: false });
const tab = ref<'cards' | 'stacks'>('cards');
// "card-12" or "stack-3" while its delete asks for confirmation.
const confirming = ref<string | null>(null);

// The archive isn't part of the board's props; it loads when the panel opens.
watch(open, (isOpen) => {
	confirming.value = null;

	if (isOpen) {
		router.reload({ only: ['archive'] });
	}
});

// After a change the board and the archive both reload.
const reload = {
	preserveScroll: true,
	preserveState: true,
	only: ['stacks', 'archive'],
};

function restoreCard(id: number) {
	router.patch(CardController.restore(id).url, {}, reload);
}

function deleteCard(id: number) {
	router.delete(CardController.destroy(id).url, reload);
}

function restoreStack(id: number) {
	router.patch(StackController.restore(id).url, {}, reload);
}

function deleteStack(id: number) {
	router.delete(StackController.destroy(id).url, reload);
}

function show(id: number) {
	open.value = false;
	openCard(board.value.key, id);
}

const cards = computed(() => props.archive?.cards ?? []);
const stacks = computed(() => props.archive?.stacks ?? []);

const tabClass = (active: boolean) =>
	active
		? 'bg-(--color-paper-3) text-foreground'
		: 'text-muted-foreground hover:text-foreground';
</script>

<template>
	<Dialog v-model:open="open">
		<DialogContent class="sm:max-w-lg">
			<DialogHeader>
				<DialogTitle>Archive</DialogTitle>
				<DialogDescription>
					Archived cards and lists keep their place. Restore puts them
					back where they were.
				</DialogDescription>
			</DialogHeader>

			<div
				class="flex gap-1 rounded-[var(--radius-sheet)] bg-(--color-paper-2) p-1"
				role="tablist"
			>
				<button
					type="button"
					role="tab"
					class="num flex-1 rounded-[3px] px-3 py-1.5 text-sm font-semibold"
					:class="tabClass(tab === 'cards')"
					:aria-selected="tab === 'cards'"
					@click="tab = 'cards'"
				>
					Cards
					<span v-if="archive" class="opacity-60">{{
						cards.length
					}}</span>
				</button>
				<button
					type="button"
					role="tab"
					class="num flex-1 rounded-[3px] px-3 py-1.5 text-sm font-semibold"
					:class="tabClass(tab === 'stacks')"
					:aria-selected="tab === 'stacks'"
					@click="tab = 'stacks'"
				>
					Lists
					<span v-if="archive" class="opacity-60">{{
						stacks.length
					}}</span>
				</button>
			</div>

			<div v-if="!archive" class="flex justify-center py-8">
				<Spinner />
			</div>

			<ul
				v-else-if="tab === 'cards'"
				class="-mx-2 max-h-[60svh] overflow-y-auto"
			>
				<li
					v-for="card in cards"
					:key="card.id"
					class="flex items-center gap-2 rounded-[var(--radius-sheet)] px-2 py-2 hover:bg-(--color-paper-2)"
				>
					<button
						type="button"
						class="min-w-0 flex-1 rounded-[3px] text-left focus-visible:outline-2 focus-visible:outline-ring focus-visible:outline-solid"
						@click="show(card.id)"
					>
						<span class="block truncate">{{ card.title }}</span>
						<span
							class="block truncate text-sm text-muted-foreground"
						>
							in {{ card.stack }} · archived
							{{ timeAgo(card.archived_at) }}
						</span>
					</button>
					<template v-if="confirming === `card-${card.id}`">
						<Button
							variant="destructive"
							size="sm"
							@click="deleteCard(card.id)"
						>
							Delete
						</Button>
						<Button
							variant="ghost"
							size="sm"
							@click="confirming = null"
						>
							Cancel
						</Button>
					</template>
					<template v-else>
						<Button
							variant="secondary"
							size="sm"
							@click="restoreCard(card.id)"
						>
							Restore
						</Button>
						<Button
							variant="ghost"
							size="sm"
							@click="confirming = `card-${card.id}`"
						>
							Delete
						</Button>
					</template>
				</li>
				<li
					v-if="cards.length === 0"
					class="px-2 py-6 text-center text-sm text-muted-foreground"
				>
					No archived cards.
				</li>
			</ul>

			<ul v-else class="-mx-2 max-h-[60svh] overflow-y-auto">
				<li
					v-for="stack in stacks"
					:key="stack.id"
					class="flex items-center gap-2 rounded-[var(--radius-sheet)] px-2 py-2 hover:bg-(--color-paper-2)"
				>
					<div class="min-w-0 flex-1">
						<p class="truncate">{{ stack.name }}</p>
						<p class="num truncate text-sm text-muted-foreground">
							{{ stack.cards_count }}
							{{ stack.cards_count === 1 ? 'card' : 'cards' }} ·
							archived {{ timeAgo(stack.archived_at) }}
						</p>
					</div>
					<template v-if="confirming === `stack-${stack.id}`">
						<Button
							variant="destructive"
							size="sm"
							@click="deleteStack(stack.id)"
						>
							Delete
						</Button>
						<Button
							variant="ghost"
							size="sm"
							@click="confirming = null"
						>
							Cancel
						</Button>
					</template>
					<template v-else>
						<Button
							variant="secondary"
							size="sm"
							@click="restoreStack(stack.id)"
						>
							Restore
						</Button>
						<Button
							variant="ghost"
							size="sm"
							@click="confirming = `stack-${stack.id}`"
						>
							Delete
						</Button>
					</template>
				</li>
				<li
					v-if="stacks.length === 0"
					class="px-2 py-6 text-center text-sm text-muted-foreground"
				>
					No archived lists.
				</li>
			</ul>

			<p
				v-if="confirming"
				class="text-sm text-muted-foreground"
				aria-live="polite"
			>
				Deleting can't be undone{{
					confirming.startsWith('stack')
						? ', and takes the list’s cards with it'
						: ''
				}}.
			</p>
		</DialogContent>
	</Dialog>
</template>
