<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import { home } from '@/routes';

defineProps<{
    title?: string;
    description?: string;
}>();

const name = usePage().props.name;
</script>

<template>
    <div class="flex min-h-svh flex-col bg-background">
        <header
            class="flex h-14 shrink-0 items-center border-b border-border px-4 md:px-8"
        >
            <Link
                :href="home()"
                class="flex items-center gap-2 rounded-sm"
                :aria-label="`${name} home`"
            >
                <AppLogoIcon class="size-6 fill-current" />
                <span class="font-display text-2xl leading-none font-bold">
                    {{ name }}
                </span>
            </Link>
        </header>

        <main class="px-4 pt-8 pb-16 md:px-8">
            <header class="mb-10 space-y-4">
                <h1
                    class="font-display text-(length:--text-poster) leading-[0.95] font-bold tracking-(--tracking-display)"
                >
                    {{ title }}
                </h1>
                <p v-if="description" class="text-lg text-muted-foreground">
                    {{ description }}
                </p>
            </header>
            <div class="max-w-xs">
                <slot />
            </div>
        </main>
    </div>
</template>
