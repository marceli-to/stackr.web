<script setup lang="ts">
import { router, usePage } from '@inertiajs/vue3';
import { PhBell } from '@phosphor-icons/vue';
import { computed, watch } from 'vue';
import NotificationController from '@/actions/App/Http/Controllers/NotificationController';
import { timeAgo } from '@/components/card/board';
import {
	Dialog,
	DialogContent,
	DialogDescription,
	DialogHeader,
	DialogTitle,
} from '@/components/ui/dialog';
import { Spinner } from '@/components/ui/spinner';
import { getInitials } from '@/composables/useInitials';
import type { AppNotification } from '@/types';

// Your notifications as a list, opened from the app menu.
const page = usePage();
const unread = computed(() => page.props.notifications?.unread ?? 0);
const list = computed(() => page.props.notificationList);

const open = defineModel<boolean>('open', { default: false });

// The list loads when the dialog opens; the count comes with every page.
watch(open, (isOpen) => {
	if (isOpen) {
		router.reload({ only: ['notificationList', 'notifications'] });
	}
});

function show(notification: AppNotification) {
	open.value = false;
	router.post(NotificationController.open(notification.id).url);
}

function readAll() {
	router.post(
		NotificationController.readAll().url,
		{},
		{
			preserveScroll: true,
			preserveState: true,
			only: ['notificationList', 'notifications'],
		},
	);
}
</script>

<template>
	<Dialog v-model:open="open">
		<DialogContent class="gap-4 sm:max-w-lg">
			<DialogHeader>
				<DialogTitle>Notifications</DialogTitle>
				<DialogDescription
					class="flex flex-wrap items-baseline gap-x-3"
				>
					<span class="num">{{
						unread > 0 ? `${unread} unread` : 'All caught up'
					}}</span>
					<button
						v-if="unread > 0"
						type="button"
						class="rounded-[3px] text-sm underline decoration-(--color-rule) underline-offset-4 hover:text-foreground focus-visible:outline-2 focus-visible:outline-ring focus-visible:outline-solid"
						@click="readAll"
					>
						Mark all as read
					</button>
				</DialogDescription>
			</DialogHeader>

			<div v-if="!list" class="flex justify-center py-8">
				<Spinner />
			</div>
			<p
				v-else-if="list.length === 0"
				class="px-4 pt-4 pb-8 text-center text-sm text-muted-foreground"
			>
				Nothing yet. You'll hear about cards you're added to, mentions
				and due dates here.
			</p>
			<ul
				v-else
				role="list"
				class="-mx-2.5 max-h-[min(32rem,65svh)] overflow-y-auto sm:-mx-4"
			>
				<li v-for="notification in list" :key="notification.id">
					<button
						type="button"
						class="flex w-full gap-3 rounded-[var(--radius-sheet)] px-2.5 py-2.5 text-left hover:bg-(--menu-hover) focus-visible:outline-2 focus-visible:outline-ring focus-visible:outline-solid"
						@click="show(notification)"
					>
						<span
							class="flex size-8 shrink-0 items-center justify-center rounded-full bg-(--color-paper-3) text-xs font-medium"
							aria-hidden="true"
						>
							<template v-if="notification.actor">{{
								getInitials(notification.actor.name)
							}}</template>
							<PhBell v-else class="size-4" />
						</span>
						<span class="min-w-0 flex-1 text-sm">
							<span class="block">
								<span
									v-if="notification.actor"
									class="font-medium"
									>{{ notification.actor.name }}</span
								>
								{{ notification.sentence }}
								<span class="font-medium">{{
									notification.card_title
								}}</span>
							</span>
							<span
								v-if="notification.excerpt"
								class="mt-1 line-clamp-2 block text-muted-foreground"
								>{{ notification.excerpt }}</span
							>
							<span
								class="mt-1 block text-xs text-muted-foreground"
							>
								{{ notification.board_name }} ·
								{{ timeAgo(notification.created_at) }}
							</span>
						</span>
						<span
							v-if="!notification.read"
							class="mt-1.5 size-2 shrink-0 rounded-full bg-yellow-400"
							aria-label="Unread"
						/>
					</button>
				</li>
			</ul>
		</DialogContent>
	</Dialog>
</template>
