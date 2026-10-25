<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import {
	PhArchive,
	PhArrowRight,
	PhBell,
	PhCalendarBlank,
	PhCalendarCheck,
	PhChatCircleText,
	PhCheck,
	PhCheckSquare,
	PhClockCounterClockwise,
	PhCopySimple,
	PhGauge,
	PhHandGrabbing,
	PhLink,
	PhMagnifyingGlass,
	PhPaintBrush,
	PhPaperclip,
	PhTag,
	PhUsersThree,
	PhX,
} from '@phosphor-icons/vue';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import BoardMock from '@/components/site/BoardMock.vue';
import SiteFooter from '@/components/site/SiteFooter.vue';
import SiteHeader from '@/components/site/SiteHeader.vue';
import { login, register } from '@/routes';
import { boardColors, solidBoardColors } from '@/types';

const repository = 'https://github.com/marceli-to/stackr.web';

const features = [
	{
		icon: PhHandGrabbing,
		title: 'Drag and drop',
		body: 'Move cards from list to list and lists across the board. The board moves right away and saves in the background.',
	},
	{
		icon: PhUsersThree,
		title: 'Invite your people',
		body: 'Invite by email, or turn on a join link and share it. People with an account join right away; everyone else signs up first.',
	},
	{
		icon: PhCalendarCheck,
		title: 'Dates that nudge',
		body: 'Give a card a start and a due date. Cards due soon or overdue stand out, and the people on a card get a reminder the day before.',
	},
	{
		icon: PhTag,
		title: 'Labels',
		body: 'Eight colours per board, with or without a name, to sort the cards at a glance.',
	},
	{
		icon: PhCheckSquare,
		title: 'Checklists',
		body: 'Break a card into steps and tick them off. The board shows how many are done, and a new checklist can copy one you already have.',
	},
	{
		icon: PhPaperclip,
		title: 'Files and covers',
		body: 'Drop files on a card or paste a screenshot, up to 20 MB each. The first image becomes the card’s cover, or pick a colour instead.',
	},
	{
		icon: PhChatCircleText,
		title: 'Notes and comments',
		body: 'Write the description in Markdown and talk about the work right on the card, not in a separate chat.',
	},
	{
		icon: PhLink,
		title: 'A link for every card',
		body: 'Every card has its own address. Send it, and it opens right on the board; the back button closes it again.',
	},
	{
		icon: PhMagnifyingGlass,
		title: 'Search and shortcuts',
		body: 'Press / and type: the board narrows to the cards that match. n adds a card, the arrow keys move between cards, and ? lists the rest.',
	},
	{
		icon: PhCalendarBlank,
		title: 'Calendar',
		body: 'Switch a board to a month of due dates. Drag a card to another day to move its date.',
	},
	{
		icon: PhBell,
		title: 'Notifications and mentions',
		body: 'Mention someone with @ and they hear about it, in the app and by email if they like. Watch a card or a whole board to follow along.',
	},
	{
		icon: PhCopySimple,
		title: 'Templates',
		body: 'Turn a card you make again and again into a template, and start new ones from it with its checklists, labels and files.',
	},
	{
		icon: PhClockCounterClockwise,
		title: 'A history on every card',
		body: 'Each card keeps a log of what happened to it and who did it, between the comments.',
	},
	{
		icon: PhArchive,
		title: 'Archive, move, copy',
		body: 'Archive cards and lists instead of deleting them, and bring them back later. Move or copy a card, even onto another board.',
	},
	{
		icon: PhGauge,
		title: 'Card limits',
		body: 'Give a list a limit to keep work in progress in check; it turns red when there’s too much. Fold away the lists you don’t need right now.',
	},
	{
		icon: PhPaintBrush,
		title: 'Make it yours',
		body: 'Eleven colours for your boards, five of them gradients, or a photo of your own behind the board.',
	},
];

// What an agent may and may not do, for the agents section.
const agentCan = [
	'Read your boards, cards, checklists and comments',
	'Create cards and edit them',
	'Move cards from list to list',
	'Tick off checklist items and add new ones',
	'Comment, assign members and set labels',
	'Archive cards',
];

const agentCannot = [
	'Delete anything',
	'Change a board’s settings',
	'Invite or remove people',
];

const agentLog = [
	'moved this from To do to Doing',
	'ticked off “Draft the copy”',
	'commented',
	'moved this from Doing to Done',
];

const facts = [
	{ label: 'Ads and tracking', value: 'None' },
	{ label: 'Price', value: 'Free' },
	{ label: 'Boards and members', value: 'Unlimited' },
	{ label: 'Hosted in', value: 'Switzerland' },
];
</script>

<template>
	<Head>
		<title>A board for every project</title>
		<meta
			name="description"
			content="Stackr is a small kanban app for small teams: every board gets its own colour, its own lists and cards, and AI agents can work them too."
		/>
	</Head>

	<div class="bg-sheet text-(--color-ink)" data-smooth-scroll>
		<div
			class="pointer-events-none absolute inset-x-0 top-0 h-[44rem] bg-linear-to-b from-(--color-paper-2) to-(--color-sheet)"
		/>

		<div class="relative">
			<SiteHeader />

			<main>
				<!-- Hero, with a board -->
				<section class="pt-10 pb-24 sm:pt-16 sm:pb-32">
					<div class="mx-auto max-w-6xl px-6 text-center lg:px-8">
						<div
							class="mx-auto flex size-28 items-center justify-center rounded-[1.75rem] bg-[oklch(18%_0.012_262)] text-(--color-on-dark) shadow-[0_20px_40px_-16px_oklch(15%_0.02_262/0.5)] [--logo-1:var(--color-label-sky)] [--logo-2:var(--color-label-yellow)] [--logo-3:var(--color-on-dark)] sm:size-32 sm:rounded-[2rem] dark:ring-1 dark:ring-white/10"
						>
							<AppLogoIcon
								class-name="size-16 sm:size-20"
								colored
							/>
						</div>
						<h1
							class="mx-auto mt-8 max-w-[14ch] font-display text-6xl leading-[1.02] font-bold tracking-(--tracking-display) text-balance sm:text-8xl"
						>
							A board for every project
						</h1>
						<p
							class="mx-auto mt-6 max-w-[50ch] text-lg/7 text-pretty text-(--color-ink-2) sm:text-xl/7.5"
						>
							Stackr is a small kanban app for small teams. Every
							board gets its own colour and its own lists and
							cards. Drag the cards along as the work moves, and
							keep everything about it on the card.
						</p>
						<div
							class="mt-10 flex flex-wrap items-center justify-center gap-x-6 gap-y-4"
						>
							<Link
								:href="register()"
								class="rounded-[var(--radius-sheet)] bg-primary px-4 py-2.5 text-base font-medium text-primary-foreground hover:bg-primary/85 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-(--color-focus) focus-visible:outline-solid"
							>
								Create an account
							</Link>
							<Link
								:href="login()"
								class="inline-flex items-center gap-1.5 text-base font-medium hover:underline"
							>
								Log in
								<PhArrowRight class="size-4" />
							</Link>
						</div>
						<p class="mt-6 text-sm/5.5 text-(--color-ink-3)">
							Free, with no ads and no tracking. Hosted in
							Switzerland.
						</p>

						<BoardMock class="mx-auto mt-16 max-w-5xl sm:mt-20" />
					</div>
				</section>

				<!-- How it works -->
				<section
					id="how"
					class="scroll-mt-8 bg-[oklch(18%_0.012_262)] py-24 text-(--color-on-dark) sm:py-32 dark:bg-[oklch(12%_0.008_262)]"
				>
					<div class="mx-auto max-w-6xl px-6 lg:px-8">
						<p
							class="text-base/6 font-medium text-(--color-label-sky)"
						>
							How it works
						</p>
						<h2
							class="mt-2 max-w-[24ch] font-display text-5xl leading-[1.1] font-bold tracking-(--tracking-display) text-balance sm:text-6xl"
						>
							Boards, lists and cards
						</h2>
						<p
							class="mt-6 max-w-[52ch] text-lg/7 text-pretty text-white/70"
						>
							That’s the whole idea. A board is a project, a list
							is a step, and a card is a piece of work. Everything
							else lives on the card.
						</p>

						<div
							class="mt-16 grid grid-cols-1 gap-8 sm:mt-20 lg:grid-cols-3"
						>
							<!-- Boards -->
							<div
								class="flex flex-col rounded-[var(--radius-tile)] bg-white/5 p-8 ring-1 ring-white/10"
							>
								<div class="flex items-center gap-x-3">
									<span class="text-base/6 font-medium"
										>Boards</span
									>
									<span
										class="rounded-full px-2 text-sm/5.5 font-normal text-(--color-label-sky) ring-1 ring-(--color-label-sky)/50"
										>{{ boardColors.length }} colours</span
									>
								</div>
								<p
									class="mt-4 text-sm/5.5 font-medium text-white/90"
								>
									One board per project
								</p>
								<p
									class="mt-2 text-base/6 text-pretty text-white/70"
								>
									Give each board a name, a link and a colour,
									or a photo. The whole board stands on it, so
									you always know where you are.
								</p>
								<ul
									class="mt-auto grid grid-cols-3 gap-2 pt-8"
									aria-label="Six of the board colours"
								>
									<li
										v-for="color in solidBoardColors"
										:key="color"
										:data-field="color"
										class="flex aspect-[16/10] items-end rounded-[var(--radius-sheet)] field-fill p-2 text-xs font-medium capitalize"
									>
										{{ color }}
									</li>
								</ul>
							</div>

							<!-- Lists -->
							<div
								class="flex flex-col rounded-[var(--radius-tile)] bg-white/5 p-8 ring-1 ring-white/10"
							>
								<div class="flex items-center gap-x-3">
									<span class="text-base/6 font-medium"
										>Lists</span
									>
									<span
										class="rounded-full px-2 text-sm/5.5 font-normal text-(--color-label-sky) ring-1 ring-(--color-label-sky)/50"
										>Drag &amp; drop</span
									>
								</div>
								<p
									class="mt-4 text-sm/5.5 font-medium text-white/90"
								>
									One list per step
								</p>
								<p
									class="mt-2 text-base/6 text-pretty text-white/70"
								>
									To do, Doing, Done, or whatever your work
									needs. Put the lists in order and drag the
									cards from one to the next.
								</p>
								<div
									class="mt-auto flex items-start gap-2 pt-8"
									aria-hidden="true"
								>
									<div
										v-for="(name, index) in [
											'To do',
											'Doing',
											'Done',
										]"
										:key="name"
										class="flex-1 rounded-[var(--radius-sheet)] bg-white/8 p-2"
									>
										<p
											class="font-display text-base font-bold"
										>
											{{ name }}
										</p>
										<div
											v-for="n in 3 - index"
											:key="n"
											class="mt-1.5 h-4 rounded-[3px] bg-white/85"
										/>
									</div>
								</div>
							</div>

							<!-- Cards -->
							<div
								class="flex flex-col rounded-[var(--radius-tile)] bg-white/5 p-8 ring-1 ring-white/10"
							>
								<div class="flex items-center gap-x-3">
									<span class="text-base/6 font-medium"
										>Cards</span
									>
									<span
										class="rounded-full px-2 text-sm/5.5 font-normal text-(--color-label-sky) ring-1 ring-(--color-label-sky)/50"
										>Click to open</span
									>
								</div>
								<p
									class="mt-4 text-sm/5.5 font-medium text-white/90"
								>
									Everything about one task
								</p>
								<p
									class="mt-2 text-base/6 text-pretty text-white/70"
								>
									Open a card for its description, dates,
									labels, members, checklists, files and
									comments.
								</p>
								<div class="mt-auto pt-8" aria-hidden="true">
									<div
										class="rounded-[var(--radius-sheet)] bg-sheet px-3 py-2.5 text-[0.9375rem] leading-snug text-(--color-ink)"
									>
										<span
											data-label="orange"
											class="mb-2 inline-block rounded-[3px] bg-label px-1.5 py-1 text-xs leading-none font-medium text-label-ink"
											>Urgent</span
										>
										<p>Send the invoices for October</p>
										<div
											class="mt-2 flex items-center gap-3 text-sm text-(--color-ink-3)"
										>
											<span
												class="inline-flex items-center gap-1"
											>
												<PhCheckSquare
													weight="regular"
													class="size-4"
												/>
												1/4
											</span>
											<span
												class="inline-flex items-center gap-1"
											>
												<PhPaperclip
													weight="regular"
													class="size-4"
												/>
												2
											</span>
											<span
												class="ml-auto flex size-6 items-center justify-center rounded-full bg-(--color-paper-3) text-[0.6875rem] font-medium text-(--color-ink)"
												>MS</span
											>
										</div>
									</div>
								</div>
							</div>
						</div>

						<p
							class="mt-12 max-w-[60ch] text-base/6 text-pretty text-white/70"
						>
							Stackr has no realtime updates: what others change
							shows up when you reload the page. It keeps the app
							small and quick.
						</p>
					</div>
				</section>

				<!-- Features -->
				<section id="features" class="scroll-mt-8 py-24 sm:py-32">
					<div class="mx-auto max-w-6xl px-6 lg:px-8">
						<p
							class="text-base/6 font-medium text-(--color-field-cobalt) dark:text-(--color-label-blue)"
						>
							Features
						</p>
						<h2
							class="mt-2 max-w-[30ch] font-display text-5xl leading-[1.1] font-bold tracking-(--tracking-display) text-balance sm:text-6xl"
						>
							Everything you need,<br />
							nothing you don’t
						</h2>
						<p
							class="mt-6 max-w-[48ch] text-lg/7 text-pretty text-(--color-ink-2)"
						>
							No workspaces, no automations, no power-ups. Make a
							board, add a few lists and get going.
						</p>

						<dl
							class="mt-16 grid grid-cols-1 gap-x-12 gap-y-12 sm:grid-cols-2 lg:grid-cols-4"
						>
							<div
								v-for="feature in features"
								:key="feature.title"
							>
								<dt
									class="flex items-start gap-x-3 text-base/6 font-medium"
								>
									<component
										:is="feature.icon"
										class="size-5 h-lh shrink-0 text-(--color-field-cobalt) dark:text-(--color-label-blue)"
									/>
									{{ feature.title }}
								</dt>
								<dd
									class="mt-1 pl-8 text-base/6 text-pretty text-(--color-ink-2)"
								>
									{{ feature.body }}
								</dd>
							</div>
						</dl>
					</div>
				</section>

				<!-- Agents -->
				<section
					id="agents"
					class="scroll-mt-8 bg-[oklch(18%_0.012_262)] py-24 text-(--color-on-dark) sm:py-32 dark:bg-[oklch(12%_0.008_262)]"
				>
					<div
						class="mx-auto grid max-w-6xl grid-cols-1 gap-x-16 gap-y-16 px-6 lg:grid-cols-2 lg:px-8"
					>
						<div>
							<p
								class="text-base/6 font-medium text-(--color-label-sky)"
							>
								Agents
							</p>
							<h2
								class="mt-2 max-w-[20ch] font-display text-5xl leading-[1.1] font-bold tracking-(--tracking-display) text-balance sm:text-6xl"
							>
								Let an agent do the busywork
							</h2>
							<p
								class="mt-6 max-w-[48ch] text-lg/7 text-pretty text-white/70"
							>
								Stackr speaks MCP, the way AI agents like Claude
								Code use other tools. Create a token in your
								settings, paste one command, and your agent can
								work your boards: pick up a card, do the work,
								tick off the checklist, say what it did and move
								the card to Done.
							</p>
							<p
								class="mt-4 max-w-[48ch] text-base/6 text-pretty text-white/70"
							>
								It acts as you, only on your boards, and
								everything it does is marked “via agent” in the
								card’s history. Revoke the token and it’s out.
							</p>

							<div
								class="mt-10 grid grid-cols-1 gap-8 sm:grid-cols-2"
							>
								<div>
									<p
										class="text-sm/5.5 font-medium text-white/90"
									>
										It can
									</p>
									<ul class="mt-3 space-y-2">
										<li
											v-for="item in agentCan"
											:key="item"
											class="flex gap-x-2.5 text-sm/5.5 text-white/70"
										>
											<PhCheck
												class="size-4 h-lh shrink-0 text-(--color-label-sky)"
											/>
											{{ item }}
										</li>
									</ul>
								</div>
								<div>
									<p
										class="text-sm/5.5 font-medium text-white/90"
									>
										It can’t
									</p>
									<ul class="mt-3 space-y-2">
										<li
											v-for="item in agentCannot"
											:key="item"
											class="flex gap-x-2.5 text-sm/5.5 text-white/70"
										>
											<PhX
												class="size-4 h-lh shrink-0 text-white/40"
											/>
											{{ item }}
										</li>
									</ul>
								</div>
							</div>
						</div>

						<!-- A prompt, and what it leaves on the card -->
						<div
							class="flex flex-col justify-center gap-4 self-center"
							aria-hidden="true"
						>
							<div
								class="ml-auto max-w-[34ch] rounded-[var(--radius-tile)] rounded-br-[4px] bg-white/10 px-4 py-3 text-base/6 text-white/90"
							>
								Take the top card in To do on the Website board,
								do it, and move it to Done.
							</div>

							<div
								class="rounded-[var(--radius-tile)] bg-sheet p-5 text-(--color-ink)"
							>
								<span
									data-label="sky"
									class="inline-block rounded-[3px] bg-label px-1.5 py-1 text-xs leading-none font-medium text-label-ink"
									>Copy</span
								>
								<p class="mt-2 font-medium">
									Write the About page
								</p>
								<ol class="mt-4 space-y-3">
									<li
										v-for="(line, index) in agentLog"
										:key="line"
										class="flex gap-2.5 text-sm"
									>
										<span
											class="flex size-7 shrink-0 items-center justify-center rounded-full bg-(--color-paper-2) text-xs font-medium text-(--color-ink-3)"
											>MS</span
										>
										<p class="pt-1">
											<span class="font-medium"
												>Marcel</span
											>
											{{ line }}
											<span class="text-(--color-ink-3)"
												>{{ 4 - index }} min ago</span
											>
											<span
												class="whitespace-nowrap text-(--color-ink-3)/70"
											>
												· via agent</span
											>
										</p>
									</li>
								</ol>
							</div>
						</div>
					</div>
				</section>

				<!-- Under the hood -->
				<section class="bg-(--color-paper-2) py-24 sm:py-32">
					<div
						class="mx-auto grid max-w-6xl grid-cols-1 gap-x-12 gap-y-16 px-6 lg:grid-cols-2 lg:px-8"
					>
						<div>
							<p
								class="text-base/6 font-medium text-(--color-field-cobalt) dark:text-(--color-label-blue)"
							>
								Under the hood
							</p>
							<h2
								class="mt-2 max-w-[30ch] font-display text-5xl leading-[1.1] font-bold tracking-(--tracking-display) text-balance sm:text-6xl"
							>
								Small, simple, and out of your way
							</h2>
							<p
								class="mt-6 max-w-[48ch] text-lg/7 text-pretty text-(--color-ink-2)"
							>
								Stackr is built with Laravel and Vue and runs on
								a small server in Switzerland. Your boards are
								backed up every night, and your files stay
								private: only the members of a board can open
								them.
							</p>
						</div>
						<dl
							class="divide-y divide-(--surface-edge) self-center"
						>
							<div
								v-for="fact in facts"
								:key="fact.label"
								class="flex items-baseline justify-between gap-x-6 py-6 first:pt-0"
							>
								<dt class="text-base/6 text-(--color-ink-2)">
									{{ fact.label }}
								</dt>
								<dd
									class="font-display text-3xl font-bold tracking-(--tracking-display)"
								>
									{{ fact.value }}
								</dd>
							</div>
							<div
								class="flex items-baseline justify-between gap-x-6 pt-6"
							>
								<dt class="text-base/6 text-(--color-ink-2)">
									Source code
								</dt>
								<dd
									class="font-display text-3xl font-bold tracking-(--tracking-display)"
								>
									<a
										:href="repository"
										class="underline decoration-(--control-edge) underline-offset-4 hover:decoration-current"
										>GitHub</a
									>
								</dd>
							</div>
						</dl>
					</div>
				</section>
			</main>

			<SiteFooter />
		</div>
	</div>
</template>
