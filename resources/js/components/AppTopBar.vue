<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import AccountMenu from '@/components/AccountMenu.vue';
import ProjectMembers from '@/components/projects/ProjectMembers.vue';
import { index } from '@/routes/projects';
import type { Project } from '@/types';

const page = usePage();

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
</template>
