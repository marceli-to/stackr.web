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

// A project's page stands on the project's colour, top bar included, or
// on its photo under a slight veil that keeps the white text readable.
const project = computed(() => page.props.project as Project | undefined);
const field = computed(() => project.value?.color);
const background = computed(() =>
	project.value?.background_url
		? {
				backgroundImage: `linear-gradient(rgb(0 0 0 / 0.28), rgb(0 0 0 / 0.28)), url("${project.value.background_url}")`,
			}
		: undefined,
);
</script>

<template>
	<div
		class="flex min-h-svh flex-col"
		:data-field="field"
		:class="[
			field ? 'bg-field text-field-ink' : 'bg-background',
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
