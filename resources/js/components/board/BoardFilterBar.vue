<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import {
	PhCheck,
	PhFunnelSimple,
	PhMagnifyingGlass,
	PhX,
} from '@phosphor-icons/vue';
import { onKeyStroke } from '@vueuse/core';
import { computed, useTemplateRef } from 'vue';
import { dueFilters, useFilter } from '@/components/board/filter';
import { busy } from '@/components/board/useBoardKeys';
import { colorNames, useBoard } from '@/components/card/board';
import { Button } from '@/components/ui/button';
import {
	Popover,
	PopoverContent,
	PopoverTrigger,
} from '@/components/ui/popover';
import { getInitials } from '@/composables/useInitials';
import type { BoardStack } from '@/types';

// The filter popover is switched off for now; search, the count and the
// filter state itself keep working.
const props = defineProps<{
	stacks: BoardStack[];
	withFilters?: boolean;
}>();

const { labels, members } = useBoard();
const { filter, count, active, shows, toggle, clear } = useFilter();

const page = usePage();
const user = computed(() => page.props.auth.user);
const me = computed(() => user.value.id);
const others = computed(() =>
	members.value.filter((member) => member.id !== me.value),
);

const cards = computed(() => props.stacks.flatMap((stack) => stack.cards));
const shown = computed(() => cards.value.filter(shows).length);

const search = useTemplateRef('search');

// "/" jumps to the search, unless you're already typing somewhere.
onKeyStroke('/', (event) => {
	if (busy(event)) {
		return;
	}

	event.preventDefault();
	search.value?.focus();
});

function escape() {
	filter.value.text = '';
	search.value?.blur();
}

const row =
	'flex w-full items-center gap-2.5 rounded-[var(--radius-sheet)] px-2 py-1.5 text-left hover:bg-(--menu-hover) focus-visible:outline-2 focus-visible:outline-ring focus-visible:outline-solid';
</script>

<template>
	<div class="flex items-center">
		<div class="relative w-40 sm:w-72">
			<label>
				<span class="sr-only">Search cards</span>
				<PhMagnifyingGlass
					class="pointer-events-none absolute top-1/2 left-2.5 size-4 -translate-y-1/2 opacity-70"
				/>
				<input
					ref="search"
					v-model="filter.text"
					type="search"
					placeholder="Search cards"
					aria-keyshortcuts="/"
					class="h-9 w-full rounded-[var(--radius-sheet)] bg-current/10 pr-9 pl-8 text-[0.9375rem] outline-hidden transition-colors duration-(--dur-micro) placeholder:text-current/60 hover:bg-current/15 focus:bg-current/15 [&::-webkit-search-cancel-button]:hidden"
					@keydown.esc="escape"
				/>
			</label>
			<button
				v-if="active"
				type="button"
				class="absolute top-1/2 right-1.5 flex size-6 -translate-y-1/2 items-center justify-center rounded-full opacity-70 transition duration-(--dur-micro) hover:bg-current/15 hover:opacity-100 focus-visible:outline-2 focus-visible:outline-ring focus-visible:outline-solid"
				aria-label="Clear search"
				@click="clear"
			>
				<PhX class="size-4" />
				<span
					class="absolute top-1/2 left-1/2 size-[max(100%,3rem)] -translate-1/2 pointer-fine:hidden"
					aria-hidden="true"
				/>
			</button>
			<p class="sr-only" aria-live="polite">
				<template v-if="active">
					{{ shown }} of {{ cards.length }}
					{{ cards.length === 1 ? 'card' : 'cards' }}
				</template>
			</p>
		</div>

		<Popover v-if="withFilters">
			<PopoverTrigger as-child>
				<Button
					variant="ghost"
					class="h-9 text-inherit hover:bg-current/10 hover:text-inherit data-[state=open]:bg-current/10"
					:class="{ 'bg-current/10': count > 0 }"
				>
					<PhFunnelSimple />
					Filter
					<span
						v-if="count > 0"
						class="num -mr-1 flex size-5 items-center justify-center rounded-full bg-current/20 text-xs"
					>
						{{ count }}
						<span class="sr-only">active</span>
					</span>
				</Button>
			</PopoverTrigger>
			<PopoverContent
				class="max-h-(--reka-popover-content-available-height) w-80 overflow-y-auto p-2"
			>
				<p class="px-2 pt-1 pb-1.5 font-semibold">Members</p>
				<ul>
					<li>
						<button
							type="button"
							:class="row"
							:aria-pressed="filter.members.includes(me)"
							@click="toggle(filter.members, me)"
						>
							<span
								class="flex size-7 shrink-0 items-center justify-center rounded-full bg-(--color-paper-3) text-xs font-semibold"
								aria-hidden="true"
							>
								{{ getInitials(user.name) }}
							</span>
							Cards assigned to me
							<PhCheck
								v-if="filter.members.includes(me)"
								class="ml-auto size-4 shrink-0"
							/>
						</button>
					</li>
					<li v-for="member in others" :key="member.id">
						<button
							type="button"
							:class="row"
							:aria-pressed="filter.members.includes(member.id)"
							@click="toggle(filter.members, member.id)"
						>
							<span
								class="flex size-7 shrink-0 items-center justify-center rounded-full bg-(--color-paper-3) text-xs font-semibold"
								aria-hidden="true"
							>
								{{ getInitials(member.name) }}
							</span>
							<span class="truncate">{{ member.name }}</span>
							<PhCheck
								v-if="filter.members.includes(member.id)"
								class="ml-auto size-4 shrink-0"
							/>
						</button>
					</li>
					<li>
						<button
							type="button"
							:class="row"
							:aria-pressed="filter.members.includes(0)"
							@click="toggle(filter.members, 0)"
						>
							<span class="w-7 shrink-0" aria-hidden="true" />
							No members
							<PhCheck
								v-if="filter.members.includes(0)"
								class="ml-auto size-4 shrink-0"
							/>
						</button>
					</li>
				</ul>

				<p class="px-2 pt-4 pb-1.5 font-semibold">Labels</p>
				<ul>
					<li v-for="label in labels" :key="label.id">
						<button
							type="button"
							:class="row"
							:aria-pressed="filter.labels.includes(label.id)"
							@click="toggle(filter.labels, label.id)"
						>
							<span
								:data-label="label.color"
								class="min-w-0 truncate rounded-[3px] bg-label px-1.5 py-1 text-xs leading-none font-semibold text-label-ink"
							>
								{{ label.name || colorNames[label.color] }}
							</span>
							<PhCheck
								v-if="filter.labels.includes(label.id)"
								class="ml-auto size-4 shrink-0"
							/>
						</button>
					</li>
					<li>
						<button
							type="button"
							:class="row"
							:aria-pressed="filter.labels.includes(0)"
							@click="toggle(filter.labels, 0)"
						>
							No labels
							<PhCheck
								v-if="filter.labels.includes(0)"
								class="ml-auto size-4 shrink-0"
							/>
						</button>
					</li>
				</ul>

				<p class="px-2 pt-4 pb-1.5 font-semibold">Due date</p>
				<ul>
					<li v-for="due in dueFilters" :key="due.value">
						<button
							type="button"
							:class="row"
							:aria-pressed="filter.due.includes(due.value)"
							@click="toggle(filter.due, due.value)"
						>
							{{ due.label }}
							<PhCheck
								v-if="filter.due.includes(due.value)"
								class="ml-auto size-4 shrink-0"
							/>
						</button>
					</li>
				</ul>
			</PopoverContent>
		</Popover>
	</div>
</template>
