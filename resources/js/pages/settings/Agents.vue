<script setup lang="ts">
import { Form, Head, router, usePage } from '@inertiajs/vue3';
import { PhRobot, PhTrash } from '@phosphor-icons/vue';
import { computed, ref, watch } from 'vue';
import AgentController from '@/actions/App/Http/Controllers/Settings/AgentController';
import CodeSnippet from '@/components/CodeSnippet.vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import {
	Dialog,
	DialogClose,
	DialogContent,
	DialogDescription,
	DialogFooter,
	DialogTitle,
	DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { edit } from '@/routes/agents';
import type { AgentToken, NewAgentToken } from '@/types/auth';

defineOptions({
	layout: {
		breadcrumbs: [
			{
				title: 'Agents',
				href: edit(),
			},
		],
	},
});

const props = defineProps<{
	tokens: AgentToken[];
	mcpUrl: string;
}>();

// The plain token comes once, as flash data; it's gone after a reload.
const page = usePage();
const created = ref<NewAgentToken | null>(page.flash.agentToken ?? null);

watch(
	() => page.flash.agentToken,
	(token) => {
		if (token) {
			created.value = token;
		}
	},
);

const token = computed(() => created.value?.token ?? '<your-token>');

const claudeCommand = computed(
	() =>
		`claude mcp add --scope user --transport http stackr ${props.mcpUrl} \\\n  --header "Authorization: Bearer ${token.value}"`,
);

const mcpJson = computed(() =>
	JSON.stringify(
		{
			mcpServers: {
				stackr: {
					type: 'http',
					url: props.mcpUrl,
					headers: { Authorization: `Bearer ${token.value}` },
				},
			},
		},
		null,
		2,
	),
);

const examplePrompt =
	'Look at the "To do" list on my Website board in Stackr. Take the top card, move it to "Doing", do the work, tick off its checklist, comment on what you did and move it to "Done".';

const revoking = ref(false);

function revoke(id: number) {
	revoking.value = true;
	router.delete(AgentController.destroy.url(id), {
		preserveScroll: true,
		onFinish: () => {
			revoking.value = false;

			if (created.value?.id === id) {
				created.value = null;
			}
		},
	});
}
</script>

<template>
	<Head title="Agents" />

	<h1 class="sr-only">Agents</h1>

	<div class="space-y-6">
		<Heading
			variant="small"
			title="Agents"
			description="Let an AI agent like Claude Code read and work your boards, as you. What it does shows in a card's history, marked “via agent”."
		/>

		<div
			v-if="created"
			class="space-y-4 rounded-[var(--radius-tile)] bg-(--color-paper-2) p-5"
		>
			<div class="space-y-1">
				<p class="font-medium">“{{ created.name }}” is ready</p>
				<p class="text-sm text-muted-foreground">
					Copy the token now: it won't be shown again. The setup below
					already includes it.
				</p>
			</div>
			<CodeSnippet :code="created.token" />
			<Button variant="secondary" size="sm" @click="created = null">
				Done
			</Button>
		</div>

		<Form
			v-bind="AgentController.store.form()"
			class="flex items-start gap-2"
			reset-on-success
			:options="{ preserveScroll: true }"
			v-slot="{ errors, processing }"
		>
			<div class="grid flex-1 gap-1">
				<Input
					name="name"
					required
					maxlength="100"
					aria-label="Token name"
					placeholder="Name, e.g. Claude Code on my MacBook"
				/>
				<InputError :message="errors.name" />
			</div>
			<Button :disabled="processing">Create token</Button>
		</Form>

		<div
			v-if="tokens.length"
			class="overflow-hidden rounded-[var(--radius-tile)] ring-1 ring-(--surface-edge)"
		>
			<div
				v-for="item in tokens"
				:key="item.id"
				class="flex items-center justify-between gap-4 border-b px-4 py-3 last:border-b-0"
			>
				<div class="flex min-w-0 items-center gap-3">
					<div
						class="flex size-9 shrink-0 items-center justify-center rounded-full bg-(--color-paper-3)"
					>
						<PhRobot class="size-4.5 text-muted-foreground" />
					</div>
					<div class="min-w-0">
						<p class="truncate font-normal">{{ item.name }}</p>
						<p class="text-sm text-muted-foreground">
							Created {{ item.created_at_diff }}
							<span class="mx-1 text-muted-foreground/50">/</span>
							{{
								item.last_used_at_diff
									? `Last used ${item.last_used_at_diff}`
									: 'Never used'
							}}
						</p>
					</div>
				</div>

				<Dialog>
					<DialogTrigger as-child>
						<Button
							variant="ghost"
							size="sm"
							class="text-destructive hover:bg-destructive/10 hover:text-destructive"
						>
							<PhTrash class="size-4" />
							<span class="sr-only">Revoke</span>
						</Button>
					</DialogTrigger>

					<DialogContent>
						<DialogTitle>Revoke token</DialogTitle>
						<DialogDescription>
							Agents using “{{ item.name }}” lose access to your
							boards right away.
						</DialogDescription>
						<DialogFooter class="gap-2">
							<DialogClose as-child>
								<Button variant="secondary">Cancel</Button>
							</DialogClose>
							<Button
								variant="destructive"
								:disabled="revoking"
								@click="revoke(item.id)"
							>
								{{ revoking ? 'Revoking...' : 'Revoke token' }}
							</Button>
						</DialogFooter>
					</DialogContent>
				</Dialog>
			</div>
		</div>
	</div>

	<div class="space-y-6">
		<Heading
			variant="small"
			title="Connect an agent"
			description="Create a token above, then add Stackr as an MCP server to your agent."
		/>

		<CodeSnippet
			label="Claude Code: run this once in a terminal. --scope user makes it available in every board, without the token ending up in a repository."
			:code="claudeCommand"
		/>

		<CodeSnippet
			label="Other agents (Cursor, VS Code, Claude Code's .mcp.json): add the server to their MCP config."
			:code="mcpJson"
		/>

		<CodeSnippet
			label="Then ask, for example:"
			:code="examplePrompt"
			wrap
		/>

		<p class="text-sm text-muted-foreground">
			The agent can list your boards, read and create cards, edit them,
			comment, move them between lists, work through checklists, assign
			members, set labels and archive cards. It can't delete anything,
			change board settings or invite people.
		</p>
	</div>
</template>
