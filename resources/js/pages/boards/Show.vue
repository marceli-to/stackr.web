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
import BoardLists from '@/components/board/BoardLists.vue';
import BoardArchivePanel from '@/components/board/BoardArchivePanel.vue';
import BoardCalendar from '@/components/board/BoardCalendar.vue';
import BoardFilterBar from '@/components/board/BoardFilterBar.vue';
import BoardInfo from '@/components/board/BoardInfo.vue';
import BoardShortcuts from '@/components/board/BoardShortcuts.vue';
import { createFilter, filterKey } from '@/components/board/filter';
import { boardKey } from '@/components/card/board';
import AppMenu from '@/components/AppMenu.vue';
import { unwatch as unwatchBoard, watch as watchBoard } from '@/routes/boards';
import CardDialog from '@/components/card/CardDialog.vue';
import { membersOpen } from '@/components/board/members';
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
	Board,
} from '@/types';

const props = defineProps<{
	board: Board;
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
	board: toRef(props, 'board'),
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
			? unwatchBoard(props.board.key)
			: watchBoard(props.board.key),
		{ preserveScroll: true, preserveState: true },
	);
}

// The rarer board actions live in one menu; these are its dialogs.
const archiveOpen = ref(false);
const shortcutsOpen = ref(false);
const infoOpen = ref(false);

// Board or calendar, remembered per board.
const view = useStorage<'board' | 'calendar'>(
	`stackr.view.${props.board.id}`,
	'board',
);
</script>

<template>
	<Head :title="props.board.name" />

	<div class="flex h-full min-h-0 flex-1 flex-col gap-8 px-3 pt-8 md:px-5">
		<h1 class="sr-only">{{ board.name }}</h1>

		<div class="flex min-h-0 flex-1 flex-col">
			<BoardCalendar v-if="view === 'calendar'" :stacks="stacks" />
			<BoardLists v-else :board="board" :stacks="stacks" />
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
				v-if="board.description || board.url"
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
	<BoardInfo v-model:open="infoOpen" :board="board" />
	<BoardShortcuts v-model:open="shortcutsOpen" />
	<CardDialog :card="card" />
</template>
