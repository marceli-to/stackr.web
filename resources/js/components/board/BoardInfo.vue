<script setup lang="ts">
import { PhLink } from '@phosphor-icons/vue';
import { computed } from 'vue';
import {
	Dialog,
	DialogContent,
	DialogDescription,
	DialogHeader,
	DialogTitle,
} from '@/components/ui/dialog';
import type { Board } from '@/types';

const props = defineProps<{ board: Board }>();

// Opened from the board menu.
const open = defineModel<boolean>('open', { default: false });

// The link as people read it: no scheme, no trailing slash.
const urlLabel = computed(() =>
	props.board.url?.replace(/^https?:\/\//, '').replace(/\/$/, ''),
);
</script>

<template>
	<Dialog v-model:open="open">
		<DialogContent class="sm:max-w-md">
			<DialogHeader>
				<DialogTitle>{{ board.name }}</DialogTitle>
				<DialogDescription
					v-if="board.description"
					class="text-base whitespace-pre-line"
				>
					{{ board.description }}
				</DialogDescription>
			</DialogHeader>
			<a
				v-if="board.url"
				:href="board.url"
				target="_blank"
				rel="noopener noreferrer"
				class="inline-flex max-w-full items-center gap-1.5 justify-self-start rounded-sm underline decoration-current/40 underline-offset-4 hover:decoration-current focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ring focus-visible:outline-solid"
			>
				<PhLink class="size-4 shrink-0" />
				<span class="truncate">{{ urlLabel }}</span>
			</a>
		</DialogContent>
	</Dialog>
</template>
