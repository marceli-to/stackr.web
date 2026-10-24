<script setup lang="ts">
import { Head, setLayoutProps } from '@inertiajs/vue3';
import { watchEffect } from 'vue';
import Board from '@/components/board/Board.vue';
import { index, show } from '@/routes/projects';
import type { BoardStack, Project } from '@/types';

const props = defineProps<{
    project: Project;
    stacks: BoardStack[];
}>();

watchEffect(() => {
    setLayoutProps({
        breadcrumbs: [
            { title: 'Projects', href: index() },
            { title: props.project.name, href: show(props.project.id) },
        ],
    });
});
</script>

<template>
    <Head :title="props.project.name" />

    <div class="flex h-full min-h-0 flex-1 flex-col gap-14 px-4 pt-8 md:px-8">
        <header class="space-y-3">
            <h1
                class="max-w-[28ch] font-display text-(length:--text-board-title) leading-[0.95] font-bold tracking-(--tracking-display)"
            >
                {{ project.name }}
            </h1>
            <p v-if="project.description" class="max-w-[60ch] text-lg">
                {{ project.description }}
            </p>
        </header>

        <Board :project="project" :stacks="stacks" />
    </div>
</template>
