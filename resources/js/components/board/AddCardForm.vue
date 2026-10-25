<script setup lang="ts">
import { Form, router } from '@inertiajs/vue3';
import { PhCopySimple, PhPlus, PhX } from '@phosphor-icons/vue';
import { ref } from 'vue';
import CardController from '@/actions/App/Http/Controllers/CardController';
import { useBoard } from '@/components/card/board';
import { Button } from '@/components/ui/button';
import {
	Popover,
	PopoverContent,
	PopoverTrigger,
} from '@/components/ui/popover';
import type { BoardStack } from '@/types';

const props = defineProps<{
	stack: BoardStack;
}>();

const { templates } = useBoard();

const open = ref(false);

// A copy of the template goes to the bottom of this list and opens.
function fromTemplate(id: number) {
	router.post(
		CardController.fromTemplate([props.stack.id, id]).url,
		{},
		{ preserveScroll: true, preserveState: true },
	);
}

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
		<div v-else class="flex gap-1.5">
			<Button
				variant="ghost"
				size="sm"
				data-add-card
				class="h-auto min-w-0 flex-1 justify-start bg-current/10 px-3 py-2.5 text-[0.9375rem] leading-snug text-inherit opacity-85 hover:bg-current/15 hover:text-inherit hover:opacity-100 has-[>svg]:px-3"
				@click="open = true"
			>
				<PhPlus />
				Add a card
			</Button>
			<Popover v-if="templates.length">
				<PopoverTrigger as-child>
					<Button
						variant="ghost"
						size="sm"
						class="h-auto bg-current/10 px-3 text-inherit opacity-85 hover:bg-current/15 hover:text-inherit hover:opacity-100 data-[state=open]:bg-current/15"
						:aria-label="`Add a card to ${stack.name} from a template`"
					>
						<PhCopySimple />
					</Button>
				</PopoverTrigger>
				<PopoverContent align="end" class="p-2">
					<p class="px-2 pt-1 pb-2 font-medium">
						Start from a template
					</p>
					<ul>
						<li v-for="template in templates" :key="template.id">
							<button
								type="button"
								class="flex w-full items-center gap-2.5 rounded-[var(--radius-sheet)] px-2 py-1.5 text-left hover:bg-(--menu-hover) focus-visible:outline-2 focus-visible:outline-ring focus-visible:outline-solid"
								@click="fromTemplate(template.id)"
							>
								<PhCopySimple class="size-4 shrink-0" />
								<span class="truncate">{{
									template.title
								}}</span>
							</button>
						</li>
					</ul>
				</PopoverContent>
			</Popover>
		</div>
	</div>
</template>
