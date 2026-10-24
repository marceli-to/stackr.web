<script setup lang="ts">
import { router, usePage } from '@inertiajs/vue3';
import {
	PhArchive,
	PhArrowCounterClockwise,
	PhArrowRight,
	PhCalendarBlank,
	PhCheckSquare,
	PhCheck,
	PhCopy,
	PhCopySimple,
	PhEye,
	PhPaperclip,
	PhPlus,
	PhRectangle,
	PhTag,
	PhTrash,
	PhUploadSimple,
	PhUser,
	PhX,
} from '@phosphor-icons/vue';
import { computed, ref, useTemplateRef, watch } from 'vue';
import AttachmentController from '@/actions/App/Http/Controllers/AttachmentController';
import CardController from '@/actions/App/Http/Controllers/CardController';
import ChecklistController from '@/actions/App/Http/Controllers/ChecklistController';
import {
	closeCard,
	dueState,
	formatDate,
	keep,
	useBoard,
} from '@/components/card/board';
import CardAttachments from '@/components/card/CardAttachments.vue';
import CardChecklist from '@/components/card/CardChecklist.vue';
import CardCoverPicker from '@/components/card/CardCoverPicker.vue';
import CardComments from '@/components/card/CardComments.vue';
import CardDatesPicker from '@/components/card/CardDatesPicker.vue';
import CardDescription from '@/components/card/CardDescription.vue';
import CardLabelsPicker from '@/components/card/CardLabelsPicker.vue';
import CardMembersPicker from '@/components/card/CardMembersPicker.vue';
import CardMoveCopy from '@/components/card/CardMoveCopy.vue';
import { Button } from '@/components/ui/button';
import {
	Dialog,
	DialogClose,
	DialogContent,
	DialogDescription,
	DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import {
	Popover,
	PopoverContent,
	PopoverTrigger,
} from '@/components/ui/popover';
import { getInitials } from '@/composables/useInitials';
import { unwatch as unwatchCard, watch as watchCard } from '@/routes/cards';
import type { CardDetail } from '@/types';

const props = defineProps<{
	card: CardDetail | null;
}>();

const { project, labels, members, checklists } = useBoard();

// The dialog closes at once and keeps showing the last card while it fades
// out; the URL catches up in the background.
const open = ref(props.card !== null);
const shown = ref<CardDetail | null>(props.card);

watch(
	() => props.card,
	(card) => {
		if (card) {
			shown.value = card;
			open.value = true;
		} else {
			open.value = false;
		}
	},
);

function close(isOpen: boolean) {
	if (!isOpen) {
		open.value = false;
		closeCard(project.value.key);
	}
}

// Focus the sheet itself on open, not the close button, so a mouse user
// doesn't land on a focus ring. Tab still starts inside the dialog.
function focusSheet(event: Event) {
	event.preventDefault();
	(event.target as HTMLElement | null)?.focus({ preventScroll: true });
}

const card = computed(() => shown.value as CardDetail);

const cardLabels = computed(() =>
	labels.value.filter((label) => card.value.labels.includes(label.id)),
);
const cardMembers = computed(() =>
	members.value.filter((member) => card.value.members.includes(member.id)),
);
const cover = computed(() =>
	card.value.attachments.find(
		(attachment) => attachment.id === card.value.cover_attachment_id,
	),
);

function update(data: Record<string, string | number | boolean | null>) {
	router.patch(CardController.update(card.value.id).url, data, keep);
}

// Title
const title = ref('');

watch(
	() => shown.value?.title,
	(value) => (title.value = value ?? ''),
	{ immediate: true },
);

function saveTitle() {
	const value = title.value.trim();

	if (!value) {
		title.value = card.value.title;
	} else if (value !== card.value.title) {
		update({ title: value });
	}
}

// Dates
const due = computed(() =>
	card.value.due_at
		? dueState(card.value.due_at, card.value.completed_at)
		: null,
);
const dueBadge = {
	complete: 'bg-(--color-success) text-white',
	overdue: 'bg-destructive text-white',
	soon: 'bg-(--color-label-yellow) text-(--color-label-ink)',
	later: 'bg-(--color-paper-2)',
};
const dueWord = {
	complete: 'Complete',
	overdue: 'Overdue',
	soon: 'Due soon',
	later: '',
};

// Checklists, optionally copying another one's items.
const checklistTitle = ref('Checklist');
const checklistOpen = ref(false);
const copyFrom = ref<number | null>(null);

watch(checklistOpen, (isOpen) => {
	if (isOpen) {
		copyFrom.value = null;
		router.reload({ only: ['checklists'] });
	}
});

watch(copyFrom, (id) => {
	const source = checklists.value?.find((checklist) => checklist.id === id);

	if (source) {
		checklistTitle.value = source.title;
	}
});

function addChecklist() {
	router.post(
		ChecklistController.store(card.value.id).url,
		{
			title: checklistTitle.value.trim() || 'Checklist',
			copy_from: copyFrom.value,
		},
		{
			...keep,
			onSuccess: () => {
				checklistOpen.value = false;
				checklistTitle.value = 'Checklist';
			},
		},
	);
}

// Attachments: the file picker, dropping files on the card, or pasting.
const fileInput = useTemplateRef('fileInput');
const uploading = ref<number | null>(null);
const dragDepth = ref(0);

function upload(files: File[]) {
	if (files.length === 0) {
		return;
	}

	uploading.value = 0;
	router.post(
		AttachmentController.store(card.value.id).url,
		{ files },
		{
			...keep,
			forceFormData: true,
			onProgress: (progress) =>
				(uploading.value = Math.round(progress?.percentage ?? 0)),
			onFinish: () => (uploading.value = null),
		},
	);
}

function picked(event: Event) {
	const input = event.target as HTMLInputElement;

	upload(Array.from(input.files ?? []));
	input.value = '';
}

const hasFiles = (event: DragEvent) =>
	event.dataTransfer?.types.includes('Files') ?? false;

function dragEnter(event: DragEvent) {
	if (hasFiles(event)) {
		dragDepth.value++;
	}
}

function dragLeave(event: DragEvent) {
	if (hasFiles(event)) {
		dragDepth.value = Math.max(0, dragDepth.value - 1);
	}
}

function drop(event: DragEvent) {
	dragDepth.value = 0;
	upload(Array.from(event.dataTransfer?.files ?? []));
}

function paste(event: ClipboardEvent) {
	const files = Array.from(event.clipboardData?.files ?? []);

	if (files.length > 0) {
		event.preventDefault();
		upload(files);
	}
}

// Archiving takes the card off the board and keeps it open; deleting is
// only offered once it's archived, and asks once, in place.
const confirmingDelete = ref(false);

// Members hear about changes anyway; watching is for everyone else.
const isMember = computed(() =>
	card.value.members.includes(usePage().props.auth.user.id),
);

function toggleWatching() {
	router.visit(
		card.value.watching
			? unwatchCard(card.value.id)
			: watchCard(card.value.id),
		keep,
	);
}

function archive() {
	router.patch(CardController.archive(card.value.id).url, {}, keep);
}

function restore() {
	router.patch(CardController.restore(card.value.id).url, {}, keep);
}

function deleteCard() {
	router.delete(CardController.destroy(card.value.id).url, {
		preserveScroll: true,
	});
}

watch(open, (isOpen) => {
	if (!isOpen) {
		confirmingDelete.value = false;
	}
});

const sideButton =
	'w-full justify-start bg-(--color-paper-2) hover:bg-(--color-paper-3)';
</script>

<template>
	<Dialog :open="open" @update:open="close">
		<DialogContent
			v-if="shown"
			:show-close-button="false"
			class="max-h-[calc(100svh-2rem)] gap-0 overflow-y-auto p-0 outline-hidden sm:max-w-4xl sm:p-0"
			@open-auto-focus="focusSheet"
			@dragenter.prevent="dragEnter"
			@dragover.prevent
			@dragleave="dragLeave"
			@drop.prevent="drop"
			@paste="paste"
		>
			<DialogTitle class="sr-only">{{ card.title }}</DialogTitle>
			<DialogDescription class="sr-only">
				Card in the list {{ card.stack.name }}
			</DialogDescription>

			<div
				v-if="cover"
				:data-field="project.color"
				class="relative flex h-48 shrink-0 justify-center field-fill"
			>
				<img
					:src="cover.thumb_url ?? cover.url"
					alt=""
					class="h-full max-w-full object-contain"
				/>
			</div>

			<div
				v-else-if="card.cover_color"
				:data-label="card.cover_color"
				class="h-20 shrink-0 bg-label"
			/>

			<DialogClose
				class="absolute top-3 right-3 flex size-9 items-center justify-center rounded-full bg-sheet/90 text-foreground hover:bg-(--color-paper-2) focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ring focus-visible:outline-solid"
				aria-label="Close"
			>
				<PhX class="size-4" />
			</DialogClose>

			<div class="p-6 sm:p-8">
				<div
					v-if="card.is_template && !card.archived"
					class="-mx-6 -mt-6 mb-6 flex items-center gap-2.5 bg-(--color-paper-2) px-6 py-3 pr-16 text-sm sm:-mx-8 sm:-mt-8 sm:px-8 sm:pr-18"
				>
					<PhCopySimple class="size-4 shrink-0" />
					<p>
						<span class="font-semibold">This is a template.</span>
						Start new cards from it with the template button next to
						“Add a card”.
					</p>
				</div>
				<div
					v-if="card.archived"
					class="-mx-6 -mt-6 mb-6 flex items-center gap-2.5 bg-(--color-paper-2) px-6 py-3 pr-16 text-sm sm:-mx-8 sm:-mt-8 sm:px-8 sm:pr-18"
				>
					<PhArchive class="size-4 shrink-0" />
					<p class="font-semibold">This card is archived.</p>
				</div>

				<header
					class="-mx-6 mb-6 border-b border-(--surface-edge) px-6 pr-16 pb-6 sm:-mx-8 sm:mb-8 sm:px-8 sm:pr-18"
				>
					<label for="card-title" class="sr-only">Title</label>
					<textarea
						id="card-title"
						v-model="title"
						name="title"
						rows="1"
						maxlength="500"
						class="-mx-1.5 block [field-sizing:content] w-[calc(100%+0.75rem)] resize-none rounded-[var(--radius-sheet)] bg-transparent px-1.5 py-0.5 font-display text-3xl leading-tight font-bold tracking-(--tracking-display) focus-field outline-hidden hover:bg-(--color-paper-2) focus-visible:bg-sheet"
						@blur="saveTitle"
						@keydown.enter.prevent="
							($event.target as HTMLElement).blur()
						"
						@keydown.esc.stop="
							title = card.title;
							($event.target as HTMLElement).blur();
						"
					/>
					<p class="mt-1 text-sm text-muted-foreground">
						in list
						<span class="font-semibold text-foreground">{{
							card.stack.name
						}}</span>
					</p>
				</header>

				<div class="grid gap-8 md:grid-cols-[minmax(0,1fr)_12.5rem]">
					<!-- Sections are split by hairlines -->
					<div
						class="min-w-0 divide-y divide-(--surface-edge) *:py-7 *:first:pt-0 *:last:pb-0"
					>
						<!-- Labels, members and dates, when the card has them -->
						<div
							v-if="
								cardLabels.length ||
								cardMembers.length ||
								card.due_at ||
								card.start_at
							"
							class="flex flex-wrap gap-x-8 gap-y-5"
						>
							<div v-if="cardMembers.length">
								<p
									class="mb-1.5 text-sm font-semibold text-muted-foreground"
								>
									Members
								</p>
								<div class="flex items-center gap-1">
									<span
										v-for="member in cardMembers"
										:key="member.id"
										class="flex size-8 items-center justify-center rounded-full bg-(--color-paper-3) text-xs font-semibold"
										:title="member.name"
										>{{ getInitials(member.name) }}</span
									>
									<CardMembersPicker :card="card">
										<Button
											variant="secondary"
											size="icon"
											class="size-8 rounded-full"
											aria-label="Change members"
										>
											<PhPlus />
										</Button>
									</CardMembersPicker>
								</div>
							</div>

							<div v-if="cardLabels.length">
								<p
									class="mb-1.5 text-sm font-semibold text-muted-foreground"
								>
									Labels
								</p>
								<div class="flex flex-wrap items-center gap-1">
									<span
										v-for="label in cardLabels"
										:key="label.id"
										:data-label="label.color"
										class="flex h-8 min-w-12 items-center rounded-[var(--radius-sheet)] bg-label px-2.5 text-sm font-semibold text-label-ink"
										>{{ label.name }}</span
									>
									<CardLabelsPicker :card="card">
										<Button
											variant="secondary"
											size="icon"
											class="size-8 rounded-[var(--radius-sheet)]"
											aria-label="Change labels"
										>
											<PhPlus />
										</Button>
									</CardLabelsPicker>
								</div>
							</div>

							<div v-if="card.due_at || card.start_at">
								<p
									class="mb-1.5 text-sm font-semibold text-muted-foreground"
								>
									{{
										card.due_at ? 'Due date' : 'Start date'
									}}
								</p>
								<div class="flex items-center gap-2">
									<input
										v-if="card.due_at"
										type="checkbox"
										:checked="card.completed_at !== null"
										aria-label="Mark complete"
										class="size-4 cursor-pointer accent-(--color-ink)"
										@change="
											update({
												completed:
													card.completed_at === null,
											})
										"
									/>
									<CardDatesPicker :card="card">
										<button
											type="button"
											class="num flex h-8 items-center gap-2 rounded-[var(--radius-sheet)] bg-(--color-paper-2) px-2.5 text-sm hover:bg-(--color-paper-3) focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ring focus-visible:outline-solid"
										>
											<span v-if="card.start_at">{{
												formatDate(card.start_at)
											}}</span>
											<span
												v-if="
													card.start_at && card.due_at
												"
												>to</span
											>
											<span v-if="card.due_at">{{
												formatDate(card.due_at, true)
											}}</span>
											<span
												v-if="due && due !== 'later'"
												class="rounded-[3px] px-1.5 py-0.5 text-xs font-semibold"
												:class="dueBadge[due]"
												>{{ dueWord[due] }}</span
											>
										</button>
									</CardDatesPicker>
								</div>
							</div>
						</div>

						<CardDescription :card="card" />

						<CardAttachments
							v-if="card.attachments.length || uploading !== null"
							:card="card"
							:uploading="uploading"
							@pick="fileInput?.click()"
						/>

						<CardChecklist
							v-for="checklist in card.checklists"
							:key="checklist.id"
							:checklist="checklist"
						/>

						<CardComments :card="card" />
					</div>

					<aside
						class="space-y-6 border-t border-(--surface-edge) pt-6 md:border-t-0 md:border-l md:pt-0 md:pl-6"
					>
						<div class="space-y-1.5">
							<p
								class="text-sm font-semibold text-muted-foreground"
							>
								Add to card
							</p>
							<CardMembersPicker :card="card">
								<Button variant="secondary" :class="sideButton">
									<PhUser />
									Members
								</Button>
							</CardMembersPicker>
							<CardLabelsPicker :card="card">
								<Button variant="secondary" :class="sideButton">
									<PhTag />
									Labels
								</Button>
							</CardLabelsPicker>
							<CardDatesPicker :card="card">
								<Button variant="secondary" :class="sideButton">
									<PhCalendarBlank />
									Dates
								</Button>
							</CardDatesPicker>
							<Popover v-model:open="checklistOpen">
								<PopoverTrigger as-child>
									<Button
										variant="secondary"
										:class="sideButton"
									>
										<PhCheckSquare />
										Checklist
									</Button>
								</PopoverTrigger>
								<PopoverContent>
									<form
										class="space-y-4"
										@submit.prevent="addChecklist"
									>
										<p class="font-semibold">
											Add a checklist
										</p>
										<div class="grid gap-2">
											<label
												for="checklist-title"
												class="text-sm font-semibold"
												>Title</label
											>
											<Input
												id="checklist-title"
												v-model="checklistTitle"
												name="title"
												maxlength="255"
												v-focus
											/>
										</div>
										<div
											v-if="checklists?.length"
											class="grid gap-2"
										>
											<label
												for="checklist-copy"
												class="text-sm font-semibold"
												>Copy items from</label
											>
											<select
												id="checklist-copy"
												v-model="copyFrom"
												class="h-9 w-full rounded-[var(--radius-sheet)] bg-sheet px-2.5 text-[0.9375rem] ring-1 focus-field ring-(--control-edge) outline-hidden ring-inset dark:bg-(--color-paper-2)"
											>
												<option :value="null">
													Nothing, start empty
												</option>
												<option
													v-for="checklist in checklists"
													:key="checklist.id"
													:value="checklist.id"
												>
													{{ checklist.title }} ({{
														checklist.card
													}},
													{{ checklist.items_count }})
												</option>
											</select>
										</div>
										<Button type="submit">Add</Button>
									</form>
								</PopoverContent>
							</Popover>
							<Button
								variant="secondary"
								:class="sideButton"
								@click="fileInput?.click()"
							>
								<PhPaperclip />
								Attachment
							</Button>
							<CardCoverPicker :card="card">
								<Button variant="secondary" :class="sideButton">
									<PhRectangle />
									Cover
								</Button>
							</CardCoverPicker>
							<input
								ref="fileInput"
								type="file"
								name="files"
								multiple
								class="sr-only"
								tabindex="-1"
								aria-label="Attach files"
								@change="picked"
							/>
						</div>

						<div
							class="space-y-1.5 border-t border-(--surface-edge) pt-6"
						>
							<p
								class="text-sm font-semibold text-muted-foreground"
							>
								Actions
							</p>
							<Button
								variant="secondary"
								:class="sideButton"
								:aria-pressed="card.watching || isMember"
								:disabled="isMember"
								:title="
									isMember
										? 'Members of a card always hear about changes'
										: undefined
								"
								@click="toggleWatching"
							>
								<PhEye />
								{{
									card.watching || isMember
										? 'Watching'
										: 'Watch'
								}}
								<PhCheck
									v-if="card.watching || isMember"
									class="ml-auto"
								/>
							</Button>
							<Button
								variant="secondary"
								:class="sideButton"
								:aria-pressed="card.is_template"
								@click="
									update({ is_template: !card.is_template })
								"
							>
								<PhCopySimple />
								{{
									card.is_template
										? 'Template'
										: 'Make template'
								}}
								<PhCheck
									v-if="card.is_template"
									class="ml-auto"
								/>
							</Button>
							<CardMoveCopy :card="card" mode="move">
								<Button variant="secondary" :class="sideButton">
									<PhArrowRight />
									Move
								</Button>
							</CardMoveCopy>
							<CardMoveCopy :card="card" mode="copy">
								<Button variant="secondary" :class="sideButton">
									<PhCopy />
									Copy
								</Button>
							</CardMoveCopy>
							<Button
								v-if="!card.archived"
								variant="secondary"
								:class="sideButton"
								@click="archive"
							>
								<PhArchive />
								Archive
							</Button>
							<template v-else-if="confirmingDelete">
								<p class="text-sm">
									Delete this card and everything on it? This
									cannot be undone.
								</p>
								<div class="flex gap-1.5">
									<Button
										variant="destructive"
										size="sm"
										@click="deleteCard"
									>
										Delete
									</Button>
									<Button
										variant="ghost"
										size="sm"
										@click="confirmingDelete = false"
									>
										Cancel
									</Button>
								</div>
							</template>
							<template v-else>
								<Button
									variant="secondary"
									:class="sideButton"
									@click="restore"
								>
									<PhArrowCounterClockwise />
									Restore
								</Button>
								<Button
									variant="secondary"
									:class="sideButton"
									@click="confirmingDelete = true"
								>
									<PhTrash />
									Delete
								</Button>
							</template>
						</div>
					</aside>
				</div>
			</div>

			<div
				v-if="dragDepth > 0"
				class="pointer-events-none fixed inset-0 z-10 flex flex-col items-center justify-center gap-2 rounded-[var(--radius-tile)] bg-sheet/90 text-lg font-semibold"
			>
				<PhUploadSimple class="size-8" />
				Drop files to attach them
			</div>
		</DialogContent>
	</Dialog>
</template>
