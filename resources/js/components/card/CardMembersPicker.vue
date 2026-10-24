<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { PhCheck } from '@phosphor-icons/vue';
import CardMemberController from '@/actions/App/Http/Controllers/CardMemberController';
import { keep, useBoard } from '@/components/card/board';
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/components/ui/popover';
import { getInitials } from '@/composables/useInitials';
import type { CardDetail, Member } from '@/types';

const props = defineProps<{
    card: CardDetail;
}>();

const { members } = useBoard();

function toggle(member: Member) {
    const route = props.card.members.includes(member.id)
        ? CardMemberController.destroy([props.card.id, member.id])
        : CardMemberController.store([props.card.id, member.id]);

    router.visit(route, keep);
}
</script>

<template>
    <Popover>
        <PopoverTrigger as-child>
            <slot />
        </PopoverTrigger>
        <PopoverContent class="p-2">
            <p class="px-2 pt-1 pb-2 font-semibold">Members</p>
            <ul>
                <li v-for="member in members" :key="member.id">
                    <button
                        type="button"
                        class="flex w-full items-center gap-2.5 rounded-[var(--radius-sheet)] px-2 py-1.5 text-left hover:bg-(--menu-hover) focus-visible:outline-2 focus-visible:outline-ring focus-visible:outline-solid"
                        :aria-pressed="card.members.includes(member.id)"
                        @click="toggle(member)"
                    >
                        <span
                            class="flex size-7 shrink-0 items-center justify-center rounded-full bg-(--color-paper-3) text-xs font-semibold"
                            aria-hidden="true"
                        >
                            {{ getInitials(member.name) }}
                        </span>
                        <span class="truncate">{{ member.name }}</span>
                        <PhCheck
                            v-if="card.members.includes(member.id)"
                            class="ml-auto size-4 shrink-0"
                        />
                    </button>
                </li>
            </ul>
        </PopoverContent>
    </Popover>
</template>
