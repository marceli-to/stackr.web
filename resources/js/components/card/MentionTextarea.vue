<script setup lang="ts">
import { computed, nextTick, onMounted, ref, useTemplateRef } from 'vue';
import { useBoard } from '@/components/card/board';
import { getInitials } from '@/composables/useInitials';
import type { Member } from '@/types';

// A textarea that suggests board members after "@" and inserts
// "@Full Name", which the server reads as a mention.
defineOptions({ inheritAttrs: false });

const props = defineProps<{
	/** Focus the field when it appears. */
	autofocus?: boolean;
}>();

const value = defineModel<string>({ required: true });

const { members } = useBoard();
const field = useTemplateRef('field');

// The "@…" being typed right before the caret, or null.
const query = ref<string | null>(null);
const active = ref(0);

const suggestions = computed<Member[]>(() => {
	if (query.value === null) {
		return [];
	}

	const typed = query.value.toLowerCase();

	return members.value
		.filter((member) =>
			member.name
				.toLowerCase()
				.split(/\s+/)
				.concat(member.name.toLowerCase())
				.some((part) => part.startsWith(typed)),
		)
		.slice(0, 6);
});

function look() {
	const element = field.value;

	if (!element || element.selectionStart !== element.selectionEnd) {
		query.value = null;

		return;
	}

	const before = element.value.slice(0, element.selectionStart);
	const match = before.match(/(?:^|\s)@([^@\n]{0,30})$/);

	query.value = match ? match[1] : null;
	active.value = 0;
}

async function pick(member: Member) {
	const element = field.value;

	if (!element || query.value === null) {
		return;
	}

	const caret = element.selectionStart;
	const start = caret - query.value.length - 1;
	const text = `@${member.name} `;

	value.value =
		element.value.slice(0, start) + text + element.value.slice(caret);
	query.value = null;

	await nextTick();
	element.focus();
	element.setSelectionRange(start + text.length, start + text.length);
}

function keydown(event: KeyboardEvent) {
	if (suggestions.value.length === 0) {
		return;
	}

	const count = suggestions.value.length;

	if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
		event.preventDefault();
		active.value =
			(active.value + (event.key === 'ArrowDown' ? 1 : count - 1)) %
			count;
	} else if (
		(event.key === 'Enter' && !event.metaKey && !event.ctrlKey) ||
		event.key === 'Tab'
	) {
		event.preventDefault();
		event.stopImmediatePropagation();
		pick(suggestions.value[active.value]);
	} else if (event.key === 'Escape') {
		// Close the list, not the comment editor or the card.
		event.preventDefault();
		event.stopImmediatePropagation();
		event.stopPropagation();
		query.value = null;
	}
}

onMounted(() => {
	if (props.autofocus) {
		field.value?.focus();
	}
});
</script>

<template>
	<div class="relative">
		<textarea
			ref="field"
			v-model="value"
			role="combobox"
			aria-autocomplete="list"
			:aria-expanded="suggestions.length > 0"
			aria-controls="mention-list"
			@keydown="keydown"
			v-bind="$attrs"
			@input="look"
			@click="look"
			@keyup.left="look"
			@keyup.right="look"
			@blur="query = null"
		/>
		<ul
			v-if="suggestions.length"
			id="mention-list"
			role="listbox"
			class="absolute top-full left-0 z-10 mt-1 w-64 rounded-[var(--radius-tile)] bg-(--menu-bg) p-1 text-(--menu-ink) shadow-(--menu-shadow)"
		>
			<li
				v-for="(member, index) in suggestions"
				:key="member.id"
				role="option"
				:aria-selected="index === active"
				class="flex cursor-pointer items-center gap-2 rounded-[var(--radius-sheet)] px-2 py-1.5 text-sm"
				:class="index === active ? 'bg-(--menu-hover)' : ''"
				@mousedown.prevent="pick(member)"
				@mouseenter="active = index"
			>
				<span
					class="flex size-6 shrink-0 items-center justify-center rounded-full bg-(--color-paper-3) text-[0.6875rem] font-medium"
					aria-hidden="true"
					>{{ getInitials(member.name) }}</span
				>
				<span class="truncate">{{ member.name }}</span>
			</li>
		</ul>
	</div>
</template>
