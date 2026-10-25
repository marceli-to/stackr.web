<script setup lang="ts">
import {
	PhCalendarBlank,
	PhChatCircle,
	PhCheckSquare,
	PhCopySimple,
	PhEye,
	PhPaperclip,
	PhTextAlignLeft,
} from '@phosphor-icons/vue';
import { computed } from 'vue';
import {
	dueState,
	formatDate,
	openCard,
	useBoard,
} from '@/components/card/board';
import { getInitials } from '@/composables/useInitials';
import type { BoardCard } from '@/types';

const props = defineProps<{
	card: BoardCard;
}>();

const { board, labels, members } = useBoard();

const cardLabels = computed(() =>
	labels.value.filter((label) => props.card.labels.includes(label.id)),
);

const cardMembers = computed(() =>
	members.value.filter((member) => props.card.members.includes(member.id)),
);

const due = computed(() => {
	if (!props.card.due_at) {
		return null;
	}

	return {
		label: props.card.start_at
			? `${formatDate(props.card.start_at)} to ${formatDate(props.card.due_at)}`
			: formatDate(props.card.due_at),
		state: dueState(props.card.due_at, props.card.completed_at),
	};
});

const dueClasses = {
	complete: 'bg-(--color-success) px-1.5 text-white',
	overdue: 'bg-destructive px-1.5 text-white',
	soon: 'bg-(--color-label-yellow) px-1.5 text-(--color-label-ink)',
	later: 'text-muted-foreground',
};

const checklistDone = computed(
	() =>
		props.card.checklist !== null &&
		props.card.checklist.done === props.card.checklist.total,
);

const hasBadges = computed(
	() =>
		due.value !== null ||
		props.card.watching ||
		props.card.has_description ||
		props.card.comments_count > 0 ||
		props.card.attachments_count > 0 ||
		props.card.checklist !== null ||
		cardMembers.value.length > 0,
);

function open() {
	openCard(board.value.key, props.card.id);
}
</script>

<template>
	<article
		role="button"
		tabindex="0"
		:data-card-id="card.id"
		:aria-label="`Open card ${card.title}`"
		class="relative shrink-0 cursor-pointer overflow-hidden rounded-[var(--radius-sheet)] bg-sheet text-[0.9375rem] leading-snug text-foreground select-none hover:outline-2 hover:outline-current/20 hover:outline-solid focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ring focus-visible:outline-solid active:cursor-grabbing active:bg-(--color-paper-2)"
		@click="open"
		@keydown.enter.prevent="open"
	>
		<div
			v-if="card.cover_color"
			:data-label="card.cover_color"
			class="h-8 bg-label"
		/>
		<img
			v-else-if="card.cover"
			:src="card.cover.url"
			alt=""
			:width="card.cover.width ?? undefined"
			:height="card.cover.height ?? undefined"
			loading="lazy"
			draggable="false"
			class="max-h-44 w-full bg-(--color-paper-3) object-cover"
		/>

		<div class="px-3 py-2.5">
			<p
				v-if="card.is_template"
				class="mb-2 inline-flex items-center gap-1 rounded-[3px] bg-(--color-paper-2) px-1.5 py-0.5 text-xs font-medium text-muted-foreground"
			>
				<PhCopySimple class="size-3.5" />
				Template
			</p>
			<ul v-if="cardLabels.length > 0" class="mb-2 flex flex-wrap gap-1">
				<li
					v-for="label in cardLabels"
					:key="label.id"
					:data-label="label.color"
					class="rounded-[3px] bg-label text-xs leading-none font-medium text-label-ink"
					:class="label.name ? 'px-1.5 py-1' : 'h-2 w-10'"
					:title="label.name ?? undefined"
				>
					{{ label.name }}
				</li>
			</ul>

			<p class="font-medium break-words">{{ card.title }}</p>

			<div
				v-if="hasBadges"
				class="mt-2 flex flex-wrap items-center gap-x-3 gap-y-1.5 text-sm text-muted-foreground"
			>
				<span
					v-if="due"
					class="num inline-flex items-center gap-1 rounded-[3px] py-0.5"
					:class="dueClasses[due.state]"
				>
					<PhCalendarBlank weight="regular" class="size-3.5" />
					<span v-if="due.state === 'overdue'" class="sr-only"
						>Overdue:
					</span>
					<span v-if="due.state === 'complete'" class="sr-only"
						>Complete:
					</span>
					{{ due.label }}
				</span>
				<span
					v-if="card.watching"
					class="inline-flex"
					title="You're watching this card"
				>
					<PhEye weight="regular" class="size-4" />
					<span class="sr-only">Watching</span>
				</span>
				<span
					v-if="card.has_description"
					class="inline-flex"
					title="This card has a description"
				>
					<PhTextAlignLeft weight="regular" class="size-4" />
					<span class="sr-only">Has a description</span>
				</span>
				<span
					v-if="card.comments_count > 0"
					class="num inline-flex items-center gap-1"
				>
					<PhChatCircle weight="regular" class="size-4" />
					{{ card.comments_count }}
					<span class="sr-only">comments</span>
				</span>
				<span
					v-if="card.attachments_count > 0"
					class="num inline-flex items-center gap-1"
				>
					<PhPaperclip weight="regular" class="size-4" />
					{{ card.attachments_count }}
					<span class="sr-only">attachments</span>
				</span>
				<span
					v-if="card.checklist"
					class="num inline-flex items-center gap-1 rounded-[3px] py-0.5"
					:class="
						checklistDone
							? 'bg-(--color-success) px-1.5 text-white'
							: ''
					"
				>
					<PhCheckSquare weight="regular" class="size-4" />
					{{ card.checklist.done }}/{{ card.checklist.total }}
					<span class="sr-only">checklist items done</span>
				</span>

				<ul
					v-if="cardMembers.length > 0"
					class="ml-auto flex -space-x-1.5"
				>
					<li
						v-for="member in cardMembers"
						:key="member.id"
						class="flex size-6 items-center justify-center rounded-full bg-(--color-paper-3) text-[0.6875rem] font-medium text-foreground ring-2 ring-sheet"
						:title="member.name"
					>
						{{ getInitials(member.name) }}
					</li>
				</ul>
			</div>
		</div>
	</article>
</template>
