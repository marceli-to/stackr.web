<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import BoardController from '@/actions/App/Http/Controllers/BoardController';
import { Button } from '@/components/ui/button';
import {
	Dialog,
	DialogClose,
	DialogContent,
	DialogDescription,
	DialogFooter,
	DialogHeader,
	DialogTitle,
} from '@/components/ui/dialog';
import type { Board } from '@/types';

defineProps<{
	board: Board | null;
}>();

const open = defineModel<boolean>('open', { required: true });
</script>

<template>
	<Dialog v-model:open="open">
		<DialogContent v-if="board">
			<Form
				v-bind="BoardController.destroy.form(board.key)"
				class="space-y-6"
				@success="open = false"
				v-slot="{ processing }"
			>
				<DialogHeader class="space-y-3">
					<DialogTitle>Delete “{{ board.name }}”?</DialogTitle>
					<DialogDescription>
						All of its lists and cards will be permanently deleted,
						for every member. This cannot be undone.
					</DialogDescription>
				</DialogHeader>

				<DialogFooter class="gap-2">
					<DialogClose as-child>
						<Button type="button" variant="secondary">
							Cancel
						</Button>
					</DialogClose>
					<Button
						type="submit"
						variant="destructive"
						:disabled="processing"
						data-test="confirm-delete-board-button"
					>
						Delete board
					</Button>
				</DialogFooter>
			</Form>
		</DialogContent>
	</Dialog>
</template>
