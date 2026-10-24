<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import AppTopBar from '@/components/AppTopBar.vue';
import { Toaster } from '@/components/ui/sonner';
import type { BreadcrumbItem, Board } from '@/types';

// Pages still pass breadcrumbs; the top bar's board switcher replaces them.
defineProps<{
	breadcrumbs?: BreadcrumbItem[];
}>();

const page = usePage();

// A board's page stands on the board's colour, top bar included, or
// on its photo under a dark veil, deepening downwards, that keeps the
// white text readable.
const board = computed(() => page.props.board as Board | undefined);
const field = computed(() => board.value?.color);
const background = computed(() =>
	board.value?.background_url
		? {
				backgroundImage: `linear-gradient(rgb(0 0 0 / 0.45), rgb(0 0 0 / 0.7)), url("${board.value.background_url}")`,
			}
		: undefined,
);
</script>

<template>
	<div
		class="flex min-h-svh flex-col"
		:data-field="field"
		:class="[
			field ? 'field-fill text-field-ink' : 'bg-background',
			background ? 'bg-cover bg-fixed bg-center' : '',
		]"
		:style="background"
	>
		<AppTopBar />
		<main class="flex min-h-0 flex-1 flex-col">
			<slot />
		</main>
		<Toaster />
	</div>
</template>
