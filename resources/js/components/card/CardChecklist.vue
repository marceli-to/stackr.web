<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { PhCheckSquare, PhX } from '@phosphor-icons/vue';
import { computed, ref } from 'vue';
import ChecklistController from '@/actions/App/Http/Controllers/ChecklistController';
import ChecklistItemController from '@/actions/App/Http/Controllers/ChecklistItemController';
import { keep } from '@/components/card/board';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import type { Checklist, ChecklistItem } from '@/types';

const props = defineProps<{
    checklist: Checklist;
}>();

const done = computed(
    () => props.checklist.items.filter((item) => item.done).length,
);
const percent = computed(() =>
    props.checklist.items.length === 0
        ? 0
        : Math.round((done.value / props.checklist.items.length) * 100),
);

const hideDone = ref(false);
const shown = computed(() =>
    hideDone.value
        ? props.checklist.items.filter((item) => !item.done)
        : props.checklist.items,
);

const renaming = ref(false);
const title = ref('');
const adding = ref(false);
const newItem = ref('');
const editingItem = ref<number | null>(null);
const itemTitle = ref('');

function rename() {
    if (title.value.trim() && title.value.trim() !== props.checklist.title) {
        router.patch(
            ChecklistController.update(props.checklist.id).url,
            { title: title.value.trim() },
            keep,
        );
    }

    renaming.value = false;
}

function remove() {
    router.delete(ChecklistController.destroy(props.checklist.id).url, keep);
}

function toggle(item: ChecklistItem) {
    router.patch(
        ChecklistItemController.update(item.id).url,
        { done: !item.done },
        keep,
    );
}

// Stays open after adding, so a list can be typed in one go.
function add() {
    if (!newItem.value.trim()) {
        return;
    }

    router.post(
        ChecklistItemController.store(props.checklist.id).url,
        { title: newItem.value.trim() },
        { ...keep, onSuccess: () => (newItem.value = '') },
    );
}

function editItem(item: ChecklistItem) {
    editingItem.value = item.id;
    itemTitle.value = item.title;
}

function saveItem(item: ChecklistItem) {
    if (itemTitle.value.trim() && itemTitle.value.trim() !== item.title) {
        router.patch(
            ChecklistItemController.update(item.id).url,
            { title: itemTitle.value.trim() },
            keep,
        );
    }

    editingItem.value = null;
}

function removeItem(item: ChecklistItem) {
    router.delete(ChecklistItemController.destroy(item.id).url, keep);
}
</script>

<template>
    <section :aria-label="checklist.title">
        <div class="mb-3 flex items-center gap-2.5">
            <PhCheckSquare class="size-5 shrink-0" />
            <form v-if="renaming" class="flex-1" @submit.prevent="rename">
                <Input
                    v-model="title"
                    name="title"
                    aria-label="Checklist title"
                    maxlength="255"
                    v-focus
                    @blur="rename"
                    @keydown.esc.stop="renaming = false"
                />
            </form>
            <h3
                v-else
                class="min-w-0 flex-1 cursor-text truncate text-base font-semibold"
                @click="
                    title = checklist.title;
                    renaming = true;
                "
            >
                {{ checklist.title }}
            </h3>
            <Button
                v-if="done > 0"
                variant="secondary"
                size="sm"
                @click="hideDone = !hideDone"
            >
                {{ hideDone ? `Show checked (${done})` : 'Hide checked' }}
            </Button>
            <Button variant="secondary" size="sm" @click="remove">
                Delete
            </Button>
        </div>

        <div class="mb-2 flex items-center gap-3 pl-7.5">
            <span class="num w-9 text-sm text-muted-foreground"
                >{{ percent }}%</span
            >
            <div
                class="h-2 flex-1 overflow-hidden rounded-full bg-(--color-paper-3)"
                role="progressbar"
                :aria-valuenow="percent"
                aria-valuemin="0"
                aria-valuemax="100"
                :aria-label="`${checklist.title} progress`"
            >
                <div
                    class="h-full origin-left rounded-full transition-transform duration-(--dur-short)"
                    :class="
                        percent === 100
                            ? 'bg-(--color-success)'
                            : 'bg-foreground'
                    "
                    :style="{ transform: `scaleX(${percent / 100})` }"
                />
            </div>
        </div>

        <ul class="space-y-0.5">
            <li
                v-for="item in shown"
                :key="item.id"
                class="group flex items-start gap-2.5 rounded-[var(--radius-sheet)] py-1 pr-1 pl-1.5 hover:bg-(--color-paper-2)"
            >
                <span class="flex h-6 shrink-0 items-center">
                    <input
                        type="checkbox"
                        :checked="item.done"
                        :aria-label="item.title"
                        class="size-4 cursor-pointer accent-(--color-ink)"
                        @change="toggle(item)"
                    />
                </span>
                <form
                    v-if="editingItem === item.id"
                    class="flex-1"
                    @submit.prevent="saveItem(item)"
                >
                    <Input
                        v-model="itemTitle"
                        name="title"
                        aria-label="Item"
                        maxlength="500"
                        v-focus
                        @blur="saveItem(item)"
                        @keydown.esc.stop="editingItem = null"
                    />
                </form>
                <span
                    v-else
                    class="min-w-0 flex-1 cursor-text py-0.5 break-words"
                    :class="{ 'text-muted-foreground line-through': item.done }"
                    @click="editItem(item)"
                >
                    {{ item.title }}
                </span>
                <Button
                    variant="ghost"
                    size="icon-sm"
                    class="opacity-0 group-hover:opacity-100 focus-visible:opacity-100"
                    :aria-label="`Delete ${item.title}`"
                    @click="removeItem(item)"
                >
                    <PhX />
                </Button>
            </li>
        </ul>

        <form v-if="adding" class="mt-2 space-y-2 pl-7.5" @submit.prevent="add">
            <Input
                v-model="newItem"
                name="title"
                aria-label="New item"
                placeholder="Add an item"
                maxlength="500"
                v-focus
                @keydown.esc.stop="adding = false"
            />
            <div class="flex gap-2">
                <Button type="submit" size="sm">Add</Button>
                <Button
                    type="button"
                    variant="ghost"
                    size="sm"
                    @click="adding = false"
                >
                    Cancel
                </Button>
            </div>
        </form>
        <Button
            v-else
            variant="secondary"
            size="sm"
            class="mt-2 ml-7.5"
            @click="adding = true"
        >
            Add an item
        </Button>
    </section>
</template>
