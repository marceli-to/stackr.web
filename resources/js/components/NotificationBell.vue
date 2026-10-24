<script setup lang="ts">
import { router, usePage } from '@inertiajs/vue3';
import { PhBell } from '@phosphor-icons/vue';
import { computed, ref, watch } from 'vue';
import NotificationController from '@/actions/App/Http/Controllers/NotificationController';
import { timeAgo } from '@/components/card/board';
import {
	Popover,
	PopoverContent,
	PopoverTrigger,
} from '@/components/ui/popover';
import { Spinner } from '@/components/ui/spinner';
import { getInitials } from '@/composables/useInitials';
import type { AppNotification } from '@/types';

const page = usePage();
const unread = computed(() => page.props.notifications?.unread ?? 0);
const list = computed(() => page.props.notificationList);

const open = ref(false);

// The list loads when the bell opens; the count comes with every page.
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
	<Popover v-model:open="open">
		<PopoverTrigger as-child>
			<button
				type="button"
				class="relative flex size-9 items-center justify-center rounded-full transition-colors duration-(--dur-micro) hover:bg-current/15 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ring focus-visible:outline-solid data-[state=open]:bg-current/15"
				:aria-label="
					unread > 0
						? `Notifications, ${unread} unread`
						: 'Notifications'
				"
			>
				<PhBell class="size-5" />
				<span
					v-if="unread > 0"
					class="num absolute top-1 right-1 flex h-4 min-w-4 items-center justify-center rounded-full bg-destructive px-1 text-[0.625rem] leading-none font-semibold text-white"
					aria-hidden="true"
				>
					{{ unread > 9 ? '9+' : unread }}
				</span>
			</button>
		</PopoverTrigger>
		<PopoverContent align="end" class="w-96 max-w-[calc(100vw-1.5rem)] p-0">
			<div class="flex items-center justify-between px-4 pt-3.5 pb-2">
				<p class="font-semibold">Notifications</p>
				<button
					v-if="unread > 0"
					type="button"
					class="rounded-[3px] text-sm text-muted-foreground underline decoration-(--color-rule) underline-offset-4 hover:text-foreground focus-visible:outline-2 focus-visible:outline-ring focus-visible:outline-solid"
					@click="readAll"
				>
					Mark all as read
				</button>
			</div>

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
			<ul v-else class="max-h-[min(28rem,70svh)] overflow-y-auto p-1.5">
				<li v-for="notification in list" :key="notification.id">
					<button
						type="button"
						class="flex w-full gap-3 rounded-[var(--radius-sheet)] px-2.5 py-2.5 text-left hover:bg-(--menu-hover) focus-visible:outline-2 focus-visible:outline-ring focus-visible:outline-solid"
						@click="show(notification)"
					>
						<span
							class="flex size-8 shrink-0 items-center justify-center rounded-full bg-(--color-paper-3) text-xs font-semibold"
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
									class="font-semibold"
									>{{ notification.actor.name }}</span
								>
								{{ notification.sentence }}
								<span class="font-semibold">{{
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
								{{ notification.project_name }} ·
								{{ timeAgo(notification.created_at) }}
							</span>
						</span>
						<span
							v-if="!notification.read"
							class="mt-1.5 size-2 shrink-0 rounded-full bg-destructive"
							aria-label="Unread"
						/>
					</button>
				</li>
			</ul>
		</PopoverContent>
	</Popover>
</template>
