<script setup lang="ts">
import { Form, usePage } from '@inertiajs/vue3';
import { PhUserPlus } from '@phosphor-icons/vue';
import { computed, ref } from 'vue';
import ProjectInvitationController from '@/actions/App/Http/Controllers/ProjectInvitationController';
import ProjectMemberController from '@/actions/App/Http/Controllers/ProjectMemberController';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import {
	Dialog,
	DialogContent,
	DialogDescription,
	DialogHeader,
	DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { getInitials } from '@/composables/useInitials';
import type { ProjectInvitation, ProjectMember } from '@/types';

// The board's people: an avatar stack in the top bar and, for the owner, an
// Invite button pinned to the corner; both open the members dialog. Only the
// owner invites and removes; anyone else can leave.
const page = usePage<{
	project: { id: number; name: string };
	members: ProjectMember[];
	invitations: ProjectInvitation[];
	canManageMembers: boolean;
}>();

const project = computed(() => page.props.project);
const members = computed(() => page.props.members ?? []);
const invitations = computed(() => page.props.invitations ?? []);
const canManage = computed(() => page.props.canManageMembers);
const me = computed(() => page.props.auth.user.id);

// Your own initials are the account menu next door, so the stack shows the others.
const others = computed(() => members.value.filter((m) => m.id !== me.value));
const shown = computed(() => others.value.slice(0, 4));
const more = computed(() => others.value.length - shown.value.length);

const open = ref(false);
// The member whose "Remove" or "Leave" waits for a second click.
const confirming = ref<number | null>(null);

const actionClass =
	'rounded-sm text-sm text-muted-foreground underline decoration-current/30 underline-offset-4 hover:text-foreground hover:decoration-current focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ring focus-visible:outline-solid';
</script>

<template>
	<button
		v-if="others.length > 0"
		type="button"
		class="flex -space-x-2 rounded-full focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ring focus-visible:outline-solid"
		:aria-label="`Members of ${project.name}`"
		@click="open = true"
	>
		<span
			v-for="member in shown"
			:key="member.id"
			class="size-9 rounded-full bg-field ring-2 ring-field"
			:title="member.name"
		>
			<span
				class="flex size-full items-center justify-center rounded-full bg-current/15 text-sm font-semibold"
			>
				{{ getInitials(member.name) }}
			</span>
		</span>
		<span
			v-if="more > 0"
			class="size-9 rounded-full bg-field ring-2 ring-field"
		>
			<span
				class="num flex size-full items-center justify-center rounded-full bg-current/15 text-sm font-semibold"
			>
				+{{ more }}
			</span>
		</span>
	</button>

	<button
		v-if="canManage"
		type="button"
		class="fixed right-5 bottom-5 z-20 flex h-11 items-center gap-2 rounded-[var(--radius-tile)] bg-field-well px-4 font-semibold text-field-ink shadow-(--float-shadow) transition-colors duration-(--dur-micro) hover:bg-field-well/85 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ring focus-visible:outline-solid"
		@click="open = true"
	>
		<PhUserPlus class="size-4" />
		Invite
	</button>

	<Dialog v-model:open="open" @update:open="confirming = null">
		<DialogContent class="sm:max-w-lg">
			<DialogHeader>
				<DialogTitle>Members</DialogTitle>
				<DialogDescription>
					Everyone here can open and edit {{ project.name }}.
				</DialogDescription>
			</DialogHeader>

			<Form
				v-if="canManage"
				v-bind="ProjectMemberController.store.form(project.id)"
				:options="{ preserveScroll: true, preserveState: true }"
				reset-on-success
				class="space-y-2"
				v-slot="{ errors, processing }"
			>
				<label for="invite-email" class="text-sm font-semibold">
					Invite by email
				</label>
				<div class="flex gap-2">
					<Input
						id="invite-email"
						type="email"
						name="email"
						required
						autocomplete="off"
						placeholder="name@example.com"
					/>
					<Button type="submit" :disabled="processing">
						Invite
					</Button>
				</div>
				<InputError :message="errors.email" />
				<p v-if="!errors.email" class="text-sm text-muted-foreground">
					People with an account are added right away; anyone else
					gets a link to sign up.
				</p>
			</Form>

			<ul class="divide-y divide-border">
				<li
					v-for="member in members"
					:key="member.id"
					class="flex items-center gap-3 py-2.5"
				>
					<span
						class="flex size-8 shrink-0 items-center justify-center rounded-full bg-(--color-paper-3) text-xs font-semibold"
					>
						{{ getInitials(member.name) }}
					</span>
					<span class="min-w-0 flex-1">
						<span class="block truncate font-semibold">
							{{ member.name }}
							<span
								v-if="member.id === me"
								class="font-normal text-muted-foreground"
								>(you)</span
							>
						</span>
						<span
							class="block truncate text-sm text-muted-foreground"
						>
							{{ member.email }}
						</span>
					</span>

					<span
						v-if="member.role === 'owner'"
						class="text-sm text-muted-foreground"
					>
						Owner
					</span>
					<template v-else-if="canManage || member.id === me">
						<Form
							v-if="confirming === member.id"
							v-bind="
								ProjectMemberController.destroy.form({
									project: project.id,
									user: member.id,
								})
							"
							:options="{ preserveScroll: true }"
							class="flex items-center gap-3"
							@success="confirming = null"
							v-slot="{ processing }"
						>
							<button
								type="submit"
								:disabled="processing"
								:class="actionClass"
								class="text-destructive hover:text-destructive"
							>
								{{
									member.id === me
										? 'Yes, leave'
										: 'Yes, remove'
								}}
							</button>
							<button
								type="button"
								:class="actionClass"
								@click="confirming = null"
							>
								Cancel
							</button>
						</Form>
						<button
							v-else
							type="button"
							:class="actionClass"
							@click="confirming = member.id"
						>
							{{ member.id === me ? 'Leave' : 'Remove' }}
						</button>
					</template>
				</li>

				<li
					v-for="invitation in invitations"
					:key="`invitation-${invitation.id}`"
					class="flex items-center gap-3 py-2.5"
				>
					<span
						class="flex size-8 shrink-0 items-center justify-center rounded-full border border-dashed border-current/30 text-muted-foreground"
						aria-hidden="true"
					>
						@
					</span>
					<span class="min-w-0 flex-1">
						<span class="block truncate">{{
							invitation.email
						}}</span>
						<span class="block text-sm text-muted-foreground">
							{{
								invitation.expired
									? 'Invitation expired'
									: 'Invited, not joined yet'
							}}
						</span>
					</span>
					<Form
						v-bind="ProjectMemberController.store.form(project.id)"
						:options="{ preserveScroll: true, preserveState: true }"
					>
						<input
							type="hidden"
							name="email"
							:value="invitation.email"
						/>
						<button type="submit" :class="actionClass">
							Resend
						</button>
					</Form>
					<Form
						v-bind="
							ProjectInvitationController.destroy.form(
								invitation.id,
							)
						"
						:options="{ preserveScroll: true, preserveState: true }"
					>
						<button type="submit" :class="actionClass">
							Withdraw
						</button>
					</Form>
				</li>
			</ul>
		</DialogContent>
	</Dialog>
</template>
