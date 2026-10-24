<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { legal } from '@/routes';

const repository = 'https://github.com/marceli-to/stackr.web';
</script>

<template>
	<footer class="border-t border-(--surface-edge)">
		<div
			class="mx-auto flex max-w-6xl flex-col gap-6 px-6 py-10 sm:flex-row sm:items-center sm:justify-between lg:px-8"
		>
			<p class="text-sm/6 text-(--color-ink-3)">
				Stackr, made with <span aria-label="love">❤️</span> by
				<a
					href="https://kimiyu.ch"
					class="font-semibold text-(--color-ink) hover:underline"
					>kimiyu.ch</a
				>
			</p>
			<nav class="flex flex-wrap gap-x-6 gap-y-2 whitespace-nowrap">
				<a
					:href="repository"
					class="text-sm/6 text-(--color-ink-3) hover:text-(--color-ink)"
					>GitHub</a
				>
				<a
					:href="`${repository}/issues`"
					class="text-sm/6 text-(--color-ink-3) hover:text-(--color-ink)"
					>Report a problem</a
				>
				<Link
					:href="legal()"
					class="text-sm/6 text-(--color-ink-3) hover:text-(--color-ink)"
					>Legal</Link
				>
			</nav>
		</div>
	</footer>
</template>
