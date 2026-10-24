<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { PhBell } from '@phosphor-icons/vue';
import { computed, ref } from 'vue';
import NotificationsDialog from '@/components/NotificationsDialog.vue';
import {
	DropdownMenu,
	DropdownMenuContent,
	DropdownMenuItem,
	DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import UserMenuContent from '@/components/UserMenuContent.vue';
import { getInitials } from '@/composables/useInitials';

// Off the boards, one menu in the top-right corner holds the account and
// the notifications. A yellow dot on your initials says there's something
// unread. Boards keep the ⋯ menu and the avatar in the corner.
const page = usePage();
const user = computed(() => page.props.auth.user);
const unread = computed(() => page.props.notifications?.unread ?? 0);

const notificationsOpen = ref(false);
</script>

<template>
	<DropdownMenu>
		<DropdownMenuTrigger as-child>
			<button
				type="button"
				class="relative flex size-9 items-center justify-center rounded-full bg-current/15 text-sm font-semibold transition-colors duration-(--dur-micro) hover:bg-current/25 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ring focus-visible:outline-solid data-[state=open]:bg-current/25"
				:aria-label="
					unread > 0
						? `Account menu for ${user.name}, ${unread} unread notifications`
						: `Account menu for ${user.name}`
				"
				data-test="user-menu-button"
			>
				{{ getInitials(user.name) }}
				<span
					v-if="unread > 0"
					class="absolute top-0 right-0 size-2.5 rounded-full bg-yellow-300 ring-2 ring-background"
					aria-hidden="true"
				/>
				<span
					class="absolute top-1/2 left-1/2 size-[max(100%,3rem)] -translate-1/2 pointer-fine:hidden"
					aria-hidden="true"
				/>
			</button>
		</DropdownMenuTrigger>
		<DropdownMenuContent align="end" class="w-64">
			<UserMenuContent :user="user">
				<DropdownMenuItem @select="notificationsOpen = true">
					<PhBell class="mr-2 h-4 w-4" />
					Notifications
					<span
						v-if="unread > 0"
						class="num ml-auto flex h-5 min-w-5 items-center justify-center rounded-full bg-yellow-300 px-1.5 text-xs font-semibold text-black"
					>
						{{ unread > 9 ? '9+' : unread }}
					</span>
				</DropdownMenuItem>
			</UserMenuContent>
		</DropdownMenuContent>
	</DropdownMenu>
	<NotificationsDialog v-model:open="notificationsOpen" />
</template>
