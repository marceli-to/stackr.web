<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import { EllipsisVertical, Pencil, Trash2 } from '@lucide/vue';
import { nextTick, ref, useTemplateRef } from 'vue';
import { VueDraggable } from 'vue-draggable-plus';
import type { DraggableEvent } from 'vue-draggable-plus';
import CardController from '@/actions/App/Http/Controllers/CardController';
import StackController from '@/actions/App/Http/Controllers/StackController';
import AddCardForm from '@/components/board/AddCardForm.vue';
import BoardCardItem from '@/components/board/BoardCardItem.vue';
import { saveMove } from '@/components/board/useBoardMove';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import type { BoardCard, BoardStack } from '@/types';

const props = defineProps<{
    stack: BoardStack;
    resync: () => void;
}>();

const cards = defineModel<BoardCard[]>('cards', { required: true });

const renaming = ref(false);
const deleteOpen = ref(false);
const nameInput = useTemplateRef('nameInput');

async function startRename() {
    renaming.value = true;
    await nextTick();
    nameInput.value?.select();
}

// Leaving the field saves, unless Escape already cancelled or nothing changed.
function finishRename(input: HTMLInputElement) {
    if (!renaming.value) {
        return;
    }

    if (input.value.trim() === props.stack.name) {
        renaming.value = false;

        return;
    }

    input.form?.requestSubmit();
}

// A card was reordered within this list, or dropped in from another one.
function onCardMoved(event: DraggableEvent<BoardCard>, fromElsewhere: boolean) {
    if (event.newIndex === undefined) {
        return;
    }

    if (!fromElsewhere && event.newIndex === event.oldIndex) {
        return;
    }

    saveMove(
        CardController.move(event.data.id),
        { index: event.newIndex, stack_id: props.stack.id },
        props.resync,
    );
}
</script>

<template>
    <section
        class="flex max-h-[calc(100svh-11rem)] w-72 shrink-0 flex-col rounded-xl bg-indigo-50 dark:bg-muted/40"
        :aria-label="stack.name"
    >
        <header
            class="flex items-center gap-1 px-3 pt-2 pb-1"
            data-stack-handle
            :class="renaming ? '' : 'cursor-grab active:cursor-grabbing'"
        >
            <Form
                v-if="renaming"
                v-bind="StackController.update.form(stack.id)"
                :options="{ preserveScroll: true, preserveState: true }"
                class="flex-1"
                @success="renaming = false"
                v-slot="{ errors }"
            >
                <input
                    ref="nameInput"
                    name="name"
                    :value="stack.name"
                    required
                    maxlength="255"
                    aria-label="List name"
                    class="w-full rounded-md border border-input bg-background px-2 py-1 text-sm font-medium outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                    @blur="finishRename($event.target as HTMLInputElement)"
                    @keydown.esc="renaming = false"
                />
                <p v-if="errors.name" class="mt-1 text-xs text-destructive">
                    {{ errors.name }}
                </p>
            </Form>
            <h3
                v-else
                class="flex-1 truncate px-2 py-1 text-sm font-medium"
                @dblclick="startRename"
            >
                {{ stack.name }}
                <span class="ml-1 font-normal text-muted-foreground">
                    {{ cards.length }}
                </span>
            </h3>

            <DropdownMenu>
                <DropdownMenuTrigger as-child>
                    <Button
                        variant="ghost"
                        size="icon"
                        class="size-7 hover:bg-indigo-100 hover:text-indigo-900 dark:hover:bg-accent dark:hover:text-accent-foreground"
                        :aria-label="`Actions for ${stack.name}`"
                    >
                        <EllipsisVertical />
                    </Button>
                </DropdownMenuTrigger>
                <DropdownMenuContent align="end">
                    <DropdownMenuItem @select="startRename">
                        <Pencil />
                        Rename
                    </DropdownMenuItem>
                    <DropdownMenuItem
                        variant="destructive"
                        @select="deleteOpen = true"
                    >
                        <Trash2 />
                        Delete list
                    </DropdownMenuItem>
                </DropdownMenuContent>
            </DropdownMenu>
        </header>

        <VueDraggable
            v-model="cards"
            group="cards"
            :animation="150"
            ghost-class="opacity-40"
            class="flex min-h-2 flex-col gap-2 overflow-y-auto px-2 py-1"
            :data-stack-id="stack.id"
            @update="onCardMoved($event, false)"
            @add="onCardMoved($event, true)"
        >
            <BoardCardItem v-for="card in cards" :key="card.id" :card="card" />
        </VueDraggable>

        <AddCardForm :stack="stack" />
    </section>

    <Dialog v-model:open="deleteOpen">
        <DialogContent>
            <Form
                v-bind="StackController.destroy.form(stack.id)"
                :options="{ preserveScroll: true, preserveState: true }"
                class="space-y-6"
                @success="deleteOpen = false"
                v-slot="{ processing }"
            >
                <DialogHeader class="space-y-3">
                    <DialogTitle>Delete “{{ stack.name }}”?</DialogTitle>
                    <DialogDescription>
                        {{
                            cards.length === 0
                                ? 'The list is empty.'
                                : `Its ${cards.length} ${cards.length === 1 ? 'card' : 'cards'} will be deleted too.`
                        }}
                        This cannot be undone.
                    </DialogDescription>
                </DialogHeader>
                <DialogFooter class="gap-2">
                    <DialogClose as-child>
                        <Button type="button" variant="secondary">
                            Cancel
                        </Button>
                    </DialogClose>
                    <Button
                        type="submit"
                        variant="destructive"
                        :disabled="processing"
                    >
                        Delete list
                    </Button>
                </DialogFooter>
            </Form>
        </DialogContent>
    </Dialog>
</template>
