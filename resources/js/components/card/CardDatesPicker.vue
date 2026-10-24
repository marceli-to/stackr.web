<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { ref } from 'vue';
import CardController from '@/actions/App/Http/Controllers/CardController';
import { keep } from '@/components/card/board';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
	Popover,
	PopoverContent,
	PopoverTrigger,
} from '@/components/ui/popover';
import type { CardDetail } from '@/types';

const props = defineProps<{
	card: CardDetail;
}>();

const open = ref(false);
const start = ref('');
const due = ref('');
const errors = ref<Record<string, string>>({});

const pad = (n: number) => String(n).padStart(2, '0');

// The inputs work in local time; the server stores UTC.
function toDateInput(iso: string | null): string {
	if (!iso) {
		return '';
	}

	const d = new Date(iso);

	return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`;
}

function toDateTimeInput(iso: string | null): string {
	if (!iso) {
		return '';
	}

	const d = new Date(iso);

	return `${toDateInput(iso)}T${pad(d.getHours())}:${pad(d.getMinutes())}`;
}

function toIso(local: string): string | null {
	return local ? new Date(local).toISOString() : null;
}

function reset(isOpen: boolean) {
	open.value = isOpen;

	if (isOpen) {
		start.value = toDateInput(props.card.start_at);
		due.value = toDateTimeInput(props.card.due_at);
		errors.value = {};
	}
}

function save(data: { start_at: string | null; due_at: string | null }) {
	router.patch(CardController.update(props.card.id).url, data, {
		...keep,
		onSuccess: () => (open.value = false),
		onError: (e) => (errors.value = e),
	});
}
</script>

<template>
	<Popover :open="open" @update:open="reset">
		<PopoverTrigger as-child>
			<slot />
		</PopoverTrigger>
		<PopoverContent>
			<form
				class="space-y-4"
				@submit.prevent="
					save({
						start_at: start ? toIso(`${start}T00:00`) : null,
						due_at: toIso(due),
					})
				"
			>
				<p class="font-semibold">Dates</p>
				<div class="grid gap-2">
					<label for="card-start" class="text-sm font-semibold"
						>Start date</label
					>
					<Input
						id="card-start"
						v-model="start"
						type="date"
						name="start_at"
					/>
					<InputError :message="errors.start_at" />
				</div>
				<div class="grid gap-2">
					<label for="card-due" class="text-sm font-semibold"
						>Due date</label
					>
					<Input
						id="card-due"
						v-model="due"
						type="datetime-local"
						name="due_at"
					/>
					<InputError :message="errors.due_at" />
				</div>
				<div class="flex items-center gap-2">
					<Button type="submit">Save</Button>
					<Button
						v-if="card.start_at || card.due_at"
						type="button"
						variant="ghost"
						class="ml-auto"
						@click="save({ start_at: null, due_at: null })"
					>
						Remove dates
					</Button>
				</div>
			</form>
		</PopoverContent>
	</Popover>
</template>
