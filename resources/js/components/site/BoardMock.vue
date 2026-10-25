<script setup lang="ts">
import {
	PhCalendarBlank,
	PhChatCircle,
	PhCheckSquare,
	PhPaperclip,
	PhPlus,
	PhTextAlignLeft,
} from '@phosphor-icons/vue';
import AppLogoIcon from '@/components/AppLogoIcon.vue';

// A static picture of a board, drawn with the same pieces as the real one.
type MockCard = {
	title: string;
	labels?: { color: string; name?: string }[];
	due?: { label: string; state?: 'soon' | 'done' };
	description?: boolean;
	comments?: number;
	files?: number;
	checklist?: [number, number];
	members?: string[];
	cover?: boolean;
};

const lists: { name: string; cards: MockCard[] }[] = [
	{
		name: 'To do',
		cards: [
			{
				title: 'Write the copy for the new homepage',
				labels: [{ color: 'purple', name: 'Content' }],
				description: true,
				members: ['AK'],
			},
			{
				title: 'Collect photos from the team day',
				files: 3,
				comments: 2,
			},
			{
				title: 'Ask the printer for a quote',
				labels: [{ color: 'yellow' }],
				due: { label: '12 Nov' },
			},
		],
	},
	{
		name: 'Doing',
		cards: [
			{
				title: 'Design the board tiles',
				cover: true,
				labels: [{ color: 'blue', name: 'Design' }],
				due: { label: 'Tomorrow', state: 'soon' },
				comments: 5,
				members: ['MS', 'LB'],
			},
			{
				title: 'Move the site to the new host',
				checklist: [3, 5],
				description: true,
				members: ['MS'],
			},
		],
	},
	{
		name: 'Done',
		cards: [
			{
				title: 'Pick a name',
				labels: [{ color: 'green', name: 'Shipped' }],
				due: { label: '20 Oct', state: 'done' },
			},
			{
				title: 'Draw the logo',
				checklist: [4, 4],
				files: 1,
			},
		],
	},
];
</script>

<template>
	<div
		role="img"
		aria-label="A Stackr board on a cobalt background with three lists, To do, Doing and Done, holding cards with labels, due dates, checklists and avatars."
		class="overflow-hidden rounded-[var(--radius-tile)] text-left shadow-[0_32px_64px_-24px_oklch(15%_0.03_262/0.45)] ring-1 ring-(--surface-edge)"
	>
		<!-- Window bar -->
		<div class="flex items-center gap-1.5 bg-(--color-paper-2) px-4 py-3">
			<span class="size-2.5 rounded-full bg-(--color-paper-3)" />
			<span class="size-2.5 rounded-full bg-(--color-paper-3)" />
			<span class="size-2.5 rounded-full bg-(--color-paper-3)" />
		</div>

		<div data-field="cobalt" class="field-fill text-field-ink">
			<!-- Top bar -->
			<div
				class="flex items-center justify-between border-b border-white/10 px-5 py-3"
			>
				<div class="flex items-center gap-2.5">
					<AppLogoIcon class-name="size-5" />
					<span
						class="font-display text-xl font-bold tracking-(--tracking-display)"
						>Website relaunch</span
					>
				</div>
				<div class="flex -space-x-1.5">
					<span
						v-for="initials in ['AK', 'LB']"
						:key="initials"
						class="flex size-7 items-center justify-center rounded-full bg-white/15 text-[0.6875rem] font-medium ring-2 ring-(--field)"
						>{{ initials }}</span
					>
				</div>
			</div>

			<!-- Lists -->
			<div
				class="flex items-start gap-3 overflow-hidden p-4 sm:gap-4 sm:p-5"
			>
				<section
					v-for="(list, index) in lists"
					:key="list.name"
					class="w-64 shrink-0 rounded-[var(--radius-tile)] bg-field-well pb-2.5"
					:class="index === 2 ? 'max-md:hidden' : ''"
				>
					<p
						class="px-4 pt-2.5 pb-2 font-display text-2xl leading-tight font-bold"
					>
						{{ list.name }}
					</p>
					<ul class="flex flex-col gap-2.5 px-2.5">
						<li
							v-for="card in list.cards"
							:key="card.title"
							class="overflow-hidden rounded-[var(--radius-sheet)] bg-sheet text-[0.9375rem] leading-snug text-(--color-ink)"
						>
							<div
								v-if="card.cover"
								class="h-20 bg-[linear-gradient(135deg,var(--color-label-sky),var(--color-label-blue)_55%,var(--color-label-purple))]"
							/>
							<div class="px-3 py-2.5">
								<ul
									v-if="card.labels"
									class="mb-2 flex flex-wrap gap-1"
								>
									<li
										v-for="label in card.labels"
										:key="label.color"
										:data-label="label.color"
										class="rounded-[3px] bg-label text-xs leading-none font-medium text-label-ink"
										:class="
											label.name
												? 'px-1.5 py-1'
												: 'h-2 w-10'
										"
									>
										{{ label.name }}
									</li>
								</ul>
								<p class="font-medium">{{ card.title }}</p>
								<div
									v-if="
										card.due ||
										card.description ||
										card.comments ||
										card.files ||
										card.checklist ||
										card.members
									"
									class="mt-2 flex flex-wrap items-center gap-x-3 gap-y-1.5 text-sm text-(--color-ink-3)"
								>
									<span
										v-if="card.due"
										class="num inline-flex items-center gap-1 rounded-[3px] py-0.5"
										:class="{
											'bg-(--color-label-yellow) px-1.5 text-label-ink':
												card.due.state === 'soon',
											'bg-(--color-success) px-1.5 text-white':
												card.due.state === 'done',
										}"
									>
										<PhCalendarBlank
											weight="regular"
											class="size-3.5"
										/>
										{{ card.due.label }}
									</span>
									<PhTextAlignLeft
										v-if="card.description"
										weight="regular"
										class="size-4"
									/>
									<span
										v-if="card.comments"
										class="num inline-flex items-center gap-1"
									>
										<PhChatCircle
											weight="regular"
											class="size-4"
										/>
										{{ card.comments }}
									</span>
									<span
										v-if="card.files"
										class="num inline-flex items-center gap-1"
									>
										<PhPaperclip
											weight="regular"
											class="size-4"
										/>
										{{ card.files }}
									</span>
									<span
										v-if="card.checklist"
										class="num inline-flex items-center gap-1 rounded-[3px] py-0.5"
										:class="
											card.checklist[0] ===
											card.checklist[1]
												? 'bg-(--color-success) px-1.5 text-white'
												: ''
										"
									>
										<PhCheckSquare
											weight="regular"
											class="size-4"
										/>
										{{ card.checklist[0] }}/{{
											card.checklist[1]
										}}
									</span>
									<span
										v-if="card.members"
										class="ml-auto flex -space-x-1.5"
									>
										<span
											v-for="initials in card.members"
											:key="initials"
											class="flex size-6 items-center justify-center rounded-full bg-(--color-paper-3) text-[0.6875rem] font-medium text-(--color-ink) ring-2 ring-sheet"
											>{{ initials }}</span
										>
									</span>
								</div>
							</div>
						</li>
					</ul>
					<p
						class="mx-2.5 mt-2.5 flex items-center gap-2 rounded-[var(--radius-sheet)] bg-white/8 px-3 py-2 text-[0.9375rem] text-field-ink/80"
					>
						<PhPlus class="size-4" />
						Add a card
					</p>
				</section>

				<p
					class="flex w-56 shrink-0 items-center gap-2 rounded-[var(--radius-tile)] bg-field-well px-4 py-3 text-[0.9375rem] max-lg:hidden"
				>
					<PhPlus class="size-4" />
					Add a list
				</p>
			</div>
		</div>
	</div>
</template>
