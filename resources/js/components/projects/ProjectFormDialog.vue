<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import { computed } from 'vue';
import ProjectController from '@/actions/App/Http/Controllers/ProjectController';
import InputError from '@/components/InputError.vue';
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
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { Project } from '@/types';

// Creates a project, or edits `project` when one is given.
const props = defineProps<{
    project?: Project | null;
}>();

const open = defineModel<boolean>('open', { required: true });

const form = computed(() =>
    props.project
        ? ProjectController.update.form(props.project.id)
        : ProjectController.store.form(),
);
</script>

<template>
    <Dialog v-model:open="open">
        <DialogContent>
            <Form
                :key="project?.id ?? 'new'"
                v-bind="form"
                :reset-on-success="!project"
                :options="{ preserveScroll: true }"
                class="space-y-6"
                @success="open = false"
                v-slot="{ errors, processing }"
            >
                <DialogHeader>
                    <DialogTitle>
                        {{ project ? 'Edit project' : 'New project' }}
                    </DialogTitle>
                    <DialogDescription>
                        {{
                            project
                                ? 'Change the name or description.'
                                : 'Each project gets its own board.'
                        }}
                    </DialogDescription>
                </DialogHeader>

                <div class="grid gap-2">
                    <Label for="project-name">Name</Label>
                    <Input
                        id="project-name"
                        name="name"
                        :default-value="project?.name"
                        required
                        maxlength="255"
                        placeholder="e.g. Website relaunch"
                        v-focus
                    />
                    <InputError :message="errors.name" />
                </div>

                <div class="grid gap-2">
                    <Label for="project-description">
                        Description
                        <span class="font-normal text-muted-foreground"
                            >(optional)</span
                        >
                    </Label>
                    <textarea
                        id="project-description"
                        name="description"
                        rows="3"
                        maxlength="2000"
                        :value="project?.description ?? ''"
                        class="w-full min-w-0 rounded-md border border-input bg-transparent px-3 py-2 text-base shadow-xs outline-none placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 md:text-sm dark:bg-input/30"
                    />
                    <InputError :message="errors.description" />
                </div>

                <DialogFooter class="gap-2">
                    <DialogClose as-child>
                        <Button type="button" variant="secondary">
                            Cancel
                        </Button>
                    </DialogClose>
                    <Button
                        type="submit"
                        :disabled="processing"
                        data-test="save-project-button"
                    >
                        {{ project ? 'Save' : 'Create project' }}
                    </Button>
                </DialogFooter>
            </Form>
        </DialogContent>
    </Dialog>
</template>
