<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import AccountMenu from '@/components/AccountMenu.vue';
import ProjectMembers from '@/components/projects/ProjectMembers.vue';
import {
	DropdownMenu,
	DropdownMenuContent,
	DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import UserMenuContent from '@/components/UserMenuContent.vue';
import { getInitials } from '@/composables/useInitials';
import { index } from '@/routes/projects';
import type { Project } from '@/types';

const page = usePage();
const user = computed(() => page.props.auth.user);

// On a board the bar carries the project's name instead of the wordmark,
// and the board page teleports its search and menu into the bar.
const project = computed(
	() => page.props.project as Pick<Project, 'name' | 'color'> | undefined,
);
</script>

<template>
	<header
		class="grid h-14 shrink-0 grid-cols-[minmax(0,1fr)_auto_auto] items-center gap-2 border-b border-current/15 px-3 sm:grid-cols-[minmax(0,1fr)_auto_minmax(max-content,1fr)] md:px-5"
	>
		<div class="flex min-w-0 items-center gap-2">
			<Link
				:href="index()"
				class="mr-2 flex items-center gap-2 rounded-sm"
				aria-label="Stackr, all projects"
			>
				<AppLogoIcon class="size-5 fill-current" />
				<span
					v-if="!project"
					class="font-display text-xl leading-none font-bold"
				>
					{{ page.props.name }}
				</span>
			</Link>
			<span
				v-if="project"
				class="min-w-0 truncate font-display text-xl leading-normal font-bold"
			>
				{{ project.name }}
			</span>
		</div>

		<!-- The board's search, teleported in, sits in the middle. -->
		<div id="topbar-search" class="flex justify-center" />

		<div class="flex items-center justify-end gap-2">
			<ProjectMembers v-if="project" />
			<!-- A board teleports its own menu, with its actions, in here. -->
			<div v-if="project" id="topbar-actions" class="contents" />
			<AccountMenu v-else />
		</div>
	</header>

	<!-- On a board your account waits in the bottom-left corner, out of the
	board's way; elsewhere it's the menu in the top bar. -->
	<div v-if="project" class="fixed bottom-4 left-3 z-10 md:left-5">
		<DropdownMenu>
			<DropdownMenuTrigger as-child>
				<button
					type="button"
					class="relative flex size-9 items-center justify-center rounded-full bg-current/15 text-sm font-semibold transition-colors duration-(--dur-micro) hover:bg-current/25 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ring focus-visible:outline-solid data-[state=open]:bg-current/25"
					:aria-label="`Account menu for ${user.name}`"
					data-test="user-menu-button"
				>
					{{ getInitials(user.name) }}
					<span
						class="absolute top-1/2 left-1/2 size-[max(100%,3rem)] -translate-1/2 pointer-fine:hidden"
						aria-hidden="true"
					/>
				</button>
			</DropdownMenuTrigger>
			<!-- Frosted in the project's colour, like the ⋯ menu. -->
			<DropdownMenuContent
				side="top"
				align="start"
				:data-field="project.color"
				class="w-64 menu-glass"
			>
				<UserMenuContent :user="user" />
			</DropdownMenuContent>
		</DropdownMenu>
	</div>
</template>
