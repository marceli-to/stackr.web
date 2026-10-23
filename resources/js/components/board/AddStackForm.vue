<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import { Plus, X } from '@lucide/vue';
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
            class="space-y-2 rounded-xl bg-muted/60 p-2 dark:bg-muted/40"
            v-slot="{ errors, processing }"
        >
            <input
                name="name"
                required
                maxlength="255"
                placeholder="List name"
                aria-label="List name"
                class="block w-full rounded-md border border-input bg-background px-3 py-1.5 text-sm outline-none placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                v-focus
                @keydown.esc="open = false"
            />
            <p v-if="errors.name" class="text-xs text-destructive">
                {{ errors.name }}
            </p>
            <div class="flex items-center gap-1">
                <Button type="submit" size="sm" :disabled="processing">
                    Add list
                </Button>
                <Button
                    type="button"
                    variant="ghost"
                    size="icon"
                    class="size-8"
                    aria-label="Close"
                    @click="open = false"
                >
                    <X />
                </Button>
            </div>
        </Form>
        <Button
            v-else
            variant="outline"
            class="w-full justify-start border-dashed text-muted-foreground"
            @click="open = true"
        >
            <Plus />
            Add a list
        </Button>
    </div>
</template>
