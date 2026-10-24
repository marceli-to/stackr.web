<script setup lang="ts">
import { PhKeyboard } from '@phosphor-icons/vue';
import { onKeyStroke } from '@vueuse/core';
import { ref } from 'vue';
import { busy } from '@/components/board/useBoardKeys';
import { Button } from '@/components/ui/button';
import {
	Dialog,
	DialogContent,
	DialogDescription,
	DialogHeader,
	DialogTitle,
	DialogTrigger,
} from '@/components/ui/dialog';

const open = ref(false);

onKeyStroke('?', (event) => {
	if (!busy(event)) {
		event.preventDefault();
		open.value = true;
	}
});

const shortcuts = [
	{ keys: ['/'], does: 'Search cards' },
	{ keys: ['n'], does: 'Add a card to the list you’re in' },
	{ keys: ['←', '↑', '↓', '→'], does: 'Move between cards' },
	{ keys: ['Enter'], does: 'Open the card' },
	{ keys: ['Esc'], does: 'Close the card, clear the search' },
	{ keys: ['⌘', 'Enter'], does: 'Save a comment' },
	{ keys: ['?'], does: 'Show these shortcuts' },
];
</script>

<template>
	<Dialog v-model:open="open">
		<DialogTrigger as-child>
			<Button
				variant="ghost"
				size="icon"
				class="text-inherit hover:bg-current/10 hover:text-inherit data-[state=open]:bg-current/10"
				aria-label="Keyboard shortcuts"
				aria-keyshortcuts="?"
			>
				<PhKeyboard />
			</Button>
		</DialogTrigger>
		<DialogContent class="sm:max-w-md">
			<DialogHeader>
				<DialogTitle>Shortcuts</DialogTitle>
				<DialogDescription
					>On the board, while you’re not typing.</DialogDescription
				>
			</DialogHeader>
			<dl class="divide-y divide-(--surface-edge)">
				<div
					v-for="shortcut in shortcuts"
					:key="shortcut.does"
					class="flex items-center justify-between gap-4 py-2.5"
				>
					<dt class="text-sm">{{ shortcut.does }}</dt>
					<dd class="flex gap-1">
						<kbd
							v-for="key in shortcut.keys"
							:key="key"
							class="num min-w-7 rounded-[3px] bg-(--color-paper-2) px-1.5 py-0.5 text-center text-sm font-semibold"
							>{{ key }}</kbd
						>
					</dd>
				</div>
			</dl>
		</DialogContent>
	</Dialog>
</template>
