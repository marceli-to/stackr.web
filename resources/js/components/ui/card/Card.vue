<script setup lang="ts">
import type { HTMLAttributes } from "vue"
import { cn } from "@/lib/utils"

const props = defineProps<{
  class?: HTMLAttributes["class"]
}>()
</script>

<template>
  <div
    data-slot="card"
    :class="
      cn(
        'bg-card text-card-foreground flex flex-col gap-6 rounded-[var(--radius-tile)] py-6 ring-1 ring-(--surface-edge)',
        props.class,
      )
    "
  >
    <slot />
  </div>
</template>
