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
    <div
        data-field="cobalt"
        class="flex min-h-svh flex-col bg-field text-field-ink"
    >
        <header class="px-6 py-5 md:px-10">
            <Link
                :href="home()"
                class="inline-flex items-center gap-2 rounded-sm"
                :aria-label="`${name} home`"
            >
                <AppLogoIcon class="size-6 fill-current" />
                <span class="font-display text-2xl leading-none font-bold">
                    {{ name }}
                </span>
            </Link>
        </header>

        <main
            class="grid flex-1 items-start gap-10 px-6 pt-[8vh] pb-16 md:grid-cols-[minmax(0,1fr)_minmax(0,26rem)] md:px-10"
        >
            <h1
                class="max-w-[12ch] font-display text-(length:--text-poster) leading-[0.95] font-bold tracking-(--tracking-display)"
            >
                {{ title }}
            </h1>
            <div
                class="rounded-[var(--radius-tile)] bg-sheet p-8 text-foreground [--ring:var(--color-focus)]"
            >
                <p v-if="description" class="mb-6 text-muted-foreground">
                    {{ description }}
                </p>
                <slot />
            </div>
        </main>
    </div>
</template>
