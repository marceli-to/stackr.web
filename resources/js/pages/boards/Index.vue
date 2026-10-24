<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import {
	PhDotsThree,
	PhPencilSimple,
	PhPlus,
	PhTrash,
} from '@phosphor-icons/vue';
import { computed, ref } from 'vue';
import DeleteBoardDialog from '@/components/board/DeleteBoardDialog.vue';
import BoardFormDialog from '@/components/board/BoardFormDialog.vue';
import { Button } from '@/components/ui/button';
import {
	DropdownMenu,
	DropdownMenuContent,
	DropdownMenuItem,
	DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { index, show } from '@/routes/boards';
import { boardColors } from '@/types';
import type { BoardColor, BoardSummary } from '@/types';

defineOptions({
	layout: {
		breadcrumbs: [
			{
				title: 'Boards',
				href: index(),
			},
		],
	},
});

const props = defineProps<{
	boards: BoardSummary[];
}>();

// A new board starts on the colour the wall uses least.
const suggestedColor = computed<BoardColor>(() => {
	const uses = (color: BoardColor) =>
		props.boards.filter((board) => board.color === color).length;

	return [...boardColors].sort((a, b) => uses(a) - uses(b))[0];
});

const formOpen = ref(false);
// By id, so the dialog sees the board's latest props (e.g. a new background).
const editingId = ref<number | null>(null);
const editing = computed(
	() => props.boards.find((board) => board.id === editingId.value) ?? null,
);
const deleteOpen = ref(false);
const deleting = ref<BoardSummary | null>(null);

function create() {
	editingId.value = null;
	formOpen.value = true;
}

function edit(board: BoardSummary) {
	editingId.value = board.id;
	formOpen.value = true;
}

function remove(board: BoardSummary) {
	deleting.value = board;
	deleteOpen.value = true;
}

function plural(count: number, word: string) {
	return `${count} ${word}${count === 1 ? '' : 's'}`;
}
</script>

<template>
	<Head title="Boards" />

	<div class="flex h-full flex-1 flex-col gap-10 px-4 pt-8 pb-12 md:px-8">
		<h1
			class="font-display text-4xl leading-[1.05] font-bold tracking-(--tracking-display) md:text-[2.75rem]"
		>
			Boards
		</h1>

		<ul class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
			<li
				v-for="board in boards"
				:key="board.id"
				:data-field="board.color"
				class="relative flex aspect-[16/10] flex-col rounded-[var(--radius-tile)] field-fill bg-cover bg-center p-5 text-field-ink"
				:style="
					board.background_url
						? {
								backgroundImage: `linear-gradient(color-mix(in oklab, var(--field) 55%, transparent), color-mix(in oklab, var(--field) 85%, transparent)), url('${board.background_url}')`,
							}
						: undefined
				"
			>
				<div class="flex items-start justify-between gap-2">
					<Link
						:href="show(board.key)"
						class="min-w-0 font-display text-4xl leading-[1.15] font-bold tracking-(--tracking-display) break-words after:absolute after:inset-0 after:rounded-[var(--radius-tile)] focus-visible:outline-none focus-visible:after:outline-2 focus-visible:after:outline-offset-2 focus-visible:after:outline-(--color-focus) focus-visible:after:outline-solid"
					>
						{{ board.name }}
					</Link>

					<DropdownMenu>
						<DropdownMenuTrigger as-child>
							<Button
								variant="ghost"
								size="icon"
								class="relative z-10 -mt-1 -mr-2 size-8 text-inherit hover:bg-current/10 hover:text-inherit data-[state=open]:bg-current/10"
								:aria-label="`Actions for ${board.name}`"
							>
								<PhDotsThree />
							</Button>
						</DropdownMenuTrigger>
						<DropdownMenuContent align="end">
							<DropdownMenuItem @select="edit(board)">
								<PhPencilSimple />
								Edit
							</DropdownMenuItem>
							<DropdownMenuItem
								v-if="board.role === 'owner'"
								variant="destructive"
								@select="remove(board)"
							>
								<PhTrash />
								Delete
							</DropdownMenuItem>
						</DropdownMenuContent>
					</DropdownMenu>
				</div>

				<p
					v-if="board.description"
					class="mt-3 line-clamp-2 max-w-[40ch] opacity-90"
				>
					{{ board.description }}
				</p>

				<div
					class="mt-auto flex flex-wrap items-center gap-1.5 text-sm"
				>
					<span
						class="num rounded-[var(--radius-sheet)] bg-current/10 px-2 py-0.5"
						>{{ plural(board.stacks_count, 'list') }}</span
					>
					<span
						class="num rounded-[var(--radius-sheet)] bg-current/10 px-2 py-0.5"
						>{{ plural(board.cards_count, 'card') }}</span
					>
					<span
						v-if="board.role === 'member'"
						class="ml-auto font-semibold"
					>
						Shared with you
					</span>
				</div>
			</li>

			<!-- The wall ends with a blank tile that starts the next board. -->
			<li>
				<button
					type="button"
					class="flex aspect-[16/10] w-full flex-col justify-between rounded-[var(--radius-tile)] bg-(--color-paper-3) p-5 text-left transition-colors duration-(--dur-micro) hover:bg-(--color-rule) focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-(--color-focus) focus-visible:outline-solid"
					data-test="new-board-button"
					@click="create"
				>
					<span
						class="font-display text-4xl leading-[0.95] font-bold tracking-(--tracking-display)"
					>
						New board
					</span>
					<span
						class="flex items-center gap-2 text-sm text-muted-foreground"
					>
						<PhPlus />
						{{
							boards.length === 0
								? 'Create your first board.'
								: 'Lists and cards, in a colour of its own.'
						}}
					</span>
				</button>
			</li>
		</ul>
	</div>

	<BoardFormDialog
		v-model:open="formOpen"
		:board="editing"
		:suggested-color="suggestedColor"
	/>
	<DeleteBoardDialog v-model:open="deleteOpen" :board="deleting" />
</template>
