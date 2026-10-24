<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { PhCalendarBlank, PhKanban, PhLink } from '@phosphor-icons/vue';
import { useStorage } from '@vueuse/core';
import { computed, provide, toRef } from 'vue';
import Board from '@/components/board/Board.vue';
import BoardArchivePanel from '@/components/board/BoardArchivePanel.vue';
import BoardCalendar from '@/components/board/BoardCalendar.vue';
import BoardFilterBar from '@/components/board/BoardFilterBar.vue';
import { createFilter, filterKey } from '@/components/board/filter';
import { boardKey } from '@/components/card/board';
import CardDialog from '@/components/card/CardDialog.vue';
import type {
	BoardArchive,
	BoardChecklist,
	BoardStack,
	CardDetail,
	Destination,
	Label,
	Member,
	Project,
} from '@/types';

const props = defineProps<{
	project: Project;
	stacks: BoardStack[];
	labels: Label[];
	members: Member[];
	card: CardDetail | null;
	archive?: BoardArchive;
	destinations?: Destination[];
	checklists?: BoardChecklist[];
}>();

provide(boardKey, {
	project: toRef(props, 'project'),
	labels: toRef(props, 'labels'),
	members: toRef(props, 'members'),
	destinations: toRef(props, 'destinations'),
	checklists: toRef(props, 'checklists'),
	templates: computed(() =>
		props.stacks
			.flatMap((stack) => stack.cards)
			.filter((card) => card.is_template)
			.map(({ id, title }) => ({ id, title })),
	),
});

provide(filterKey, createFilter(toRef(props, 'labels')));

// Board or calendar, remembered per project.
const view = useStorage<'board' | 'calendar'>(
	`stackr.view.${props.project.id}`,
	'board',
);

// The link as people read it: no scheme, no trailing slash.
const urlLabel = computed(() =>
	props.project.url?.replace(/^https?:\/\//, '').replace(/\/$/, ''),
);
</script>

<template>
	<Head :title="props.project.name" />

	<div class="flex h-full min-h-0 flex-1 flex-col gap-8 px-4 pt-8 md:px-8">
		<h1 class="sr-only">{{ project.name }}</h1>

		<header v-if="project.description || project.url" class="space-y-3">
			<p v-if="project.description" class="max-w-[60ch] text-lg">
				{{ project.description }}
			</p>
			<a
				v-if="project.url"
				:href="project.url"
				target="_blank"
				rel="noopener noreferrer"
				class="inline-flex max-w-full items-center gap-1.5 rounded-sm underline decoration-current/40 underline-offset-4 hover:decoration-current focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ring focus-visible:outline-solid"
			>
				<PhLink class="size-4 shrink-0" />
				<span class="truncate">{{ urlLabel }}</span>
			</a>
		</header>

		<div class="flex min-h-0 flex-1 flex-col gap-4">
			<div class="flex flex-wrap items-center gap-2">
				<BoardFilterBar :stacks="stacks" />
				<div class="ml-auto flex items-center gap-2">
					<div
						class="flex rounded-[var(--radius-sheet)] bg-current/10 p-0.5"
						role="group"
						aria-label="View"
					>
						<button
							v-for="option in [
								{
									value: 'board',
									label: 'Board',
									icon: PhKanban,
								},
								{
									value: 'calendar',
									label: 'Calendar',
									icon: PhCalendarBlank,
								},
							] as const"
							:key="option.value"
							type="button"
							class="flex h-8 items-center gap-1.5 rounded-[3px] px-2.5 text-sm font-semibold transition-colors duration-(--dur-micro) focus-visible:outline-2 focus-visible:outline-ring focus-visible:outline-solid"
							:class="
								view === option.value
									? 'bg-field-ink text-field'
									: 'opacity-80 hover:opacity-100'
							"
							:aria-pressed="view === option.value"
							@click="view = option.value"
						>
							<component :is="option.icon" class="size-4" />
							{{ option.label }}
						</button>
					</div>
					<BoardArchivePanel :archive="archive" />
				</div>
			</div>
			<BoardCalendar v-if="view === 'calendar'" :stacks="stacks" />
			<Board v-else :project="project" :stacks="stacks" />
		</div>
	</div>

	<CardDialog :card="card" />
</template>
