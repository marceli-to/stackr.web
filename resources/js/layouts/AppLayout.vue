<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import AppTopBar from '@/components/AppTopBar.vue';
import { Toaster } from '@/components/ui/sonner';
import type { BreadcrumbItem, Project } from '@/types';

// Pages still pass breadcrumbs; the top bar's project switcher replaces them.
defineProps<{
	breadcrumbs?: BreadcrumbItem[];
}>();

const page = usePage();

// A project's page stands on the project's colour, top bar included.
const field = computed(
	() => (page.props.project as Project | undefined)?.color,
);
</script>

<template>
	<div
		class="flex min-h-svh flex-col"
		:data-field="field"
		:class="field ? 'bg-field text-field-ink' : 'bg-background'"
	>
		<AppTopBar />
		<main class="flex min-h-0 flex-1 flex-col">
			<slot />
		</main>
		<Toaster />
	</div>
</template>
