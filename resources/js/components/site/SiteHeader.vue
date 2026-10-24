<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import { home, login } from '@/routes';
</script>

<template>
	<header
		class="mx-auto flex max-w-6xl items-center justify-between gap-x-6 px-6 py-5 lg:px-8"
	>
		<Link
			:href="home()"
			aria-label="Homepage"
			class="flex items-center gap-2 font-display text-2xl font-bold tracking-(--tracking-display) text-(--color-ink)"
		>
			<AppLogoIcon class-name="size-6" />
			Stackr
		</Link>
		<nav class="flex items-center gap-x-8">
			<a
				href="/#how"
				class="text-sm/6 font-semibold text-(--color-ink-2) hover:text-(--color-ink) max-sm:hidden"
				>How it works</a
			>
			<a
				href="/#features"
				class="text-sm/6 font-semibold text-(--color-ink-2) hover:text-(--color-ink) max-sm:hidden"
				>Features</a
			>
			<Link
				:href="login()"
				class="rounded-full px-3.5 py-1.5 text-sm/6 font-semibold text-(--color-ink) ring-1 ring-(--control-edge) hover:bg-(--color-paper-2)"
				>Log in</Link
			>
		</nav>
	</header>
</template>
