<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import { home } from '@/routes';

defineProps<{
	title?: string;
	description?: string;
}>();

const name = usePage().props.name;
</script>

<template>
	<div
		class="flex min-h-svh flex-col items-center justify-center bg-background px-4 py-12"
	>
		<div
			class="w-full max-w-sm rounded-[var(--radius-tile)] bg-sheet p-8 shadow-(--float-shadow) ring-1 ring-(--surface-edge) sm:p-10"
		>
			<Link
				:href="home()"
				class="mx-auto flex w-fit items-center gap-2 rounded-sm"
				:aria-label="`${name} home`"
			>
				<AppLogoIcon class="size-7 fill-current" />
				<span class="font-display text-3xl leading-none font-bold">
					{{ name }}
				</span>
			</Link>

			<header class="mt-8 mb-8 space-y-2 text-center">
				<h1 class="text-xl font-medium tracking-tight">
					{{ title }}
				</h1>
				<p
					v-if="description"
					class="leading-snug text-balance text-muted-foreground"
				>
					{{ description }}
				</p>
			</header>

			<slot />
		</div>
	</div>
</template>
