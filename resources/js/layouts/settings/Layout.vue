<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';
import { useCurrentUrl } from '@/composables/useCurrentUrl';
import { toUrl } from '@/lib/utils';
import { edit as editAppearance } from '@/routes/appearance';
import { edit as editProfile } from '@/routes/profile';
import { edit as editSecurity } from '@/routes/security';
import type { NavItem } from '@/types';

const sidebarNavItems: NavItem[] = [
	{
		title: 'Profile',
		href: editProfile(),
	},
	{
		title: 'Security',
		href: editSecurity(),
	},
	{
		title: 'Appearance',
		href: editAppearance(),
	},
];

const { isCurrentOrParentUrl } = useCurrentUrl();
</script>

<template>
	<div class="px-4 pt-8 pb-16 md:px-8">
		<Heading
			title="Settings"
			description="Manage your profile and account settings"
		/>

		<div class="grid gap-8 lg:grid-cols-[12rem_minmax(0,1fr)] lg:gap-16">
			<nav
				class="flex gap-1 self-start overflow-x-auto border-border shadow-[inset_0_-2px_0_var(--color-border)] lg:flex-col lg:gap-0 lg:overflow-visible lg:border-l-2 lg:shadow-none"
				aria-label="Settings"
			>
				<Link
					v-for="item in sidebarNavItems"
					:key="toUrl(item.href)"
					:href="item.href"
					:aria-current="
						isCurrentOrParentUrl(item.href) ? 'page' : undefined
					"
					class="shrink-0 border-b-2 border-transparent px-3 py-2.5 text-muted-foreground hover:text-foreground aria-[current=page]:border-foreground aria-[current=page]:font-semibold aria-[current=page]:text-foreground lg:-ml-0.5 lg:border-b-0 lg:border-l-2 lg:px-4"
				>
					{{ item.title }}
				</Link>
			</nav>

			<section class="max-w-xl space-y-14">
				<slot />
			</section>
		</div>
	</div>
</template>
