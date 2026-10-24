<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import SiteFooter from '@/components/site/SiteFooter.vue';
import SiteHeader from '@/components/site/SiteHeader.vue';

const repository = 'https://github.com/marceli-to/stackr.web';
const link =
	'font-semibold text-(--color-ink) underline decoration-(--control-edge) underline-offset-2 hover:decoration-current';
</script>

<template>
	<Head title="Legal notice" />

	<div class="min-h-svh bg-sheet text-(--color-ink)">
		<div
			class="pointer-events-none absolute inset-x-0 top-0 h-[44rem] bg-linear-to-b from-(--color-paper-2) to-(--color-sheet)"
		/>

		<div class="relative">
			<SiteHeader />

			<main
				class="mx-auto max-w-6xl px-6 pt-10 pb-24 sm:pt-16 sm:pb-32 lg:px-8"
			>
				<p
					class="text-base/7 font-semibold text-(--color-field-cobalt) dark:text-(--color-label-blue)"
				>
					Legal notice
				</p>
				<h1
					class="mt-2 max-w-[30ch] font-display text-5xl font-bold tracking-(--tracking-display) text-balance sm:text-6xl"
				>
					The small print
				</h1>
				<p
					class="mt-6 max-w-[48ch] text-lg/8 text-pretty text-(--color-ink-2)"
				>
					Stackr is a free board for small teams. Here’s what happens
					with what you put in, and what you can expect from us.
				</p>

				<div
					class="mt-16 divide-y divide-(--surface-edge) border-t border-(--surface-edge)"
				>
					<section
						id="data"
						class="grid scroll-mt-8 grid-cols-1 gap-x-12 gap-y-4 py-12 sm:grid-cols-[12rem_1fr]"
					>
						<h2
							class="font-display text-3xl font-bold tracking-(--tracking-display)"
						>
							Your data
						</h2>
						<div
							class="max-w-[65ch] space-y-4 text-base/7 text-pretty text-(--color-ink-2)"
						>
							<p>
								For your account we store your name, your email
								address and your password, which is only kept as
								a hash. Everything you put on a board, like
								lists, cards, comments and files, is stored so
								you and your project’s members can see it.
							</p>
							<p>
								Only the members of a project can see its board
								and open its files. Files aren’t public: they’re
								handed out by the app, and only to members.
							</p>
						</div>
					</section>

					<section
						id="privacy"
						class="grid scroll-mt-8 grid-cols-1 gap-x-12 gap-y-4 py-12 sm:grid-cols-[12rem_1fr]"
					>
						<h2
							class="font-display text-3xl font-bold tracking-(--tracking-display)"
						>
							Privacy
						</h2>
						<div
							class="max-w-[65ch] space-y-4 text-base/7 text-pretty text-(--color-ink-2)"
						>
							<p>
								Stackr has no ads and no analytics, and it loads
								nothing from other servers: even the fonts come
								from our own. It sets the cookies it needs to
								work: one for your session, one that protects
								forms, and one that remembers whether you chose
								the light or dark look.
							</p>
							<p>
								Stackr runs at
								<a href="https://www.hostpoint.ch" :class="link"
									>Hostpoint</a
								>
								in Switzerland, who keep the usual server logs.
								Emails, like invitations and password resets,
								are sent through Hostpoint’s mail server. A copy
								of the database is made every night and kept for
								30 days.
							</p>
							<p>
								We don’t sell or share your data, and we only
								look at it to fix a problem you report.
							</p>
						</div>
					</section>

					<section
						id="deleting"
						class="grid scroll-mt-8 grid-cols-1 gap-x-12 gap-y-4 py-12 sm:grid-cols-[12rem_1fr]"
					>
						<h2
							class="font-display text-3xl font-bold tracking-(--tracking-display)"
						>
							Leaving
						</h2>
						<div
							class="max-w-[65ch] space-y-4 text-base/7 text-pretty text-(--color-ink-2)"
						>
							<p>
								You can delete your account any time under
								Settings, Profile. That deletes your account and
								every project you own, with its cards and files.
								In other people’s projects, your comments and
								files stay without your name. Nightly copies of
								the database are gone after 30 days.
							</p>
						</div>
					</section>

					<section
						id="warranty"
						class="grid scroll-mt-8 grid-cols-1 gap-x-12 gap-y-4 py-12 sm:grid-cols-[12rem_1fr]"
					>
						<h2
							class="font-display text-3xl font-bold tracking-(--tracking-display)"
						>
							No warranty
						</h2>
						<div
							class="max-w-[65ch] space-y-4 text-base/7 text-pretty text-(--color-ink-2)"
						>
							<p>
								Stackr is provided free of charge and “as is”,
								without any warranty. We do our best to keep it
								running and your data safe, but keep your own
								copy of anything you can’t lose. To the extent
								the law allows, we’re not liable for any damage
								or loss from using it.
							</p>
						</div>
					</section>

					<section
						id="contact"
						class="grid scroll-mt-8 grid-cols-1 gap-x-12 gap-y-4 py-12 sm:grid-cols-[12rem_1fr]"
					>
						<h2
							class="font-display text-3xl font-bold tracking-(--tracking-display)"
						>
							Contact
						</h2>
						<div
							class="max-w-[65ch] space-y-4 text-base/7 text-pretty text-(--color-ink-2)"
						>
							<p>
								Stackr is made by
								<a href="https://kimiyu.ch" :class="link"
									>kimiyu.ch</a
								>. For questions or problems, please
								<a :href="`${repository}/issues`" :class="link"
									>open an issue on GitHub</a
								>.
							</p>
						</div>
					</section>
				</div>
			</main>

			<SiteFooter />
		</div>
	</div>
</template>
