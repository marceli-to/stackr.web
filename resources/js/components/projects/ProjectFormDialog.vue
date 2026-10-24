<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import { computed } from 'vue';
import ProjectController from '@/actions/App/Http/Controllers/ProjectController';
import InputError from '@/components/InputError.vue';
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
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { projectColors } from '@/types';
import type { Project, ProjectColor } from '@/types';

// Creates a project, or edits `project` when one is given.
const props = defineProps<{
	project?: Project | null;
	suggestedColor?: ProjectColor;
}>();

const colorNames: Record<ProjectColor, string> = {
	cobalt: 'Cobalt',
	rust: 'Rust',
	moss: 'Moss',
	oxblood: 'Oxblood',
	lagoon: 'Lagoon',
	plum: 'Plum',
};

const open = defineModel<boolean>('open', { required: true });

const form = computed(() =>
	props.project
		? ProjectController.update.form(props.project.id)
		: ProjectController.store.form(),
);
</script>

<template>
	<Dialog v-model:open="open">
		<DialogContent>
			<Form
				:key="project?.id ?? 'new'"
				v-bind="form"
				:reset-on-success="!project"
				:options="{ preserveScroll: true }"
				class="space-y-6"
				@success="open = false"
				v-slot="{ errors, processing }"
			>
				<DialogHeader>
					<DialogTitle>
						{{ project ? 'Edit project' : 'New project' }}
					</DialogTitle>
					<DialogDescription>
						{{
							project
								? 'Change the name, description, link or colour.'
								: 'Each project gets its own board.'
						}}
					</DialogDescription>
				</DialogHeader>

				<div class="grid gap-2">
					<Label for="project-name">Name</Label>
					<Input
						id="project-name"
						name="name"
						:default-value="project?.name"
						required
						maxlength="255"
						placeholder="e.g. Website relaunch"
						v-focus
					/>
					<InputError :message="errors.name" />
				</div>

				<div class="grid gap-2">
					<Label for="project-description">
						Description
						<span class="font-normal text-muted-foreground"
							>(optional)</span
						>
					</Label>
					<textarea
						id="project-description"
						name="description"
						rows="3"
						maxlength="2000"
						:value="project?.description ?? ''"
						class="w-full min-w-0 rounded-[var(--radius-sheet)] bg-sheet px-3 py-2 text-base shadow-xs ring-1 focus-field ring-(--control-edge) outline-hidden ring-inset placeholder:text-muted-foreground sm:text-[0.9375rem] dark:bg-(--color-paper-2)"
					/>
					<InputError :message="errors.description" />
				</div>

				<div class="grid gap-2">
					<Label for="project-url">
						Link
						<span class="font-normal text-muted-foreground"
							>(optional)</span
						>
					</Label>
					<Input
						id="project-url"
						name="url"
						type="url"
						inputmode="url"
						autocomplete="url"
						maxlength="2048"
						:default-value="project?.url ?? ''"
						placeholder="https://"
					/>
					<InputError :message="errors.url" />
				</div>

				<fieldset class="grid gap-2">
					<legend class="mb-2 text-sm font-medium">Colour</legend>
					<div class="flex flex-wrap gap-2">
						<label
							v-for="color in projectColors"
							:key="color"
							:data-field="color"
							class="relative size-10 cursor-pointer rounded-[var(--radius-sheet)] bg-field focus-visible:outline-solid has-checked:outline-2 has-checked:outline-offset-2 has-checked:outline-foreground has-checked:outline-solid has-focus-visible:outline-2 has-focus-visible:outline-offset-2 has-focus-visible:outline-(--color-focus) has-focus-visible:outline-solid"
						>
							<input
								type="radio"
								name="color"
								:value="color"
								:checked="
									color === (project?.color ?? suggestedColor)
								"
								class="sr-only"
							/>
							<span class="sr-only">{{ colorNames[color] }}</span>
						</label>
					</div>
					<InputError :message="errors.color" />
				</fieldset>

				<DialogFooter class="gap-2">
					<DialogClose as-child>
						<Button type="button" variant="secondary">
							Cancel
						</Button>
					</DialogClose>
					<Button
						type="submit"
						:disabled="processing"
						data-test="save-project-button"
					>
						{{ project ? 'Save' : 'Create project' }}
					</Button>
				</DialogFooter>
			</Form>
		</DialogContent>
	</Dialog>
</template>
