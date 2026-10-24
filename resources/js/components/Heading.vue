<script setup lang="ts">
type Props = {
	title: string;
	description?: string;
	variant?: 'default' | 'small';
};

withDefaults(defineProps<Props>(), {
	variant: 'default',
});
</script>

<template>
	<header :class="variant === 'small' ? 'space-y-1' : 'mb-10 space-y-4'">
		<h2
			:class="
				variant === 'small'
					? 'text-xl font-semibold tracking-tight'
					: 'font-display text-(length:--text-poster) leading-[0.95] font-bold tracking-(--tracking-display)'
			"
		>
			{{ title }}
		</h2>
		<p
			v-if="description"
			:class="
				variant === 'small'
					? 'text-muted-foreground'
					: 'text-lg text-muted-foreground'
			"
		>
			{{ description }}
		</p>
	</header>
</template>
