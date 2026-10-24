<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { EllipsisVertical, Pencil, Plus, Trash2 } from '@lucide/vue';
import { computed, ref } from 'vue';
import DeleteProjectDialog from '@/components/projects/DeleteProjectDialog.vue';
import ProjectFormDialog from '@/components/projects/ProjectFormDialog.vue';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { index, show } from '@/routes/projects';
import { projectColors } from '@/types';
import type { ProjectColor, ProjectSummary } from '@/types';

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

const props = defineProps<{
    projects: ProjectSummary[];
}>();

// A new project starts on the colour the wall uses least.
const suggestedColor = computed<ProjectColor>(() => {
    const uses = (color: ProjectColor) =>
        props.projects.filter((project) => project.color === color).length;

    return [...projectColors].sort((a, b) => uses(a) - uses(b))[0];
});

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

    <div class="flex h-full flex-1 flex-col gap-8 px-4 pt-8 pb-12 md:px-8">
        <div class="flex items-end justify-between gap-4">
            <header class="space-y-4">
                <h1
                    class="font-display text-(length:--text-poster) leading-[0.95] font-bold tracking-(--tracking-display)"
                >
                    Projects
                </h1>
                <p class="text-lg text-muted-foreground">
                    Every project has its own board
                </p>
            </header>
            <Button @click="create" data-test="new-project-button">
                <Plus />
                New project
            </Button>
        </div>

        <div
            v-if="projects.length === 0"
            class="flex flex-col items-start gap-4 rounded-[var(--radius-tile)] border-2 border-dashed border-border p-8"
        >
            <div class="space-y-1">
                <p class="font-display text-3xl font-bold">No projects yet</p>
                <p class="text-muted-foreground">
                    Create your first project to start a board.
                </p>
            </div>
            <Button variant="outline" @click="create">
                <Plus />
                New project
            </Button>
        </div>

        <ul
            v-else
            class="grid grid-cols-[repeat(auto-fill,minmax(min(100%,20rem),1fr))] gap-3"
        >
            <li
                v-for="project in projects"
                :key="project.id"
                :data-field="project.color"
                class="relative flex aspect-[4/3] flex-col rounded-[var(--radius-tile)] bg-field p-5 text-field-ink"
            >
                <div class="flex items-start justify-between gap-2">
                    <Link
                        :href="show(project.id)"
                        class="min-w-0 font-display text-4xl leading-[0.95] font-bold tracking-(--tracking-display) break-words after:absolute after:inset-0 after:rounded-[var(--radius-tile)] focus-visible:outline-none focus-visible:after:outline-2 focus-visible:after:outline-offset-2 focus-visible:after:outline-(--color-focus)"
                    >
                        {{ project.name }}
                    </Link>

                    <DropdownMenu>
                        <DropdownMenuTrigger as-child>
                            <Button
                                variant="ghost"
                                size="icon"
                                class="relative z-10 -mt-1 -mr-2 size-8 text-inherit hover:bg-current/10 hover:text-inherit data-[state=open]:bg-current/10"
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
                    class="mt-3 line-clamp-2 max-w-[40ch] opacity-90"
                >
                    {{ project.description }}
                </p>

                <div
                    class="mt-auto flex flex-wrap items-center gap-x-4 gap-y-1 text-sm"
                >
                    <span class="num">{{
                        plural(project.stacks_count, 'list')
                    }}</span>
                    <span class="num">{{
                        plural(project.cards_count, 'card')
                    }}</span>
                    <span
                        v-if="project.role === 'member'"
                        class="ml-auto font-semibold"
                    >
                        Shared with you
                    </span>
                </div>
            </li>
        </ul>
    </div>

    <ProjectFormDialog
        v-model:open="formOpen"
        :project="editing"
        :suggested-color="suggestedColor"
    />
    <DeleteProjectDialog v-model:open="deleteOpen" :project="deleting" />
</template>
