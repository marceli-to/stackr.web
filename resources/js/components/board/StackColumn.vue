<script setup lang="ts">
import { Form, router } from '@inertiajs/vue3';
import { PhArchive, PhDotsThree, PhPencilSimple } from '@phosphor-icons/vue';
import { nextTick, ref, useTemplateRef } from 'vue';
import { VueDraggable } from 'vue-draggable-plus';
import type { DraggableEvent } from 'vue-draggable-plus';
import CardController from '@/actions/App/Http/Controllers/CardController';
import StackController from '@/actions/App/Http/Controllers/StackController';
import AddCardForm from '@/components/board/AddCardForm.vue';
import BoardCardItem from '@/components/board/BoardCardItem.vue';
import { useFilter } from '@/components/board/filter';
import { saveMove } from '@/components/board/useBoardMove';
import { Button } from '@/components/ui/button';
import {
	DropdownMenu,
	DropdownMenuContent,
	DropdownMenuItem,
	DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import type { BoardCard, BoardStack } from '@/types';

const props = defineProps<{
	stack: BoardStack;
	resync: () => void;
}>();

const cards = defineModel<BoardCard[]>('cards', { required: true });

// Filtered-out cards stay in the list, only hidden, so dropped cards still
// land at the right index among all of the list's cards.
const { shows } = useFilter();

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
					class="w-full rounded-[var(--radius-sheet)] bg-sheet px-2 py-1 text-[0.9375rem] font-semibold text-foreground outline-hidden focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ring focus-visible:outline-solid"
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
</template>
