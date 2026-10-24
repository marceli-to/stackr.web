<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import ProjectMembers from '@/components/projects/ProjectMembers.vue';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import UserMenuContent from '@/components/UserMenuContent.vue';
import { getInitials } from '@/composables/useInitials';
import { index } from '@/routes/projects';

const page = usePage();
const user = computed(() => page.props.auth.user);

// On a board the bar carries the project's name instead of the wordmark.
const project = computed(
    () => page.props.project as { name: string } | undefined,
);
</script>

<template>
    <header
        class="flex h-14 shrink-0 items-center gap-2 border-b border-current/15 px-3 md:px-5"
    >
        <Link
            :href="index()"
            class="mr-2 flex items-center gap-2 rounded-sm"
            aria-label="Stackr, all projects"
        >
            <AppLogoIcon class="size-5 fill-current" />
            <span
                v-if="!project"
                class="font-display text-xl leading-none font-bold"
            >
                {{ page.props.name }}
            </span>
        </Link>
        <span
            v-if="project"
            class="min-w-0 truncate font-display text-xl leading-normal font-bold"
        >
            {{ project.name }}
        </span>

        <div class="ml-auto flex shrink-0 items-center gap-3">
            <ProjectMembers v-if="project" />

            <DropdownMenu>
                <DropdownMenuTrigger as-child>
                    <button
                        type="button"
                        class="relative flex size-9 items-center justify-center rounded-full bg-current/15 text-sm font-semibold transition-colors duration-(--dur-micro) hover:bg-current/25 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ring focus-visible:outline-solid data-[state=open]:bg-current/25"
                        :aria-label="`Account menu for ${user.name}`"
                        data-test="user-menu-button"
                    >
                        {{ getInitials(user.name) }}
                        <span
                            class="absolute top-1/2 left-1/2 size-[max(100%,3rem)] -translate-1/2 pointer-fine:hidden"
                            aria-hidden="true"
                        />
                    </button>
                </DropdownMenuTrigger>
                <DropdownMenuContent align="end" class="w-64">
                    <UserMenuContent :user="user" />
                </DropdownMenuContent>
            </DropdownMenu>
        </div>
    </header>
</template>
