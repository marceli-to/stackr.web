<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { PhCaretLeft, PhCheck, PhPencilSimple } from '@phosphor-icons/vue';
import { ref } from 'vue';
import CardLabelController from '@/actions/App/Http/Controllers/CardLabelController';
import LabelController from '@/actions/App/Http/Controllers/LabelController';
import { colorNames, keep, useBoard } from '@/components/card/board';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
	Popover,
	PopoverContent,
	PopoverTrigger,
} from '@/components/ui/popover';
import { labelColors } from '@/types';
import type { CardDetail, Label, LabelColor } from '@/types';

const props = defineProps<{
	card: CardDetail;
}>();

const { project, labels } = useBoard();

// The picker lists labels; "editing" swaps the list for a form.
const editing = ref<Label | 'new' | null>(null);
const name = ref('');
const color = ref<LabelColor>('green');

function toggle(label: Label) {
	const route = props.card.labels.includes(label.id)
		? CardLabelController.destroy([props.card.id, label.id])
		: CardLabelController.store([props.card.id, label.id]);

	router.visit(route, keep);
}

function edit(label: Label | 'new') {
	editing.value = label;
	name.value = label === 'new' ? '' : (label.name ?? '');
	color.value = label === 'new' ? 'green' : label.color;
}

function save() {
	const data = { name: name.value.trim() || null, color: color.value };
	const label = editing.value;

	if (label === 'new') {
		router.post(LabelController.store(project.value.id).url, data, {
			...keep,
			onSuccess: () => (editing.value = null),
		});
	} else if (label) {
		router.patch(LabelController.update(label.id).url, data, {
			...keep,
			onSuccess: () => (editing.value = null),
		});
	}
}

function remove() {
	const label = editing.value;

	if (label && label !== 'new') {
		router.delete(LabelController.destroy(label.id).url, {
			...keep,
			onSuccess: () => (editing.value = null),
		});
	}
}
</script>

<template>
	<Popover @update:open="(open) => !open && (editing = null)">
		<PopoverTrigger as-child>
			<slot />
		</PopoverTrigger>
		<PopoverContent class="w-80">
			<template v-if="editing === null">
				<p class="mb-3 font-semibold">Labels</p>
				<ul class="space-y-1.5">
					<li
						v-for="label in labels"
						:key="label.id"
						class="flex items-center gap-1.5"
					>
						<button
							type="button"
							:data-label="label.color"
							class="flex h-8 min-w-0 flex-1 items-center gap-2 rounded-[var(--radius-sheet)] bg-label px-2.5 text-left text-sm font-semibold text-label-ink hover:brightness-95 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ring focus-visible:outline-solid"
							:aria-pressed="card.labels.includes(label.id)"
							@click="toggle(label)"
						>
							<span class="truncate">{{
								label.name || colorNames[label.color]
							}}</span>
							<PhCheck
								v-if="card.labels.includes(label.id)"
								class="ml-auto size-4 shrink-0"
							/>
						</button>
						<Button
							variant="ghost"
							size="icon-sm"
							:aria-label="`Edit label ${label.name || colorNames[label.color]}`"
							@click="edit(label)"
						>
							<PhPencilSimple />
						</Button>
					</li>
				</ul>
				<p
					v-if="labels.length === 0"
					class="text-sm text-muted-foreground"
				>
					This project has no labels yet.
				</p>
				<Button
					variant="secondary"
					class="mt-3 w-full"
					@click="edit('new')"
				>
					Create a label
				</Button>
			</template>

			<form v-else class="space-y-4" @submit.prevent="save">
				<div class="flex items-center gap-1">
					<Button
						type="button"
						variant="ghost"
						size="icon-sm"
						aria-label="Back to labels"
						@click="editing = null"
					>
						<PhCaretLeft />
					</Button>
					<p class="font-semibold">
						{{
							editing === 'new' ? 'Create a label' : 'Edit label'
						}}
					</p>
				</div>

				<div
					:data-label="color"
					class="flex h-9 items-center rounded-[var(--radius-sheet)] bg-label px-3 text-sm font-semibold text-label-ink"
				>
					{{ name || colorNames[color] }}
				</div>

				<div class="grid gap-2">
					<label for="label-name" class="text-sm font-semibold"
						>Name</label
					>
					<Input
						id="label-name"
						v-model="name"
						name="name"
						maxlength="60"
						placeholder="Optional"
						v-focus
					/>
				</div>

				<fieldset>
					<legend class="mb-2 text-sm font-semibold">Colour</legend>
					<div class="grid grid-cols-4 gap-1.5">
						<label
							v-for="option in labelColors"
							:key="option"
							:data-label="option"
							class="h-8 cursor-pointer rounded-[var(--radius-sheet)] bg-label has-checked:outline-2 has-checked:outline-offset-2 has-checked:outline-foreground has-checked:outline-solid has-focus-visible:outline-2 has-focus-visible:outline-offset-2 has-focus-visible:outline-ring has-focus-visible:outline-solid"
						>
							<input
								v-model="color"
								type="radio"
								name="color"
								:value="option"
								class="sr-only"
							/>
							<span class="sr-only">{{
								colorNames[option]
							}}</span>
						</label>
					</div>
				</fieldset>

				<div class="flex items-center gap-2">
					<Button type="submit">
						{{ editing === 'new' ? 'Create' : 'Save' }}
					</Button>
					<Button
						v-if="editing !== 'new'"
						type="button"
						variant="ghost"
						class="ml-auto text-destructive"
						@click="remove"
					>
						Delete
					</Button>
				</div>
			</form>
		</PopoverContent>
	</Popover>
</template>
