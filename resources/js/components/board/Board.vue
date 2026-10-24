<script setup lang="ts">
import { ref, watch } from 'vue';
import { VueDraggable } from 'vue-draggable-plus';
import type { DraggableEvent } from 'vue-draggable-plus';
import StackController from '@/actions/App/Http/Controllers/StackController';
import AddStackForm from '@/components/board/AddStackForm.vue';
import StackColumn from '@/components/board/StackColumn.vue';
import { hasPendingMoves, saveMove } from '@/components/board/useBoardMove';
import type { BoardStack, Project } from '@/types';

const props = defineProps<{
	project: Project;
	stacks: BoardStack[];
}>();

// Local copy that drag & drop rearranges straight away. (structuredClone
// can't copy Vue's reactive props, so copy by hand.)
function copy(stacks: BoardStack[]): BoardStack[] {
	return stacks.map((stack) => ({
		...stack,
		cards: stack.cards.map((card) => ({ ...card })),
	}));
}

const board = ref<BoardStack[]>(copy(props.stacks));

function resync() {
	board.value = copy(props.stacks);
}

watch(
	() => props.stacks,
	() => {
		if (!hasPendingMoves()) {
			resync();
		}
	},
);

function onStackMoved(event: DraggableEvent<BoardStack>) {
	if (event.newIndex === undefined || event.newIndex === event.oldIndex) {
		return;
	}

	saveMove(
		StackController.move(event.data.id),
		{ index: event.newIndex },
		resync,
	);
}
</script>

<template>
	<div class="flex min-h-0 flex-1 gap-5 overflow-x-auto pb-6">
		<VueDraggable
			v-model="board"
			:animation="150"
			handle="[data-stack-handle]"
			ghost-class="opacity-40"
			class="flex items-start gap-5"
			@update="onStackMoved"
		>
			<StackColumn
				v-for="stack in board"
				:key="stack.id"
				v-model:cards="stack.cards"
				:stack="stack"
				:resync="resync"
			/>
		</VueDraggable>

		<AddStackForm :project="project" />
	</div>
</template>
