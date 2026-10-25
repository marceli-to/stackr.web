<script setup lang="ts">
import { Link, router, usePage } from '@inertiajs/vue3';
import { PhBell, PhDotsThree, PhGearSix, PhSignOut } from '@phosphor-icons/vue';
import { computed, ref } from 'vue';
import NotificationsDialog from '@/components/NotificationsDialog.vue';
import {
	DropdownMenu,
	DropdownMenuContent,
	DropdownMenuItem,
	DropdownMenuLabel,
	DropdownMenuSeparator,
	DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import UserInfo from '@/components/UserInfo.vue';
import { logout } from '@/routes';
import { edit as editProfile } from '@/routes/profile';
import type { Board } from '@/types';

// The ⋯ menu at the end of the top bar: notifications, whatever the
// board adds below them (view, people, archive), and your account last.
// A yellow dot on the button says there's something unread. On a board
// it's frosted glass of the board's colour.
const page = usePage();
const user = computed(() => page.props.auth.user);
const unread = computed(() => page.props.notifications?.unread ?? 0);
const field = computed(() => (page.props.board as Board | undefined)?.color);

const notificationsOpen = ref(false);
</script>

<template>
	<DropdownMenu>
		<DropdownMenuTrigger as-child>
			<button
				type="button"
				class="relative flex size-9 items-center justify-center rounded-full bg-current/15 transition-colors duration-(--dur-micro) hover:bg-current/25 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ring focus-visible:outline-solid data-[state=open]:bg-current/25"
				:aria-label="
					unread > 0 ? `Menu, ${unread} unread notifications` : 'Menu'
				"
			>
				<PhDotsThree weight="bold" class="size-5" />
				<span
					v-if="unread > 0"
					class="absolute top-0 right-0 size-2.5 rounded-full bg-yellow-300 ring-2 ring-background in-data-field:ring-field"
					aria-hidden="true"
				/>
				<span
					class="absolute top-1/2 left-1/2 size-[max(100%,3rem)] -translate-1/2 pointer-fine:hidden"
					aria-hidden="true"
				/>
			</button>
		</DropdownMenuTrigger>
		<DropdownMenuContent
			align="end"
			:data-field="field"
			class="w-60"
			:class="{ 'menu-glass': field }"
		>
			<DropdownMenuItem @select="notificationsOpen = true">
				<PhBell />
				Notifications
				<span
					v-if="unread > 0"
					class="num ml-auto flex h-5 min-w-5 items-center justify-center rounded-full bg-yellow-300 px-1.5 text-xs font-medium text-black"
				>
					{{ unread > 9 ? '9+' : unread }}
				</span>
			</DropdownMenuItem>
			<template v-if="$slots.default">
				<DropdownMenuSeparator />
				<slot />
			</template>
			<DropdownMenuSeparator />
			<DropdownMenuLabel class="px-2.5 py-2 font-normal">
				<UserInfo :user="user" :show-email="true" />
			</DropdownMenuLabel>
			<DropdownMenuItem :as-child="true">
				<Link
					class="block w-full cursor-pointer"
					:href="editProfile()"
					prefetch
				>
					<PhGearSix />
					Settings
				</Link>
			</DropdownMenuItem>
			<DropdownMenuItem :as-child="true">
				<Link
					class="block w-full cursor-pointer"
					:href="logout()"
					as="button"
					data-test="logout-button"
					@click="router.flushAll()"
				>
					<PhSignOut />
					Log out
				</Link>
			</DropdownMenuItem>
		</DropdownMenuContent>
	</DropdownMenu>
	<NotificationsDialog v-model:open="notificationsOpen" />
</template>
