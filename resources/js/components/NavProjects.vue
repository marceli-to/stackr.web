<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import {
    SidebarGroup,
    SidebarGroupLabel,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { useCurrentUrl } from '@/composables/useCurrentUrl';
import { show } from '@/routes/projects';

const page = usePage();
const projects = computed(() => page.props.sidebarProjects);

const { isCurrentUrl } = useCurrentUrl();
</script>

<template>
    <SidebarGroup
        v-if="projects.length > 0"
        class="px-2 py-0 group-data-[collapsible=icon]:hidden"
    >
        <SidebarGroupLabel>Your projects</SidebarGroupLabel>
        <SidebarMenu>
            <SidebarMenuItem v-for="project in projects" :key="project.id">
                <SidebarMenuButton
                    as-child
                    :is-active="isCurrentUrl(show(project.id))"
                >
                    <Link :href="show(project.id)">
                        <span
                            class="flex size-5 shrink-0 items-center justify-center rounded bg-sidebar-accent text-[0.625rem] font-semibold uppercase"
                            aria-hidden="true"
                        >
                            {{ project.name.charAt(0) }}
                        </span>
                        <span>{{ project.name }}</span>
                    </Link>
                </SidebarMenuButton>
            </SidebarMenuItem>
        </SidebarMenu>
    </SidebarGroup>
</template>
