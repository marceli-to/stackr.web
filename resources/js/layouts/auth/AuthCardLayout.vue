<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { home } from '@/routes';

defineProps<{
    title?: string;
    description?: string;
}>();
</script>

<template>
    <div
        class="flex min-h-svh flex-col items-center justify-center gap-6 bg-indigo-50 p-6 md:p-10 dark:bg-muted"
    >
        <div class="flex w-full max-w-sm flex-col gap-6">
            <Link
                :href="home()"
                class="flex items-center gap-2 self-center font-medium"
            >
                <div class="flex h-9 w-9 items-center justify-center">
                    <AppLogoIcon class="size-9 fill-current text-primary" />
                </div>
            </Link>

            <div class="flex flex-col gap-6">
                <Card
                    class="gap-8 rounded-2xl py-8 shadow-[0_1px_2px_rgb(0_0_0/0.04),0_8px_24px_-6px_rgb(0_0_0/0.08)]"
                >
                    <CardHeader class="px-8 text-center">
                        <CardTitle class="text-xl">{{ title }}</CardTitle>
                        <CardDescription>
                            {{ description }}
                        </CardDescription>
                    </CardHeader>
                    <CardContent class="px-8">
                        <slot />
                    </CardContent>
                </Card>
            </div>
        </div>
    </div>
</template>
