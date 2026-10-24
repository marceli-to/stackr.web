<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
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
                <button
                    type="button"
                    class="relative ml-auto flex size-9 items-center justify-center rounded-full text-sm font-semibold ring-1 ring-current/35 transition-colors duration-(--dur-micro) ring-inset hover:bg-current/10 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ring focus-visible:outline-solid data-[state=open]:bg-current/10"
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
    </header>
</template>
