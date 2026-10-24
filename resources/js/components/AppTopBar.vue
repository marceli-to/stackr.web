<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { ChevronDown } from '@lucide/vue';
import { computed } from 'vue';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import UserMenuContent from '@/components/UserMenuContent.vue';
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
                <Button
                    variant="ghost"
                    class="ml-auto gap-1.5 px-2 text-inherit hover:bg-current/10 hover:text-inherit data-[state=open]:bg-current/10"
                    data-test="user-menu-button"
                >
                    <span class="max-w-40 truncate">{{ user.name }}</span>
                    <ChevronDown class="opacity-70" />
                </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end" class="w-64">
                <UserMenuContent :user="user" />
            </DropdownMenuContent>
        </DropdownMenu>
    </header>
</template>
