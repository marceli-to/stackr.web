<script setup lang="ts">
import { PhCalendarBlank } from '@phosphor-icons/vue';
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
        class="cursor-grab rounded-[var(--radius-sheet)] bg-sheet px-3 py-2.5 text-[0.9375rem] leading-snug text-foreground hover:outline-2 hover:outline-current/20 active:cursor-grabbing"
    >
        <p class="break-words">{{ card.title }}</p>
        <p
            v-if="due"
            class="num mt-1.5 inline-flex items-center gap-1 text-sm"
            :class="
                due.overdue
                    ? 'font-semibold text-destructive'
                    : 'text-muted-foreground'
            "
        >
            <PhCalendarBlank weight="regular" class="size-3.5" />
            <span v-if="due.overdue" class="sr-only">Overdue: </span>
            {{ due.label }}
        </p>
    </article>
</template>
