<script setup lang="ts">
import { router, usePage } from '@inertiajs/vue3';
import { PhChatCircle } from '@phosphor-icons/vue';
import { computed, ref } from 'vue';
import CommentController from '@/actions/App/Http/Controllers/CommentController';
import { keep, timeAgo } from '@/components/card/board';
import { Button } from '@/components/ui/button';
import { getInitials } from '@/composables/useInitials';
import type { CardComment, CardDetail } from '@/types';

const props = defineProps<{
    card: CardDetail;
}>();

const me = computed(() => usePage().props.auth.user);

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
            <h3 id="card-comments" class="text-base font-semibold">Comments</h3>
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
            <li
                v-for="comment in card.comments"
                :key="comment.id"
                class="flex gap-2.5"
            >
                <span
                    class="flex size-7 shrink-0 items-center justify-center rounded-full bg-(--color-paper-3) text-xs font-semibold"
                    aria-hidden="true"
                    >{{ getInitials(comment.user?.name ?? '?') }}</span
                >
                <div class="min-w-0 flex-1">
                    <p class="flex flex-wrap items-baseline gap-x-2 text-sm">
                        <span class="font-semibold">{{
                            comment.user?.name ?? 'Former member'
                        }}</span>
                        <time
                            :datetime="comment.created_at"
                            class="text-muted-foreground"
                            >{{ timeAgo(comment.created_at) }}</time
                        >
                        <span
                            v-if="comment.edited"
                            class="text-muted-foreground"
                            >(edited)</span
                        >
                    </p>

                    <form
                        v-if="editing === comment.id"
                        class="mt-1.5 space-y-2"
                        @submit.prevent="save(comment)"
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
                            @keydown.meta.enter.prevent="save(comment)"
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
                            v-html="comment.body_html"
                        />
                        <div
                            v-if="comment.mine"
                            class="mt-1 flex gap-3 text-sm text-muted-foreground"
                        >
                            <button
                                type="button"
                                class="underline decoration-(--color-rule) underline-offset-4 hover:text-foreground"
                                @click="edit(comment)"
                            >
                                Edit
                            </button>
                            <button
                                type="button"
                                class="underline decoration-(--color-rule) underline-offset-4 hover:text-destructive"
                                @click="remove(comment)"
                            >
                                Delete
                            </button>
                        </div>
                    </template>
                </div>
            </li>
        </ol>
    </section>
</template>
