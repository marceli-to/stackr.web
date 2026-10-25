<script setup lang="ts">
import { Form, router } from '@inertiajs/vue3';
import {
	PhArchive,
	PhArrowsInLineHorizontal,
	PhDotsThree,
	PhGauge,
	PhPencilSimple,
} from '@phosphor-icons/vue';
import { useStorage } from '@vueuse/core';
import { computed, nextTick, ref, useTemplateRef } from 'vue';
import { VueDraggable } from 'vue-draggable-plus';
import type { DraggableEvent } from 'vue-draggable-plus';
import CardController from '@/actions/App/Http/Controllers/CardController';
import StackController from '@/actions/App/Http/Controllers/StackController';
import AddCardForm from '@/components/board/AddCardForm.vue';
import BoardCardItem from '@/components/board/BoardCardItem.vue';
import { useFilter } from '@/components/board/filter';
import { saveMove } from '@/components/board/useBoardMove';
import { useBoard } from '@/components/card/board';
import { Button } from '@/components/ui/button';
import {
	DropdownMenu,
	DropdownMenuContent,
	DropdownMenuItem,
	DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import {
	Dialog,
	DialogClose,
	DialogContent,
	DialogDescription,
	DialogFooter,
	DialogHeader,
	DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import type { BoardCard, BoardStack } from '@/types';

const props = defineProps<{
	stack: BoardStack;
	resync: () => void;
}>();

const cards = defineModel<BoardCard[]>('cards', { required: true });

// Filtered-out cards stay in the list, only hidden, so dropped cards still
// land at the right index among all of the list's cards.
const { shows } = useFilter();
const { board } = useBoard();

// Collapsing is a personal view, so it's remembered in the browser.
const collapsedLists = useStorage<number[]>(
	`stackr.collapsed.${board.value.id}`,
	[],
);
const collapsed = computed(() => collapsedLists.value.includes(props.stack.id));

function toggleCollapsed() {
	collapsedLists.value = collapsed.value
		? collapsedLists.value.filter((id) => id !== props.stack.id)
		: [...collapsedLists.value, props.stack.id];
}

// The card limit is shared: everyone sees when a list holds too many.
const over = computed(
	() =>
		props.stack.wip_limit !== null &&
		cards.value.length > props.stack.wip_limit,
);
const limitOpen = ref(false);
const limit = ref<string | number>('');

function editLimit() {
	limit.value = props.stack.wip_limit ?? '';
	limitOpen.value = true;
}

function saveLimit(value: string | number | null) {
	router.patch(
		StackController.limit(props.stack.id).url,
		{ wip_limit: value === '' || value === null ? null : Number(value) },
		{
			preserveScroll: true,
			preserveState: true,
			onSuccess: () => (limitOpen.value = false),
		},
	);
}

const renaming = ref(false);
const nameInput = useTemplateRef('nameInput');

async function startRename() {
	renaming.value = true;
	await nextTick();
	nameInput.value?.select();
}

// The list and its cards leave the board; the archive can bring them back.
function archive() {
	router.patch(
		StackController.archive(props.stack.id).url,
		{},
		{ preserveScroll: true, preserveState: true },
	);
}

// Leaving the field saves, unless Escape already cancelled or nothing changed.
function finishRename(input: HTMLInputElement) {
	if (!renaming.value) {
		return;
	}

	if (input.value.trim() === props.stack.name) {
		renaming.value = false;

		return;
	}

	input.form?.requestSubmit();
}

// A card was reordered within this list, or dropped in from another one.
function onCardMoved(event: DraggableEvent<BoardCard>, fromElsewhere: boolean) {
	if (event.newIndex === undefined) {
		return;
	}

	if (!fromElsewhere && event.newIndex === event.oldIndex) {
		return;
	}

	saveMove(
		CardController.move(event.data.id),
		{ index: event.newIndex, stack_id: props.stack.id },
		props.resync,
	);
}
</script>

<template>
	<section
		v-if="collapsed"
		data-stack
		class="flex w-11 shrink-0 cursor-grab flex-col items-center gap-3 rounded-[var(--radius-tile)] bg-field-well py-3 active:cursor-grabbing"
		:aria-label="`${stack.name}, collapsed`"
		data-stack-handle
	>
		<button
			type="button"
			class="flex flex-col items-center gap-3 rounded-[3px] focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ring focus-visible:outline-solid"
			:aria-label="`Expand ${stack.name}`"
			@click="toggleCollapsed"
		>
			<PhArrowsInLineHorizontal class="size-4 rotate-90 opacity-75" />
			<span
				class="font-display text-xl leading-none font-bold [writing-mode:vertical-rl]"
				>{{ stack.name }}</span
			>
			<span
				class="num rounded-full px-1.5 py-0.5 text-xs font-medium"
				:class="over ? 'bg-destructive text-white' : 'bg-current/10'"
				>{{ cards.length }}</span
			>
		</button>
	</section>
	<section
		v-else
		data-stack
		class="flex max-h-[calc(100svh-19.25rem)] w-72 shrink-0 flex-col rounded-[var(--radius-tile)] bg-field-well"
		:aria-label="stack.name"
	>
		<header
			class="flex items-center gap-1 px-3 pt-2.5 pb-2"
			data-stack-handle
			:class="renaming ? '' : 'cursor-grab active:cursor-grabbing'"
		>
			<Form
				v-if="renaming"
				v-bind="StackController.update.form(stack.id)"
				:options="{ preserveScroll: true, preserveState: true }"
				class="flex-1"
				@success="renaming = false"
				v-slot="{ errors }"
			>
				<input
					ref="nameInput"
					name="name"
					:value="stack.name"
					required
					maxlength="255"
					aria-label="List name"
					class="w-full rounded-[var(--radius-sheet)] bg-sheet px-2 py-1 text-[0.9375rem] font-medium text-foreground outline-hidden focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ring focus-visible:outline-solid"
					@blur="finishRename($event.target as HTMLInputElement)"
					@keydown.esc="renaming = false"
				/>
				<p v-if="errors.name" class="mt-1 text-xs text-destructive">
					{{ errors.name }}
				</p>
			</Form>
			<h3
				v-else
				class="min-w-0 flex-1 truncate px-1 pb-0.5 font-display text-2xl leading-tight font-bold"
				@dblclick="startRename"
			>
				{{ stack.name }}
			</h3>
			<button
				v-if="stack.wip_limit !== null && !renaming"
				type="button"
				class="num shrink-0 rounded-full px-2 py-0.5 text-xs font-medium focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ring focus-visible:outline-solid"
				:class="over ? 'bg-destructive text-white' : 'bg-current/10'"
				:title="over ? 'More cards than the limit' : 'Card limit'"
				:aria-label="`${cards.length} of at most ${stack.wip_limit} cards`"
				@click="editLimit"
			>
				{{ cards.length }} / {{ stack.wip_limit }}
			</button>

			<DropdownMenu>
				<DropdownMenuTrigger as-child>
					<Button
						variant="ghost"
						size="icon"
						class="size-8 text-inherit hover:bg-current/10 hover:text-inherit data-[state=open]:bg-current/10"
						:aria-label="`Actions for ${stack.name}`"
					>
						<PhDotsThree />
					</Button>
				</DropdownMenuTrigger>
				<DropdownMenuContent align="end">
					<DropdownMenuItem @select="startRename">
						<PhPencilSimple />
						Rename
					</DropdownMenuItem>
					<DropdownMenuItem @select="editLimit">
						<PhGauge />
						Card limit…
					</DropdownMenuItem>
					<DropdownMenuItem @select="toggleCollapsed">
						<PhArrowsInLineHorizontal />
						Collapse list
					</DropdownMenuItem>
					<DropdownMenuItem @select="archive">
						<PhArchive />
						Archive list
					</DropdownMenuItem>
				</DropdownMenuContent>
			</DropdownMenu>
		</header>

		<VueDraggable
			v-model="cards"
			group="cards"
			:animation="150"
			ghost-class="opacity-40"
			class="flex min-h-2 flex-col gap-3 overflow-y-auto px-2.5 py-1"
			:data-stack-id="stack.id"
			@update="onCardMoved($event, false)"
			@add="onCardMoved($event, true)"
		>
			<BoardCardItem
				v-for="card in cards"
				v-show="shows(card)"
				:key="card.id"
				:card="card"
			/>
		</VueDraggable>

		<AddCardForm :stack="stack" />
	</section>

	<Dialog v-model:open="limitOpen">
		<DialogContent class="sm:max-w-sm">
			<form class="space-y-5" @submit.prevent="saveLimit(limit)">
				<DialogHeader>
					<DialogTitle>Card limit</DialogTitle>
					<DialogDescription>
						How many cards “{{ stack.name }}” should hold at most.
						The list turns red when it has more; nothing is blocked.
					</DialogDescription>
				</DialogHeader>
				<div class="grid gap-2">
					<label for="wip-limit" class="text-sm font-medium"
						>Limit</label
					>
					<Input
						id="wip-limit"
						v-model="limit"
						type="number"
						min="1"
						max="999"
						placeholder="No limit"
						class="num w-32"
					/>
				</div>
				<DialogFooter class="gap-2">
					<Button
						v-if="stack.wip_limit !== null"
						type="button"
						variant="ghost"
						class="mr-auto"
						@click="saveLimit(null)"
					>
						Remove limit
					</Button>
					<DialogClose as-child>
						<Button type="button" variant="secondary"
							>Cancel</Button
						>
					</DialogClose>
					<Button type="submit">Save</Button>
				</DialogFooter>
			</form>
		</DialogContent>
	</Dialog>
</template>
