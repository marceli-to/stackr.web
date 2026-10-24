<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import {
	PhArchive,
	PhCalendarBlank,
	PhCheck,
	PhEye,
	PhInfo,
	PhKanban,
	PhKeyboard,
	PhUserPlus,
} from '@phosphor-icons/vue';
import { useStorage } from '@vueuse/core';
import { computed, provide, ref, toRef } from 'vue';
import Board from '@/components/board/Board.vue';
import BoardArchivePanel from '@/components/board/BoardArchivePanel.vue';
import BoardCalendar from '@/components/board/BoardCalendar.vue';
import BoardFilterBar from '@/components/board/BoardFilterBar.vue';
import BoardInfo from '@/components/board/BoardInfo.vue';
import BoardShortcuts from '@/components/board/BoardShortcuts.vue';
import { createFilter, filterKey } from '@/components/board/filter';
import { boardKey } from '@/components/card/board';
import AppMenu from '@/components/AppMenu.vue';
import {
	unwatch as unwatchProject,
	watch as watchProject,
} from '@/routes/projects';
import CardDialog from '@/components/card/CardDialog.vue';
import { membersOpen } from '@/components/projects/members';
import {
	DropdownMenuItem,
	DropdownMenuSeparator,
	DropdownMenuShortcut,
} from '@/components/ui/dropdown-menu';
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
	watching: boolean;
	canManageMembers: boolean;
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

// Watching the board brings every card's updates to the bell.
function toggleWatching() {
	router.visit(
		props.watching
			? unwatchProject(props.project.key)
			: watchProject(props.project.key),
		{ preserveScroll: true, preserveState: true },
	);
}

// The rarer board actions live in one menu; these are its dialogs.
const archiveOpen = ref(false);
const shortcutsOpen = ref(false);
const infoOpen = ref(false);

// Board or calendar, remembered per project.
const view = useStorage<'board' | 'calendar'>(
	`stackr.view.${props.project.id}`,
	'board',
);
</script>

<template>
	<Head :title="props.project.name" />

	<div class="flex h-full min-h-0 flex-1 flex-col gap-8 px-4 pt-8 md:px-8">
		<h1 class="sr-only">{{ project.name }}</h1>

		<div class="flex min-h-0 flex-1 flex-col">
			<BoardCalendar v-if="view === 'calendar'" :stacks="stacks" />
			<Board v-else :project="project" :stacks="stacks" />
		</div>
	</div>

	<Teleport defer to="#topbar-search">
		<BoardFilterBar :stacks="stacks" />
	</Teleport>

	<Teleport defer to="#topbar-actions">
		<AppMenu>
			<DropdownMenuItem
				v-for="option in [
					{ value: 'board', label: 'Board', icon: PhKanban },
					{
						value: 'calendar',
						label: 'Calendar',
						icon: PhCalendarBlank,
					},
				] as const"
				:key="option.value"
				@select="view = option.value"
			>
				<component :is="option.icon" />
				{{ option.label }}
				<PhCheck v-if="view === option.value" class="ml-auto" />
			</DropdownMenuItem>
			<DropdownMenuSeparator />
			<DropdownMenuItem
				:title="
					watching
						? 'You get updates about every card on this board'
						: 'Get updates about every card on this board'
				"
				@select="toggleWatching"
			>
				<PhEye />
				{{ watching ? 'Stop watching' : 'Watch board' }}
			</DropdownMenuItem>
			<DropdownMenuItem @select="membersOpen = true">
				<PhUserPlus />
				{{ canManageMembers ? 'Invite people…' : 'Members…' }}
			</DropdownMenuItem>
			<DropdownMenuItem @select="archiveOpen = true">
				<PhArchive />
				Archive…
			</DropdownMenuItem>
			<DropdownMenuSeparator />
			<DropdownMenuItem
				v-if="project.description || project.url"
				@select="infoOpen = true"
			>
				<PhInfo />
				Info
			</DropdownMenuItem>
			<DropdownMenuItem @select="shortcutsOpen = true">
				<PhKeyboard />
				Keyboard shortcuts
				<DropdownMenuShortcut>?</DropdownMenuShortcut>
			</DropdownMenuItem>
		</AppMenu>
	</Teleport>

	<BoardArchivePanel v-model:open="archiveOpen" :archive="archive" />
	<BoardInfo v-model:open="infoOpen" :project="project" />
	<BoardShortcuts v-model:open="shortcutsOpen" />
	<CardDialog :card="card" />
</template>
