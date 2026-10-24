<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { PhPaperclip } from '@phosphor-icons/vue';
import AttachmentController from '@/actions/App/Http/Controllers/AttachmentController';
import CardController from '@/actions/App/Http/Controllers/CardController';
import { fileSize, keep, timeAgo } from '@/components/card/board';
import { Button } from '@/components/ui/button';
import type { CardAttachment, CardDetail } from '@/types';

const props = defineProps<{
    card: CardDetail;
    uploading: number | null;
}>();

const emit = defineEmits<{
    pick: [];
}>();

function setCover(attachment: CardAttachment | null) {
    router.patch(
        CardController.update(props.card.id).url,
        { cover_attachment_id: attachment?.id ?? null },
        keep,
    );
}

function remove(attachment: CardAttachment) {
    router.delete(AttachmentController.destroy(attachment.id).url, keep);
}

function extension(name: string): string {
    const dot = name.lastIndexOf('.');

    return dot > 0 ? name.slice(dot + 1, dot + 5).toUpperCase() : 'FILE';
}
</script>

<template>
    <section aria-labelledby="card-attachments">
        <div class="mb-3 flex items-center gap-2.5">
            <PhPaperclip class="size-5" />
            <h3 id="card-attachments" class="text-base font-semibold">
                Attachments
            </h3>
            <Button
                variant="secondary"
                size="sm"
                class="ml-auto"
                @click="emit('pick')"
            >
                Add
            </Button>
        </div>

        <p
            v-if="uploading !== null"
            class="num mb-2 pl-7.5 text-sm text-muted-foreground"
            role="status"
        >
            Uploading… {{ uploading }}%
        </p>

        <ul class="space-y-2 pl-7.5">
            <li
                v-for="attachment in card.attachments"
                :key="attachment.id"
                class="flex items-center gap-3"
            >
                <a
                    :href="attachment.url"
                    target="_blank"
                    rel="noopener"
                    class="flex h-16 w-24 shrink-0 items-center justify-center overflow-hidden rounded-[var(--radius-sheet)] bg-(--color-paper-3) text-sm font-semibold text-muted-foreground focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ring focus-visible:outline-solid"
                    :aria-label="`Open ${attachment.name}`"
                >
                    <img
                        v-if="attachment.is_image"
                        :src="attachment.url"
                        alt=""
                        loading="lazy"
                        class="size-full object-cover"
                    />
                    <span v-else>{{ extension(attachment.name) }}</span>
                </a>
                <div class="min-w-0 flex-1">
                    <a
                        :href="attachment.url"
                        target="_blank"
                        rel="noopener"
                        class="block truncate font-semibold hover:underline"
                        >{{ attachment.name }}</a
                    >
                    <p
                        class="flex flex-wrap gap-x-3 text-sm text-muted-foreground"
                    >
                        <span>{{ timeAgo(attachment.created_at) }}</span>
                        <span class="num">{{ fileSize(attachment.size) }}</span>
                        <span v-if="card.cover_attachment_id === attachment.id"
                            >Cover</span
                        >
                    </p>
                    <div class="mt-1 flex flex-wrap gap-x-3 text-sm">
                        <a
                            :href="attachment.download_url"
                            class="underline decoration-(--color-rule) underline-offset-4 hover:decoration-current"
                            >Download</a
                        >
                        <template v-if="attachment.is_image">
                            <button
                                v-if="
                                    card.cover_attachment_id === attachment.id
                                "
                                type="button"
                                class="underline decoration-(--color-rule) underline-offset-4 hover:decoration-current"
                                @click="setCover(null)"
                            >
                                Remove cover
                            </button>
                            <button
                                v-else
                                type="button"
                                class="underline decoration-(--color-rule) underline-offset-4 hover:decoration-current"
                                @click="setCover(attachment)"
                            >
                                Make cover
                            </button>
                        </template>
                        <button
                            type="button"
                            class="text-destructive underline decoration-destructive/30 underline-offset-4 hover:decoration-current"
                            @click="remove(attachment)"
                        >
                            Delete
                        </button>
                    </div>
                </div>
            </li>
        </ul>

        <p class="mt-3 pl-7.5 text-sm text-muted-foreground">
            Drop files anywhere on the card, or paste an image.
        </p>
    </section>
</template>
