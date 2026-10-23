<script setup lang="ts">
import { CalendarClock } from '@lucide/vue';
import { computed } from 'vue';
import type { BoardCard } from '@/types';

const props = defineProps<{
    card: BoardCard;
}>();

const due = computed(() => {
    if (!props.card.due_at) {
        return null;
    }

    const date = new Date(props.card.due_at);

    return {
        label: date.toLocaleDateString(undefined, {
            day: 'numeric',
            month: 'short',
        }),
        overdue: date.getTime() < Date.now(),
    };
});
</script>

<template>
    <article
        class="cursor-grab rounded-lg border border-transparent bg-card px-3 py-2 text-sm shadow-xs ring-1 ring-foreground/5 hover:border-foreground/15 active:cursor-grabbing"
    >
        <p class="break-words">{{ card.title }}</p>
        <p
            v-if="due"
            class="mt-1.5 inline-flex items-center gap-1 text-xs"
            :class="due.overdue ? 'text-destructive' : 'text-muted-foreground'"
        >
            <CalendarClock class="size-3.5" />
            {{ due.label }}
        </p>
    </article>
</template>
