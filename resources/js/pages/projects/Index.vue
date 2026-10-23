<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import {
    EllipsisVertical,
    FolderKanban,
    Pencil,
    Plus,
    Trash2,
} from '@lucide/vue';
import { ref } from 'vue';
import Heading from '@/components/Heading.vue';
import DeleteProjectDialog from '@/components/projects/DeleteProjectDialog.vue';
import ProjectFormDialog from '@/components/projects/ProjectFormDialog.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { index, show } from '@/routes/projects';
import type { ProjectSummary } from '@/types';

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Projects',
                href: index(),
            },
        ],
    },
});

defineProps<{
    projects: ProjectSummary[];
}>();

const formOpen = ref(false);
const editing = ref<ProjectSummary | null>(null);
const deleteOpen = ref(false);
const deleting = ref<ProjectSummary | null>(null);

function create() {
    editing.value = null;
    formOpen.value = true;
}

function edit(project: ProjectSummary) {
    editing.value = project;
    formOpen.value = true;
}

function remove(project: ProjectSummary) {
    deleting.value = project;
    deleteOpen.value = true;
}

function plural(count: number, word: string) {
    return `${count} ${word}${count === 1 ? '' : 's'}`;
}
</script>

<template>
    <Head title="Projects" />

    <div class="flex h-full flex-1 flex-col gap-6 p-4 md:p-6">
        <div class="flex items-start justify-between gap-4">
            <Heading
                title="Projects"
                description="Every project has its own board"
                class="mb-0!"
            />
            <Button @click="create" data-test="new-project-button">
                <Plus />
                New project
            </Button>
        </div>

        <div
            v-if="projects.length === 0"
            class="flex flex-1 flex-col items-center justify-center gap-3 rounded-xl border border-dashed border-sidebar-border/70 p-12 text-center dark:border-sidebar-border"
        >
            <FolderKanban class="size-10 text-muted-foreground" />
            <div class="space-y-1">
                <p class="font-medium">No projects yet</p>
                <p class="text-sm text-muted-foreground">
                    Create your first project to start a board.
                </p>
            </div>
            <Button variant="outline" @click="create">
                <Plus />
                New project
            </Button>
        </div>

        <ul v-else class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
            <li
                v-for="project in projects"
                :key="project.id"
                class="group relative flex flex-col gap-3 rounded-xl border border-sidebar-border/70 bg-card p-4 transition-colors hover:border-foreground/20 dark:border-sidebar-border"
            >
                <div class="flex items-start justify-between gap-2">
                    <Link
                        :href="show(project.id)"
                        class="font-medium after:absolute after:inset-0 after:rounded-xl focus-visible:outline-none"
                    >
                        {{ project.name }}
                    </Link>

                    <DropdownMenu>
                        <DropdownMenuTrigger as-child>
                            <Button
                                variant="ghost"
                                size="icon"
                                class="relative z-10 -mt-1 -mr-2 size-8"
                                :aria-label="`Actions for ${project.name}`"
                            >
                                <EllipsisVertical />
                            </Button>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent align="end">
                            <DropdownMenuItem @select="edit(project)">
                                <Pencil />
                                Edit
                            </DropdownMenuItem>
                            <DropdownMenuItem
                                v-if="project.role === 'owner'"
                                variant="destructive"
                                @select="remove(project)"
                            >
                                <Trash2 />
                                Delete
                            </DropdownMenuItem>
                        </DropdownMenuContent>
                    </DropdownMenu>
                </div>

                <p
                    v-if="project.description"
                    class="line-clamp-2 text-sm text-muted-foreground"
                >
                    {{ project.description }}
                </p>

                <div
                    class="mt-auto flex items-center gap-2 text-xs text-muted-foreground"
                >
                    <span>{{ plural(project.stacks_count, 'list') }}</span>
                    <span aria-hidden="true">·</span>
                    <span>{{ plural(project.cards_count, 'card') }}</span>
                    <Badge
                        v-if="project.role === 'member'"
                        variant="secondary"
                        class="ml-auto"
                    >
                        Shared with you
                    </Badge>
                </div>
            </li>
        </ul>
    </div>

    <ProjectFormDialog v-model:open="formOpen" :project="editing" />
    <DeleteProjectDialog v-model:open="deleteOpen" :project="deleting" />
</template>
