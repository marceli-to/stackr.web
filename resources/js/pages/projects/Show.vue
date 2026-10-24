<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { PhLink } from '@phosphor-icons/vue';
import { computed, provide, toRef } from 'vue';
import Board from '@/components/board/Board.vue';
import BoardArchivePanel from '@/components/board/BoardArchivePanel.vue';
import BoardFilterBar from '@/components/board/BoardFilterBar.vue';
import { createFilter, filterKey } from '@/components/board/filter';
import { boardKey } from '@/components/card/board';
import CardDialog from '@/components/card/CardDialog.vue';
import type {
	BoardArchive,
	BoardStack,
	CardDetail,
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
}>();

provide(boardKey, {
	project: toRef(props, 'project'),
	labels: toRef(props, 'labels'),
	members: toRef(props, 'members'),
});

provide(filterKey, createFilter(toRef(props, 'labels')));

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
				<div class="ml-auto">
					<BoardArchivePanel :archive="archive" />
				</div>
			</div>
			<Board :project="project" :stacks="stacks" />
		</div>
	</div>

	<CardDialog :card="card" />
</template>
