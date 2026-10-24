<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import { PhPlus, PhX } from '@phosphor-icons/vue';
import { ref } from 'vue';
import CardController from '@/actions/App/Http/Controllers/CardController';
import { Button } from '@/components/ui/button';
import type { BoardStack } from '@/types';

defineProps<{
	stack: BoardStack;
}>();

const open = ref(false);

function submitOnEnter(event: KeyboardEvent) {
	if (!event.shiftKey && !event.isComposing) {
		event.preventDefault();
		(event.target as HTMLTextAreaElement).form?.requestSubmit();
	}
}
</script>

<template>
	<div class="px-2.5 pt-2 pb-2.5">
		<!-- Stays open after adding, so several cards can be typed in a row. -->
		<Form
			v-if="open"
			v-bind="CardController.store.form(stack.id)"
			:options="{ preserveScroll: true, preserveState: true }"
			reset-on-success
			class="space-y-2"
			v-slot="{ errors, processing }"
		>
			<textarea
				name="title"
				rows="2"
				required
				maxlength="500"
				placeholder="Card title"
				aria-label="Card title"
				class="block w-full resize-none rounded-[var(--radius-sheet)] bg-sheet px-3 py-2.5 text-[0.9375rem] text-foreground focus-field outline-hidden placeholder:text-muted-foreground"
				v-focus
				@keydown.enter="submitOnEnter"
				@keydown.esc="open = false"
			/>
			<p v-if="errors.title" class="text-xs text-destructive">
				{{ errors.title }}
			</p>
			<div class="flex items-center gap-1">
				<Button
					type="submit"
					size="sm"
					:disabled="processing"
					class="bg-field-ink text-field hover:bg-field-ink/90"
				>
					Add card
				</Button>
				<Button
					type="button"
					variant="ghost"
					size="icon"
					class="size-8 text-inherit hover:bg-current/10 hover:text-inherit"
					aria-label="Close"
					@click="open = false"
				>
					<PhX />
				</Button>
			</div>
		</Form>
		<Button
			v-else
			variant="ghost"
			size="sm"
			class="h-auto w-full justify-start bg-current/10 px-3 py-2.5 text-[0.9375rem] leading-snug text-inherit opacity-85 hover:bg-current/15 hover:text-inherit hover:opacity-100 has-[>svg]:px-3"
			@click="open = true"
		>
			<PhPlus />
			Add a card
		</Button>
	</div>
</template>
