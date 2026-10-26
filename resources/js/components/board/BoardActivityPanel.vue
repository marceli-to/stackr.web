<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import {
	describeActivity,
	formatDate,
	openCard,
	useBoard,
} from '@/components/card/board';
import { Button } from '@/components/ui/button';
import {
	Dialog,
	DialogContent,
	DialogDescription,
	DialogHeader,
	DialogTitle,
} from '@/components/ui/dialog';
import { Spinner } from '@/components/ui/spinner';
import { getInitials } from '@/composables/useInitials';
import type { BoardFeed, FeedEntry } from '@/types';

const props = defineProps<{
	feed?: BoardFeed;
}>();

const { board } = useBoard();

// Opened from the board menu.
const open = defineModel<boolean>('open', { default: false });

const pageSize = 50;
const limit = ref(pageSize);
const loading = ref(false);

// The feed isn't part of the board's props; it loads when the panel opens,
// and "Show more" asks for a longer one (in a header, so the URL stays put).
function load() {
	loading.value = true;
	router.reload({
		only: ['feed'],
		headers: { 'X-Feed-Limit': String(limit.value) },
		onFinish: () => (loading.value = false),
	});
}

watch(open, (isOpen) => {
	if (isOpen) {
		limit.value = pageSize;
		load();
	}
});

function more() {
	limit.value += pageSize;
	load();
}

function show(id: number) {
	open.value = false;
	openCard(board.value.key, id);
}

const time = new Intl.DateTimeFormat(undefined, { timeStyle: 'short' });

function dayOf(iso: string): string {
	const date = new Date(iso);
	const today = new Date();
	const yesterday = new Date();
	yesterday.setDate(today.getDate() - 1);

	if (date.toDateString() === today.toDateString()) {
		return 'Today';
	}

	if (date.toDateString() === yesterday.toDateString()) {
		return 'Yesterday';
	}

	return formatDate(iso);
}

// Entries under a heading per day, newest first.
const days = computed(() => {
	const groups: { day: string; entries: FeedEntry[] }[] = [];

	for (const entry of props.feed?.entries ?? []) {
		const day = dayOf(entry.created_at);

		if (groups.at(-1)?.day !== day) {
			groups.push({ day, entries: [] });
		}

		groups.at(-1)!.entries.push(entry);
	}

	return groups;
});
</script>

<template>
	<Dialog v-model:open="open">
		<DialogContent class="sm:max-w-lg">
			<DialogHeader>
				<DialogTitle>Activity</DialogTitle>
				<DialogDescription>
					What happened on this board’s cards, newest first.
				</DialogDescription>
			</DialogHeader>

			<div v-if="!feed" class="flex justify-center py-8">
				<Spinner />
			</div>

			<div v-else class="-mx-2 max-h-[60svh] overflow-y-auto">
				<p
					v-if="feed.entries.length === 0"
					class="px-2 py-6 text-center text-sm text-muted-foreground"
				>
					Nothing has happened here yet.
				</p>

				<section
					v-for="group in days"
					:key="group.day"
					:aria-label="group.day"
				>
					<h3
						class="sticky top-0 z-10 bg-sheet px-2 pt-3 pb-1 text-sm font-medium text-muted-foreground"
					>
						{{ group.day }}
					</h3>
					<ol>
						<li v-for="entry in group.entries" :key="entry.key">
							<button
								type="button"
								class="flex w-full gap-2.5 rounded-[var(--radius-sheet)] px-2 py-2 text-left hover:bg-(--color-paper-2) focus-visible:outline-2 focus-visible:outline-ring focus-visible:outline-solid"
								@click="show(entry.card.id)"
							>
								<span
									class="flex size-7 shrink-0 items-center justify-center rounded-full bg-(--color-paper-3) text-xs font-medium"
									aria-hidden="true"
									>{{
										getInitials(entry.user?.name ?? '?')
									}}</span
								>
								<span class="min-w-0 flex-1">
									<span class="flex items-baseline gap-2">
										<span class="truncate font-medium">{{
											entry.card.title
										}}</span>
										<span
											v-if="entry.card.archived"
											class="shrink-0 text-sm text-muted-foreground/70"
											>archived</span
										>
									</span>
									<span
										class="block text-sm text-muted-foreground"
									>
										<span class="text-foreground">{{
											entry.user?.name ?? 'Former member'
										}}</span>
										{{
											entry.kind === 'comment'
												? 'commented'
												: describeActivity(
														{
															type:
																entry.type ??
																'',
															data: entry.data,
															user: entry.user,
														},
														'it',
													)
										}}
										<time
											:datetime="entry.created_at"
											class="whitespace-nowrap"
										>
											·
											{{
												time.format(
													new Date(entry.created_at),
												)
											}}</time
										>
										<span
											v-if="entry.via_agent"
											class="whitespace-nowrap text-muted-foreground/70"
										>
											· via agent</span
										>
									</span>
									<span
										v-if="entry.snippet"
										class="mt-1 line-clamp-2 block rounded-[var(--radius-sheet)] bg-(--color-paper-2) px-2.5 py-1.5 text-sm"
										>{{ entry.snippet }}</span
									>
								</span>
							</button>
						</li>
					</ol>
				</section>

				<div v-if="feed.more" class="flex justify-center px-2 py-3">
					<Button
						variant="ghost"
						size="sm"
						:disabled="loading"
						@click="more"
					>
						<Spinner v-if="loading" />
						Show more
					</Button>
				</div>
			</div>
		</DialogContent>
	</Dialog>
</template>
