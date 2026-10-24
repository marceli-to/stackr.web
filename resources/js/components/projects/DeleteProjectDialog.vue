<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import ProjectController from '@/actions/App/Http/Controllers/ProjectController';
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
import type { Project } from '@/types';

defineProps<{
	project: Project | null;
}>();

const open = defineModel<boolean>('open', { required: true });
</script>

<template>
	<Dialog v-model:open="open">
		<DialogContent v-if="project">
			<Form
				v-bind="ProjectController.destroy.form(project.id)"
				class="space-y-6"
				@success="open = false"
				v-slot="{ processing }"
			>
				<DialogHeader class="space-y-3">
					<DialogTitle>Delete “{{ project.name }}”?</DialogTitle>
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
						data-test="confirm-delete-project-button"
					>
						Delete project
					</Button>
				</DialogFooter>
			</Form>
		</DialogContent>
	</Dialog>
</template>
