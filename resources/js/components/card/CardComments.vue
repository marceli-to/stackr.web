<script setup lang="ts">
import { router, usePage } from '@inertiajs/vue3';
import { PhChatCircle } from '@phosphor-icons/vue';
import { useStorage } from '@vueuse/core';
import { computed, ref } from 'vue';
import CommentController from '@/actions/App/Http/Controllers/CommentController';
import { describeActivity, keep, timeAgo } from '@/components/card/board';
import { Button } from '@/components/ui/button';
import { getInitials } from '@/composables/useInitials';
import type { CardActivity, CardComment, CardDetail } from '@/types';

const props = defineProps<{
	card: CardDetail;
}>();

const me = computed(() => usePage().props.auth.user);

// Comments and activity in one timeline, newest first. The activity can be
// hidden; the choice is remembered across cards.
const details = useStorage('stackr.card-activity', true);

type Entry =
	| { kind: 'comment'; key: string; at: string; comment: CardComment }
	| { kind: 'activity'; key: string; at: string; activity: CardActivity };

const timeline = computed<Entry[]>(() => {
	const entries: Entry[] = props.card.comments.map((comment) => ({
		kind: 'comment',
		key: `c${comment.id}`,
		at: comment.created_at,
		comment,
	}));

	if (details.value) {
		for (const activity of props.card.activities) {
			entries.push({
				kind: 'activity',
				key: `a${activity.id}`,
				at: activity.created_at,
				activity,
			});
		}
	}

	return entries.sort((a, b) => b.at.localeCompare(a.at));
});

const body = ref('');
const writing = ref(false);
const editing = ref<number | null>(null);
const editBody = ref('');

const fieldClass =
	'focus-field block w-full resize-y rounded-[var(--radius-sheet)] bg-sheet px-3 py-2 text-base shadow-xs ring-1 ring-(--control-edge) outline-hidden ring-inset placeholder:text-muted-foreground sm:text-[0.9375rem] dark:bg-(--color-paper-2)';

function post() {
	if (!body.value.trim()) {
		return;
	}

	router.post(
		CommentController.store(props.card.id).url,
		{ body: body.value.trim() },
		{
			...keep,
			onSuccess: () => {
				body.value = '';
				writing.value = false;
			},
		},
	);
}

function edit(comment: CardComment) {
	editing.value = comment.id;
	editBody.value = comment.body;
}

function save(comment: CardComment) {
	router.patch(
		CommentController.update(comment.id).url,
		{ body: editBody.value.trim() },
		{ ...keep, onSuccess: () => (editing.value = null) },
	);
}

function remove(comment: CardComment) {
	router.delete(CommentController.destroy(comment.id).url, keep);
}
</script>

<template>
	<section aria-labelledby="card-comments">
		<div class="mb-3 flex items-center gap-2.5">
			<PhChatCircle class="size-5" />
			<h3 id="card-comments" class="text-base font-semibold">
				Comments and activity
			</h3>
			<Button
				v-if="card.activities.length"
				variant="ghost"
				size="sm"
				class="ml-auto text-muted-foreground"
				:aria-pressed="details"
				@click="details = !details"
			>
				{{ details ? 'Hide details' : 'Show details' }}
			</Button>
		</div>

		<form class="flex gap-2.5" @submit.prevent="post">
			<span
				class="flex size-7 shrink-0 items-center justify-center rounded-full bg-(--color-paper-3) text-xs font-semibold"
				aria-hidden="true"
				>{{ getInitials(me.name) }}</span
			>
			<div class="min-w-0 flex-1 space-y-2">
				<textarea
					v-model="body"
					name="body"
					aria-label="Write a comment"
					:rows="writing ? 3 : 1"
					maxlength="10000"
					placeholder="Write a comment…"
					:class="fieldClass"
					@focus="writing = true"
					@keydown.meta.enter.prevent="post"
					@keydown.ctrl.enter.prevent="post"
				/>
				<div v-if="writing" class="flex gap-2">
					<Button type="submit" size="sm" :disabled="!body.trim()">
						Save
					</Button>
					<Button
						type="button"
						variant="ghost"
						size="sm"
						@click="
							writing = false;
							body = '';
						"
					>
						Cancel
					</Button>
				</div>
			</div>
		</form>

		<ol class="mt-5 space-y-5">
			<template v-for="entry in timeline" :key="entry.key">
				<li
					v-if="entry.kind === 'activity'"
					class="flex gap-2.5 text-sm"
				>
					<span
						class="flex size-7 shrink-0 items-center justify-center rounded-full bg-(--color-paper-2) text-xs font-semibold text-muted-foreground"
						aria-hidden="true"
						>{{
							getInitials(entry.activity.user?.name ?? '?')
						}}</span
					>
					<p class="min-w-0 flex-1 pt-1">
						<span class="font-semibold">{{
							entry.activity.user?.name ?? 'Former member'
						}}</span>
						{{ describeActivity(entry.activity) }}
						<time
							:datetime="entry.activity.created_at"
							class="ml-1 whitespace-nowrap text-muted-foreground"
							>{{ timeAgo(entry.activity.created_at) }}</time
						>
					</p>
				</li>
				<li v-else class="flex gap-2.5">
					<span
						class="flex size-7 shrink-0 items-center justify-center rounded-full bg-(--color-paper-3) text-xs font-semibold"
						aria-hidden="true"
						>{{
							getInitials(entry.comment.user?.name ?? '?')
						}}</span
					>
					<div class="min-w-0 flex-1">
						<p
							class="flex flex-wrap items-baseline gap-x-2 text-sm"
						>
							<span class="font-semibold">{{
								entry.comment.user?.name ?? 'Former member'
							}}</span>
							<time
								:datetime="entry.comment.created_at"
								class="text-muted-foreground"
								>{{ timeAgo(entry.comment.created_at) }}</time
							>
							<span
								v-if="entry.comment.edited"
								class="text-muted-foreground"
								>(edited)</span
							>
						</p>

						<form
							v-if="editing === entry.comment.id"
							class="mt-1.5 space-y-2"
							@submit.prevent="save(entry.comment)"
						>
							<textarea
								v-model="editBody"
								name="body"
								aria-label="Edit comment"
								rows="3"
								maxlength="10000"
								:class="fieldClass"
								v-focus
								@keydown.esc.stop="editing = null"
								@keydown.meta.enter.prevent="
									save(entry.comment)
								"
							/>
							<div class="flex gap-2">
								<Button type="submit" size="sm">Save</Button>
								<Button
									type="button"
									variant="ghost"
									size="sm"
									@click="editing = null"
								>
									Cancel
								</Button>
							</div>
						</form>
						<template v-else>
							<div
								class="card-prose mt-1.5 rounded-[var(--radius-sheet)] bg-(--color-paper-2) px-3 py-2"
								v-html="entry.comment.body_html"
							/>
							<div
								v-if="entry.comment.mine"
								class="mt-1 flex gap-3 text-sm text-muted-foreground"
							>
								<button
									type="button"
									class="underline decoration-(--color-rule) underline-offset-4 hover:text-foreground"
									@click="edit(entry.comment)"
								>
									Edit
								</button>
								<button
									type="button"
									class="underline decoration-(--color-rule) underline-offset-4 hover:text-destructive"
									@click="remove(entry.comment)"
								>
									Delete
								</button>
							</div>
						</template>
					</div>
				</li>
			</template>
		</ol>
	</section>
</template>
