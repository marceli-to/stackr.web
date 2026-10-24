<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import { Plus, X } from '@lucide/vue';
import { ref } from 'vue';
import CardController from '@/actions/App/Http/Controllers/CardController';
import { Button } from '@/components/ui/button';
import type { BoardStack } from '@/types';

defineProps<{
    stack: BoardStack;
}>();

const open = ref(false);

function submitOnEnter(event: KeyboardEvent) {
    if (!event.shiftKey && !event.isComposing) {
        event.preventDefault();
        (event.target as HTMLTextAreaElement).form?.requestSubmit();
    }
}
</script>

<template>
    <div class="px-2 pt-1 pb-2">
        <!-- Stays open after adding, so several cards can be typed in a row. -->
        <Form
            v-if="open"
            v-bind="CardController.store.form(stack.id)"
            :options="{ preserveScroll: true, preserveState: true }"
            reset-on-success
            class="space-y-2"
            v-slot="{ errors, processing }"
        >
            <textarea
                name="title"
                rows="2"
                required
                maxlength="500"
                placeholder="Card title"
                aria-label="Card title"
                class="block w-full resize-none rounded-lg border border-input bg-card px-3 py-2 text-sm shadow-xs outline-none placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                v-focus
                @keydown.enter="submitOnEnter"
                @keydown.esc="open = false"
            />
            <p v-if="errors.title" class="text-xs text-destructive">
                {{ errors.title }}
            </p>
            <div class="flex items-center gap-1">
                <Button type="submit" size="sm" :disabled="processing">
                    Add card
                </Button>
                <Button
                    type="button"
                    variant="ghost"
                    size="icon"
                    class="size-8 hover:bg-indigo-100 hover:text-indigo-900 dark:hover:bg-accent dark:hover:text-accent-foreground"
                    aria-label="Close"
                    @click="open = false"
                >
                    <X />
                </Button>
            </div>
        </Form>
        <Button
            v-else
            variant="ghost"
            size="sm"
            class="w-full justify-start text-muted-foreground hover:bg-indigo-100 hover:text-indigo-900 dark:hover:bg-accent dark:hover:text-accent-foreground"
            @click="open = true"
        >
            <Plus />
            Add a card
        </Button>
    </div>
</template>
