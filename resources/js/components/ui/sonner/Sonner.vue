<script lang="ts" setup>
import type { ToasterProps } from "vue-sonner"
import { PhCheckCircle, PhCircleNotch, PhInfo, PhWarning, PhX, PhXCircle } from "@phosphor-icons/vue"
import { Toaster as Sonner } from "vue-sonner"
import { cn } from "@/lib/utils"

import 'vue-sonner/style.css';

const props = defineProps<ToasterProps>()
</script>

<template>
  <Sonner
    :class="cn('toaster group', props.class)"
    :style="{
      '--normal-bg': 'var(--color-sheet)',
      '--normal-text': 'var(--color-ink)',
      '--normal-border': 'var(--surface-edge)',
      '--border-radius': 'var(--radius-tile)',
      '--width': '22rem',
    }"
    v-bind="props"
  >
    <template #success-icon>
      <PhCheckCircle class="size-4" />
    </template>
    <template #info-icon>
      <PhInfo class="size-4" />
    </template>
    <template #warning-icon>
      <PhWarning class="size-4" />
    </template>
    <template #error-icon>
      <PhXCircle class="size-4" />
    </template>
    <template #loading-icon>
      <div>
        <PhCircleNotch class="size-4 animate-spin" />
      </div>
    </template>
    <template #close-icon>
      <PhX class="size-4" />
    </template>
  </Sonner>
</template>
