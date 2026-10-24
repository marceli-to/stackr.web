<script setup lang="ts">
import { PhCheck, PhCopy } from '@phosphor-icons/vue';
import { useClipboard } from '@vueuse/core';

const props = defineProps<{
	code: string;
	label?: string;
	/** Wrap long lines, for prose rather than commands. */
	wrap?: boolean;
}>();

const { copy, copied } = useClipboard({ copiedDuring: 1500 });
</script>

<template>
	<div class="space-y-1.5">
		<p v-if="label" class="text-sm text-muted-foreground">{{ label }}</p>
		<!-- The button gets its own column, so long lines scroll beside it, never under it. -->
		<div
			class="flex items-start gap-1 rounded-[var(--radius-sheet)] bg-(--color-paper-3)"
		>
			<pre
				class="min-w-0 flex-1 py-3 pl-3.5 font-mono text-[13px] leading-relaxed"
				:class="props.wrap ? 'whitespace-pre-wrap' : 'overflow-x-auto'"
			><code>{{ props.code }}</code></pre>
			<button
				type="button"
				class="m-2 flex size-7 shrink-0 items-center justify-center rounded-full text-muted-foreground transition-colors hover:bg-foreground/5 hover:text-foreground"
				:aria-label="copied ? 'Copied' : 'Copy'"
				@click="copy(props.code)"
			>
				<PhCheck v-if="copied" class="size-4 text-(--color-success)" />
				<PhCopy v-else class="size-4" />
			</button>
		</div>
	</div>
</template>
