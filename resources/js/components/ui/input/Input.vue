<script setup lang="ts">
import type { HTMLAttributes } from "vue"
import { useVModel } from "@vueuse/core"
import { cn } from "@/lib/utils"

const props = defineProps<{
  defaultValue?: string | number
  modelValue?: string | number
  class?: HTMLAttributes["class"]
}>()

const emits = defineEmits<{
  (e: "update:modelValue", payload: string | number): void
}>()

const modelValue = useVModel(props, "modelValue", emits, {
  passive: true,
  defaultValue: props.defaultValue,
})
</script>

<template>
  <input
    v-model="modelValue"
    data-slot="input"
    :class="cn(
      'file:text-foreground placeholder:text-muted-foreground h-9 w-full min-w-0 rounded-[var(--radius-sheet)] border-0 bg-sheet px-3 dark:bg-(--color-paper-2) py-1 text-base shadow-xs ring-1 ring-inset ring-(--control-edge) outline-hidden file:inline-flex file:h-7 file:border-0 file:bg-transparent file:text-sm file:font-medium disabled:cursor-not-allowed disabled:opacity-55 sm:text-[0.9375rem]',
      'focus-field',
      'aria-invalid:ring-destructive',
      props.class,
    )"
  >
</template>
