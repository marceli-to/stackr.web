<script setup lang="ts">
import { ref, watch } from 'vue';
import { VueDraggable } from 'vue-draggable-plus';
import type { DraggableEvent } from 'vue-draggable-plus';
import StackController from '@/actions/App/Http/Controllers/StackController';
import AddStackForm from '@/components/board/AddStackForm.vue';
import StackColumn from '@/components/board/StackColumn.vue';
import { useBoardKeys } from '@/components/board/useBoardKeys';
import { hasPendingMoves, saveMove } from '@/components/board/useBoardMove';
import type { BoardStack, Board } from '@/types';

const props = defineProps<{
	board: Board;
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

const lists = ref<BoardStack[]>(copy(props.stacks));

function resync() {
	lists.value = copy(props.stacks);
}

watch(
	() => props.stacks,
	() => {
		if (!hasPendingMoves()) {
			resync();
		}
	},
);

useBoardKeys();

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
	<div class="flex min-h-0 flex-1 gap-5 overflow-x-auto pb-6 select-none">
		<VueDraggable
			v-model="lists"
			:animation="150"
			handle="[data-stack-handle]"
			ghost-class="opacity-40"
			class="flex items-start gap-5"
			@update="onStackMoved"
		>
			<StackColumn
				v-for="stack in lists"
				:key="stack.id"
				v-model:cards="stack.cards"
				:stack="stack"
				:resync="resync"
			/>
		</VueDraggable>

		<AddStackForm :board="board" />
	</div>
</template>
