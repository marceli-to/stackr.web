<script setup lang="ts">
import { Head, setLayoutProps } from '@inertiajs/vue3';
import { Columns3, Pencil, Trash2 } from '@lucide/vue';
import { ref, watchEffect } from 'vue';
import Heading from '@/components/Heading.vue';
import DeleteProjectDialog from '@/components/projects/DeleteProjectDialog.vue';
import ProjectFormDialog from '@/components/projects/ProjectFormDialog.vue';
import { Button } from '@/components/ui/button';
import { index, show } from '@/routes/projects';
import type { Project, ProjectRole } from '@/types';

const props = defineProps<{
    project: Project;
    role: ProjectRole;
}>();

watchEffect(() => {
    setLayoutProps({
        breadcrumbs: [
            { title: 'Projects', href: index() },
            { title: props.project.name, href: show(props.project.id) },
        ],
    });
});

const editOpen = ref(false);
const deleteOpen = ref(false);
</script>

<template>
    <Head :title="props.project.name" />

    <div class="flex h-full flex-1 flex-col gap-6 p-4 md:p-6">
        <div class="flex items-start justify-between gap-4">
            <Heading
                :title="project.name"
                :description="project.description ?? undefined"
                class="mb-0!"
            />
            <div class="flex shrink-0 gap-2">
                <Button variant="outline" @click="editOpen = true">
                    <Pencil />
                    Edit
                </Button>
                <Button
                    v-if="role === 'owner'"
                    variant="outline"
                    class="text-destructive hover:text-destructive"
                    @click="deleteOpen = true"
                >
                    <Trash2 />
                    Delete
                </Button>
            </div>
        </div>

        <div
            class="flex flex-1 flex-col items-center justify-center gap-3 rounded-xl border border-dashed border-sidebar-border/70 p-12 text-center dark:border-sidebar-border"
        >
            <Columns3 class="size-10 text-muted-foreground" />
            <p class="text-sm text-muted-foreground">
                Lists and cards for this board are coming next.
            </p>
        </div>
    </div>

    <ProjectFormDialog v-model:open="editOpen" :project="project" />
    <DeleteProjectDialog v-model:open="deleteOpen" :project="project" />
</template>
