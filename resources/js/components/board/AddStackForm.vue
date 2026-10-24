<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import { PhPlus, PhX } from '@phosphor-icons/vue';
import { ref } from 'vue';
import StackController from '@/actions/App/Http/Controllers/StackController';
import { Button } from '@/components/ui/button';
import type { Project } from '@/types';

defineProps<{
    project: Project;
}>();

const open = ref(false);
</script>

<template>
    <div class="w-72 shrink-0">
        <!-- Stays open after adding, so several lists can be typed in a row. -->
        <Form
            v-if="open"
            v-bind="StackController.store.form(project.id)"
            :options="{ preserveScroll: true, preserveState: true }"
            reset-on-success
            class="space-y-2 rounded-[var(--radius-tile)] bg-field-well p-2"
            v-slot="{ errors, processing }"
        >
            <input
                name="name"
                required
                maxlength="255"
                placeholder="List name"
                aria-label="List name"
                class="block w-full rounded-[var(--radius-sheet)] bg-sheet px-3 py-2 text-[0.9375rem] text-foreground outline-hidden placeholder:text-muted-foreground focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ring focus-visible:outline-solid"
                v-focus
                @keydown.esc="open = false"
            />
            <p v-if="errors.name" class="text-xs text-destructive">
                {{ errors.name }}
            </p>
            <div class="flex items-center gap-1">
                <Button
                    type="submit"
                    size="sm"
                    :disabled="processing"
                    class="bg-field-ink text-field hover:bg-field-ink/90"
                >
                    Add list
                </Button>
                <Button
                    type="button"
                    variant="ghost"
                    size="icon"
                    class="size-8 text-inherit hover:bg-current/10 hover:text-inherit"
                    aria-label="Close"
                    @click="open = false"
                >
                    <PhX />
                </Button>
            </div>
        </Form>
        <Button
            v-else
            variant="ghost"
            class="h-11 w-full justify-start rounded-[var(--radius-tile)] border-2 border-current/30 text-inherit hover:border-current/60 hover:bg-transparent hover:text-inherit"
            @click="open = true"
        >
            <PhPlus />
            Add a list
        </Button>
    </div>
</template>
