<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { PhTextAlignLeft } from '@phosphor-icons/vue';
import { ref } from 'vue';
import CardController from '@/actions/App/Http/Controllers/CardController';
import { keep } from '@/components/card/board';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import type { CardDetail } from '@/types';

const props = defineProps<{
    card: CardDetail;
}>();

const editing = ref(false);
const text = ref('');
const error = ref<string>();
const saving = ref(false);

function edit() {
    text.value = props.card.description ?? '';
    error.value = undefined;
    editing.value = true;
}

function save() {
    saving.value = true;
    router.patch(
        CardController.update(props.card.id).url,
        { description: text.value.trim() || null },
        {
            ...keep,
            onSuccess: () => (editing.value = false),
            onError: (errors) => (error.value = errors.description),
            onFinish: () => (saving.value = false),
        },
    );
}

// Links in the description open on their own; any other click edits it.
function clickBody(event: MouseEvent) {
    if (!(event.target as HTMLElement).closest('a')) {
        edit();
    }
}
</script>

<template>
    <section aria-labelledby="card-description">
        <div class="mb-3 flex items-center gap-2.5">
            <PhTextAlignLeft class="size-5" />
            <h3 id="card-description" class="text-base font-semibold">
                Description
            </h3>
            <Button
                v-if="card.description && !editing"
                variant="secondary"
                size="sm"
                class="ml-auto"
                @click="edit"
            >
                Edit
            </Button>
        </div>

        <form v-if="editing" class="space-y-2 pl-7.5" @submit.prevent="save">
            <textarea
                v-model="text"
                name="description"
                aria-label="Description"
                rows="8"
                maxlength="20000"
                placeholder="Add a more detailed description…"
                class="block w-full resize-y rounded-[var(--radius-sheet)] bg-sheet px-3 py-2 text-base shadow-xs ring-1 focus-field ring-(--control-edge) outline-hidden ring-inset placeholder:text-muted-foreground sm:text-[0.9375rem] dark:bg-(--color-paper-2)"
                v-focus
                @keydown.meta.enter.prevent="save"
                @keydown.ctrl.enter.prevent="save"
                @keydown.esc.stop="editing = false"
            />
            <InputError :message="error" />
            <div class="flex items-center gap-2">
                <Button type="submit" :disabled="saving">Save</Button>
                <Button type="button" variant="ghost" @click="editing = false">
                    Cancel
                </Button>
                <span class="ml-auto text-sm text-muted-foreground"
                    >Markdown works here</span
                >
            </div>
        </form>

        <div
            v-else-if="card.description_html"
            class="card-prose pl-7.5"
            v-html="card.description_html"
            @click="clickBody"
        />

        <button
            v-else
            type="button"
            class="ml-7.5 block w-[calc(100%-1.875rem)] rounded-[var(--radius-sheet)] bg-(--color-paper-2) px-3 py-3 text-left text-muted-foreground hover:bg-(--color-paper-3) focus-visible:outline-2 focus-visible:outline-ring focus-visible:outline-solid"
            @click="edit"
        >
            Add a more detailed description…
        </button>
    </section>
</template>
