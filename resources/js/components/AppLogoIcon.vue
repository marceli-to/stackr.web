<script setup lang="ts">
import type { HTMLAttributes } from 'vue';

defineOptions({
	inheritAttrs: false,
});

type Props = {
	className?: HTMLAttributes['class'];
	// Full-strength bars, for when --logo-1 to --logo-3 give each its colour.
	colored?: boolean;
};

defineProps<Props>();
</script>

<template>
	<svg
		xmlns="http://www.w3.org/2000/svg"
		viewBox="0 0 24 24"
		aria-hidden="true"
		:class="className"
		v-bind="$attrs"
	>
		<rect
			x="3"
			y="3.5"
			width="14"
			height="4.5"
			rx="1.5"
			:style="{ fill: 'var(--logo-1, currentColor)' }"
			:opacity="colored ? 1 : 0.5"
		/>
		<rect
			x="5"
			y="9.75"
			width="14"
			height="4.5"
			rx="1.5"
			:style="{ fill: 'var(--logo-2, currentColor)' }"
			:opacity="colored ? 1 : 0.75"
		/>
		<rect
			x="7"
			y="16"
			width="14"
			height="4.5"
			rx="1.5"
			:style="{ fill: 'var(--logo-3, currentColor)' }"
		/>
	</svg>
</template>
