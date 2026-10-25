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
import {
	Popover,
	PopoverContent,
	PopoverTrigger,
} from '@/components/ui/popover';
import type { BoardStack } from '@/types';

const props = defineProps<{
	stacks: BoardStack[];
}>();

const { board, labels, members } = useBoard();
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
	<div class="flex items-center gap-2">
		<div class="relative w-40 sm:w-72">
			<label>
				<span class="sr-only">Search cards</span>
				<PhMagnifyingGlass
					class="pointer-events-none absolute top-1/2 left-2.5 z-10 size-4 -translate-y-1/2 opacity-70"
				/>
				<input
					ref="search"
					v-model="filter.text"
					type="search"
					placeholder="Search cards"
					aria-keyshortcuts="/"
					class="h-9 w-full rounded-[var(--radius-sheet)] bg-current/15 pr-9 pl-8 text-[0.9375rem] outline-hidden backdrop-blur-md transition-colors duration-(--dur-micro) placeholder:text-current/70 hover:bg-current/20 focus:bg-current/20 [&::-webkit-search-cancel-button]:hidden"
					@keydown.esc="escape"
				/>
			</label>
			<button
				v-if="active"
				type="button"
				class="absolute top-1/2 right-1.5 flex size-6 -translate-y-1/2 items-center justify-center rounded-full opacity-70 transition duration-(--dur-micro) hover:bg-current/15 hover:opacity-100 focus-visible:outline-2 focus-visible:outline-ring focus-visible:outline-solid"
				:aria-label="
					count > 0 ? 'Clear search and filters' : 'Clear search'
				"
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

		<!-- Like the ⋯ button: a tinted circle; a dot while filters are on. -->
		<Popover>
			<PopoverTrigger as-child>
				<button
					type="button"
					class="relative flex size-9 shrink-0 items-center justify-center rounded-full backdrop-blur-md transition-colors duration-(--dur-micro) hover:bg-current/25 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ring focus-visible:outline-solid data-[state=open]:bg-current/25"
					:class="count > 0 ? 'bg-current/25' : 'bg-current/15'"
					:aria-label="count > 0 ? `Filter, ${count} on` : 'Filter'"
				>
					<PhFunnelSimple class="size-[1.125rem]" />
					<span
						v-if="count > 0"
						class="absolute top-0 right-0 size-2.5 rounded-full bg-current ring-2 ring-background in-data-field:ring-field"
						aria-hidden="true"
					/>
					<span
						class="absolute top-1/2 left-1/2 size-[max(100%,3rem)] -translate-1/2 pointer-fine:hidden"
						aria-hidden="true"
					/>
				</button>
			</PopoverTrigger>
			<PopoverContent
				align="end"
				:data-field="board.color"
				class="max-h-(--reka-popover-content-available-height) w-80 overflow-y-auto p-2"
				:class="{ 'menu-glass': board.color }"
			>
				<p class="px-2 pt-1 pb-1 text-xs font-semibold">Members</p>
				<ul>
					<li>
						<button
							type="button"
							:class="row"
							:aria-pressed="filter.members.includes(me)"
							@click="toggle(filter.members, me)"
						>
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
							No members
							<PhCheck
								v-if="filter.members.includes(0)"
								class="ml-auto size-4 shrink-0"
							/>
						</button>
					</li>
				</ul>

				<p class="px-2 pt-4 pb-1 text-xs font-semibold">Labels</p>
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
								class="min-w-0 truncate rounded-[3px] bg-label px-1.5 py-1 text-xs leading-none font-medium text-label-ink"
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

				<p class="px-2 pt-4 pb-1 text-xs font-semibold">Due date</p>
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
