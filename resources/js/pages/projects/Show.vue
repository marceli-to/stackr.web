<script setup lang="ts">
import { Head, setLayoutProps } from '@inertiajs/vue3';
import { watchEffect } from 'vue';
import Board from '@/components/board/Board.vue';
import Heading from '@/components/Heading.vue';
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

    <div class="flex h-full min-h-0 flex-1 flex-col gap-6 p-4 md:p-6">
        <Heading
            :title="project.name"
            :description="project.description ?? undefined"
            class="mb-0!"
        />

        <Board :project="project" :stacks="stacks" />
    </div>
</template>
