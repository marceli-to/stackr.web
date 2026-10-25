<script setup lang="ts">
import { PhMonitor, PhMoon, PhSun } from '@phosphor-icons/vue';
import { useAppearance } from '@/composables/useAppearance';

const { appearance, updateAppearance } = useAppearance();

const tabs = [
	{ value: 'light', Icon: PhSun, label: 'Light' },
	{ value: 'dark', Icon: PhMoon, label: 'Dark' },
	{ value: 'system', Icon: PhMonitor, label: 'System' },
] as const;
</script>

<template>
	<div
		class="inline-flex gap-1 rounded-[var(--radius-tile)] bg-(--color-paper-3) p-1"
	>
		<button
			v-for="{ value, Icon, label } in tabs"
			:key="value"
			@click="updateAppearance(value)"
			:class="[
				'flex items-center rounded-[var(--radius-sheet)] px-3.5 py-1.5 transition-colors',
				appearance === value
					? 'bg-sheet font-medium shadow-xs'
					: 'text-muted-foreground hover:bg-sheet/60 hover:text-foreground',
			]"
		>
			<component :is="Icon" class="-ml-1 h-4 w-4" />
			<span class="ml-1.5 text-sm">{{ label }}</span>
		</button>
	</div>
</template>
