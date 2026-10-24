<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { ChevronDown, ChevronsUpDown, LayoutGrid } from '@lucide/vue';
import { computed } from 'vue';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import UserMenuContent from '@/components/UserMenuContent.vue';
import { index, show } from '@/routes/projects';
import type { Project } from '@/types';

const page = usePage();
const user = computed(() => page.props.auth.user);
const projects = computed(() => page.props.sidebarProjects);
const current = computed(
    () => (page.props.project as Project | undefined) ?? null,
);
</script>

<template>
    <header
        class="flex h-14 shrink-0 items-center gap-2 border-b border-current/15 px-4 md:px-8"
    >
        <Link
            :href="index()"
            class="mr-2 flex items-center gap-2 rounded-sm"
            aria-label="Stackr, all projects"
        >
            <AppLogoIcon class="size-6 fill-current" />
            <span class="font-display text-2xl leading-none font-bold">
                {{ page.props.name }}
            </span>
        </Link>

        <DropdownMenu>
            <DropdownMenuTrigger as-child>
                <Button
                    variant="ghost"
                    class="min-w-0 gap-1.5 px-2 text-inherit hover:bg-current/10 hover:text-inherit"
                    data-test="project-switcher"
                >
                    <span class="truncate">{{
                        current?.name ?? 'Projects'
                    }}</span>
                    <ChevronsUpDown class="opacity-70" />
                </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="start" class="w-64">
                <DropdownMenuItem
                    v-for="project in projects"
                    :key="project.id"
                    as-child
                >
                    <Link
                        :href="show(project.id)"
                        class="w-full"
                        :aria-current="
                            current?.id === project.id ? 'page' : undefined
                        "
                        :class="{ 'font-semibold': current?.id === project.id }"
                    >
                        <span
                            :data-field="project.color"
                            class="size-3.5 shrink-0 rounded-[2px] bg-field"
                            aria-hidden="true"
                        />
                        <span class="truncate">{{ project.name }}</span>
                    </Link>
                </DropdownMenuItem>
                <DropdownMenuSeparator v-if="projects.length > 0" />
                <DropdownMenuItem as-child>
                    <Link :href="index()" class="w-full">
                        <LayoutGrid />
                        All projects
                    </Link>
                </DropdownMenuItem>
            </DropdownMenuContent>
        </DropdownMenu>

        <DropdownMenu>
            <DropdownMenuTrigger as-child>
                <Button
                    variant="ghost"
                    class="ml-auto gap-1.5 px-2 text-inherit hover:bg-current/10 hover:text-inherit"
                    data-test="user-menu-button"
                >
                    <span class="max-w-40 truncate">{{ user.name }}</span>
                    <ChevronDown class="opacity-70" />
                </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end" class="w-56">
                <UserMenuContent :user="user" />
            </DropdownMenuContent>
        </DropdownMenu>
    </header>
</template>
