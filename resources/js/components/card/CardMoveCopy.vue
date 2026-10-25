<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import CardController from '@/actions/App/Http/Controllers/CardController';
import { keep, useBoard } from '@/components/card/board';
import { Button } from '@/components/ui/button';
import {
	Popover,
	PopoverContent,
	PopoverTrigger,
} from '@/components/ui/popover';
import { Spinner } from '@/components/ui/spinner';
import type { CardDetail } from '@/types';

const props = defineProps<{
	card: CardDetail;
	mode: 'move' | 'copy';
}>();

const { board, destinations } = useBoard();

const open = ref(false);
const boardId = ref(board.value.id);
const stackId = ref<number | null>(null);
const position = ref(0);
const title = ref('');
const kept = ref<string[]>([]);
const processing = ref(false);

const extras = computed(() =>
	[
		{
			value: 'checklists',
			label: 'Checklists',
			has: props.card.checklists.length > 0,
		},
		{ value: 'labels', label: 'Labels', has: props.card.labels.length > 0 },
		{
			value: 'members',
			label: 'Members',
			has: props.card.members.length > 0,
		},
		{
			value: 'attachments',
			label: 'Attachments',
			has: props.card.attachments.length > 0,
		},
	].filter((extra) => extra.has),
);

// The user's boards load when the popover opens, so the counts are fresh.
watch(open, (isOpen) => {
	if (!isOpen) {
		return;
	}

	boardId.value = board.value.id;
	stackId.value = props.card.stack.id;
	title.value = props.card.title;
	kept.value = extras.value.map((extra) => extra.value);
	placeDefault();
	router.reload({ only: ['destinations'], onSuccess: placeDefault });
});

const target = computed(() =>
	destinations.value?.find((destination) => destination.id === boardId.value),
);
const stack = computed(() =>
	target.value?.stacks.find((stack) => stack.id === stackId.value),
);

// The card's own list counts it already, unless it's archived.
const inOwnList = computed(
	() => stackId.value === props.card.stack.id && props.card.index !== null,
);
const positions = computed(() => {
	const count = stack.value?.cards_count ?? 0;

	return props.mode === 'move' && inOwnList.value ? count : count + 1;
});

// Moving stays put in its own list; anywhere else goes to the bottom. A copy
// in its own list lands right under the card.
function placeDefault() {
	if (inOwnList.value && props.card.index !== null) {
		position.value =
			props.mode === 'move' ? props.card.index : props.card.index + 1;
	} else {
		position.value = Math.max(0, positions.value - 1);
	}
}

function pickBoard() {
	const stacks = target.value?.stacks ?? [];

	stackId.value =
		stacks.find((stack) => stack.id === props.card.stack.id)?.id ??
		stacks[0]?.id ??
		null;
	placeDefault();
}

function submit() {
	if (stackId.value === null) {
		return;
	}

	const options = {
		...keep,
		onStart: () => (processing.value = true),
		onFinish: () => (processing.value = false),
		onSuccess: () => (open.value = false),
	};

	if (props.mode === 'move') {
		router.patch(
			CardController.move(props.card.id).url,
			{ stack_id: stackId.value, index: position.value },
			options,
		);
	} else {
		router.post(
			CardController.copy(props.card.id).url,
			{
				stack_id: stackId.value,
				index: position.value,
				title: title.value.trim() || props.card.title,
				keep: kept.value,
			},
			options,
		);
	}
}

const field =
	'h-9 w-full rounded-[var(--radius-sheet)] bg-sheet px-2.5 text-[0.9375rem] ring-1 ring-(--control-edge) outline-hidden ring-inset focus-field disabled:opacity-55 dark:bg-(--color-paper-2)';
</script>

<template>
	<Popover v-model:open="open">
		<PopoverTrigger as-child>
			<slot />
		</PopoverTrigger>
		<PopoverContent class="w-80">
			<form class="space-y-4" @submit.prevent="submit">
				<p class="font-medium">
					{{ mode === 'move' ? 'Move card' : 'Copy card' }}
				</p>

				<template v-if="mode === 'copy'">
					<div class="grid gap-2">
						<label for="copy-title" class="text-sm font-medium"
							>Title</label
						>
						<textarea
							id="copy-title"
							v-model="title"
							rows="2"
							maxlength="500"
							:class="[field, 'h-auto resize-none py-1.5']"
						/>
					</div>
					<fieldset v-if="extras.length" class="grid gap-1.5">
						<legend class="mb-2 text-sm font-medium">Keep</legend>
						<label
							v-for="extra in extras"
							:key="extra.value"
							class="flex items-center gap-2 text-sm"
						>
							<input
								v-model="kept"
								type="checkbox"
								:value="extra.value"
								class="size-4 cursor-pointer accent-(--color-ink)"
							/>
							{{ extra.label }}
						</label>
					</fieldset>
				</template>

				<div v-if="!destinations" class="flex justify-center py-4">
					<Spinner />
				</div>
				<template v-else>
					<div class="grid gap-2">
						<label for="move-board" class="text-sm font-medium"
							>Board</label
						>
						<select
							id="move-board"
							v-model="boardId"
							:class="field"
							@change="pickBoard"
						>
							<option
								v-for="destination in destinations"
								:key="destination.id"
								:value="destination.id"
							>
								{{ destination.name
								}}{{
									destination.id === board.id
										? ' (this board)'
										: ''
								}}
							</option>
						</select>
					</div>
					<div class="grid grid-cols-[minmax(0,1fr)_5.5rem] gap-2">
						<div class="grid gap-2">
							<label for="move-list" class="text-sm font-medium"
								>List</label
							>
							<select
								id="move-list"
								v-model="stackId"
								:class="field"
								:disabled="!target?.stacks.length"
								@change="placeDefault"
							>
								<option
									v-for="option in target?.stacks"
									:key="option.id"
									:value="option.id"
								>
									{{ option.name }}
								</option>
							</select>
						</div>
						<div class="grid gap-2">
							<label
								for="move-position"
								class="text-sm font-medium"
								>Position</label
							>
							<select
								id="move-position"
								v-model="position"
								class="num"
								:class="field"
								:disabled="stackId === null"
							>
								<option
									v-for="index in positions"
									:key="index"
									:value="index - 1"
								>
									{{ index }}
								</option>
							</select>
						</div>
					</div>
					<p
						v-if="target && target.stacks.length === 0"
						class="text-sm text-muted-foreground"
					>
						This board has no lists yet.
					</p>
				</template>

				<Button
					type="submit"
					:disabled="processing || stackId === null || !destinations"
				>
					{{ mode === 'move' ? 'Move' : 'Copy' }}
				</Button>
			</form>
		</PopoverContent>
	</Popover>
</template>
