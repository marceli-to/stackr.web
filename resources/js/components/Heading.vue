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
					? 'text-xl font-medium tracking-tight'
					: 'font-display text-4xl leading-[1.05] font-bold tracking-(--tracking-display) md:text-[2.75rem]'
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
