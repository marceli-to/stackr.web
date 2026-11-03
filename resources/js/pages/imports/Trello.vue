<script setup lang="ts">
import { Head, Link, useHttp, usePage } from '@inertiajs/vue3';
import {
	PhArrowSquareOut,
	PhCheck,
	PhFileArrowUp,
	PhWarningCircle,
} from '@phosphor-icons/vue';
import { computed, reactive, ref } from 'vue';
import TrelloImportController from '@/actions/App/Http/Controllers/TrelloImportController';
import CodeSnippet from '@/components/CodeSnippet.vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { index as boardsIndex, show as showBoard } from '@/routes/boards';

defineOptions({
	layout: {
		breadcrumbs: [{ title: 'Boards', href: boardsIndex() }],
	},
});

type Person = { id: number; name: string; email: string };
type Unfinished = {
	id: number;
	board: { key: string; name: string };
	done: number;
	total: number;
};
type Choice = {
	choice: 'user' | 'create' | 'nobody';
	user_id: number | null;
	name: string;
	email: string;
};
type TrelloMember = { id: string; fullName?: string; username?: string };
type TrelloExport = {
	name?: string;
	lists?: { closed?: boolean }[];
	cards?: {
		closed?: boolean;
		idMembers?: string[];
		attachments?: { isUpload?: boolean }[];
	}[];
	members?: TrelloMember[];
	actions?: { type?: string; idMemberCreator?: string }[];
	checklists?: unknown[];
};
type Failed = { name: string; card: string; reason: string };
type Progress = {
	name: string | null;
	done: number;
	total: number;
	failed: Failed[];
	finished: boolean;
};

const props = defineProps<{
	users: Person[];
	canCreateUsers: boolean;
	unfinished: Unfinished[];
}>();

const me = computed(() => usePage().props.auth.user);

// Step 1: the export, read in the browser for the overview.
const picker = ref<HTMLInputElement | null>(null);
const exportData = ref<TrelloExport | null>(null);
const fileError = ref<string | null>(null);

const form = useHttp<{
	file: File | null;
	name: string;
	people: Record<string, Choice>;
}>({ file: null, name: '', people: {} });

async function pick(event: Event) {
	const file = (event.target as HTMLInputElement).files?.[0];
	(event.target as HTMLInputElement).value = '';

	if (!file) {
		return;
	}

	fileError.value = null;

	try {
		const data = JSON.parse(await file.text()) as TrelloExport;

		if (!data.lists || !data.cards || !data.members) {
			throw new Error();
		}

		exportData.value = data;
		form.file = file;
		form.name = data.name ?? file.name.replace(/\.json$/i, '');
		form.people = Object.fromEntries(
			data.members.map((member) => [member.id, guess(member)]),
		);
	} catch {
		exportData.value = null;
		form.file = null;
		fileError.value =
			'This isn’t a Trello board export. In Trello, open the board’s menu, then Print, export and share → Export as JSON.';
	}
}

// Someone who never touched the board (a bot, a guest) needs no account.
function involved(member: TrelloMember) {
	return (
		(exportData.value?.cards ?? []).some((card) =>
			card.idMembers?.includes(member.id),
		) ||
		(exportData.value?.actions ?? []).some(
			(a) => a.idMemberCreator === member.id,
		)
	);
}

// Same name (or a name that starts with it) is probably the same person.
function guess(member: TrelloMember): Choice {
	const name = (member.fullName ?? '').trim().toLowerCase();
	const match =
		props.users.find((user) => user.name.toLowerCase() === name) ??
		(name !== ''
			? props.users.find((user) =>
					user.name.toLowerCase().startsWith(`${name} `),
				)
			: undefined);

	return {
		choice: match
			? 'user'
			: props.canCreateUsers && involved(member)
				? 'create'
				: 'nobody',
		user_id: match?.id ?? null,
		name: member.fullName ?? member.username ?? '',
		email: '',
	};
}

const summary = computed(() => {
	const data = exportData.value;

	if (!data) {
		return null;
	}

	const cards = data.cards ?? [];

	return {
		lists: (data.lists ?? []).length,
		cards: cards.length,
		archived: cards.filter((card) => card.closed).length,
		comments: (data.actions ?? []).filter((a) => a.type === 'commentCard')
			.length,
		files: cards.reduce(
			(sum, card) =>
				sum + (card.attachments ?? []).filter((a) => a.isUpload).length,
			0,
		),
		checklists: (data.checklists ?? []).length,
	};
});

// What's in the file; empty kinds stay out.
const chips = computed(() => {
	const counts = summary.value;

	if (!counts) {
		return [];
	}

	return [
		plural(counts.lists, 'list'),
		plural(counts.cards, 'card'),
		counts.archived ? `${counts.archived} archived` : null,
		counts.checklists ? plural(counts.checklists, 'checklist') : null,
		counts.comments ? plural(counts.comments, 'comment') : null,
		counts.files ? plural(counts.files, 'file') : null,
	].filter((chip): chip is string => chip !== null);
});

// How much of the board each person touched, to help tell them apart.
function involvement(member: TrelloMember) {
	const cards = (exportData.value?.cards ?? []).filter((card) =>
		card.idMembers?.includes(member.id),
	).length;
	const comments = (exportData.value?.actions ?? []).filter(
		(a) => a.type === 'commentCard' && a.idMemberCreator === member.id,
	).length;

	return [plural(cards, 'card'), plural(comments, 'comment')].join(', ');
}

function setChoice(memberId: string, value: string) {
	const person = form.people[memberId];

	if (value === 'create' || value === 'nobody') {
		person.choice = value;
		person.user_id = null;
	} else {
		person.choice = 'user';
		person.user_id = Number(value);
	}
}

function error(path: string) {
	return (form.errors as Record<string, string | undefined>)[path];
}

// Step 3: the Trello key and token, kept in this page only.
const trello = reactive({ key: '', token: '' });
const tokenUrl = computed(
	() =>
		`https://trello.com/1/authorize?expiration=1day&scope=read&response_type=token&name=Stackr%20Import&key=${encodeURIComponent(trello.key.trim())}`,
);

// Importing: the board first, then the files one by one.
type Phase = 'setup' | 'files' | 'done';
const phase = ref<Phase>('setup');
const imported = ref<{
	importId: number | null;
	board: { key: string; name: string };
	cards: number;
	comments: number;
	created: { name: string; email: string; password: string }[];
} | null>(null);
const progress = ref<Progress | null>(null);
const downloadError = ref<string | null>(null);
const downloading = ref(false);
const http = useHttp<{ key: string; token: string }>({ key: '', token: '' });

async function start() {
	try {
		const result = (await form.post(
			TrelloImportController.store.url(),
		)) as {
			import: { id: number; total: number };
			board: { key: string; name: string };
			cards: number;
			comments: number;
			created: { name: string; email: string; password: string }[];
		};

		imported.value = {
			importId: result.import.id,
			board: result.board,
			cards: result.cards,
			comments: result.comments,
			created: result.created,
		};
		progress.value = {
			name: null,
			done: 0,
			total: result.import.total,
			failed: [],
			finished: result.import.total === 0,
		};

		if (result.import.total === 0) {
			await finishWithoutFiles();
		} else if (trello.key.trim() && trello.token.trim()) {
			phase.value = 'files';
			await fetchFiles();
		} else {
			phase.value = 'files';
		}
	} catch {
		// The form shows the validation errors.
	}
}

function resume(item: Unfinished) {
	imported.value = {
		importId: item.id,
		board: item.board,
		cards: 0,
		comments: 0,
		created: [],
	};
	progress.value = {
		name: null,
		done: item.done,
		total: item.total,
		failed: [],
		finished: false,
	};
	phase.value = 'files';
}

async function fetchFiles() {
	if (!imported.value?.importId) {
		return;
	}

	downloadError.value = null;
	downloading.value = true;
	http.key = trello.key.trim();
	http.token = trello.token.trim();

	try {
		while (!progress.value?.finished) {
			progress.value = (await http.post(
				TrelloImportController.download.url(imported.value.importId),
			)) as Progress;
		}

		phase.value = 'done';
	} catch {
		downloadError.value =
			http.errors.token ??
			http.errors.key ??
			'The download stopped. Check the connection and continue.';
	} finally {
		downloading.value = false;
	}
}

async function finishWithoutFiles() {
	if (imported.value?.importId) {
		await http.delete(
			TrelloImportController.destroy.url(imported.value.importId),
		);
	}

	phase.value = 'done';
}

const percent = computed(() =>
	progress.value && progress.value.total > 0
		? Math.round((progress.value.done / progress.value.total) * 100)
		: 0,
);

function plural(count: number, word: string) {
	return `${count} ${word}${count === 1 ? '' : 's'}`;
}

const field =
	'h-9 w-full rounded-[var(--radius-sheet)] bg-sheet px-2.5 text-[0.9375rem] ring-1 ring-(--control-edge) outline-hidden ring-inset focus-field disabled:opacity-55 dark:bg-(--color-paper-2)';
const step = 'space-y-4 border-t border-current/10 pt-8';
const stepNumber =
	'num mr-2 inline-flex size-6 items-center justify-center rounded-full bg-current/8 text-sm font-medium';
</script>

<template>
	<Head title="Import from Trello" />

	<div class="mx-auto w-full max-w-2xl space-y-8 px-3 pt-8 pb-16 md:px-5">
		<Heading
			title="Import from Trello"
			description="Bring a Trello board over as a new board: lists, cards, labels, checklists, comments and files, with their original dates."
		/>

		<!-- Imports whose files didn't all come in. -->
		<div
			v-if="phase === 'setup' && unfinished.length"
			class="space-y-2 rounded-[var(--radius-tile)] bg-(--color-paper-2) p-4"
		>
			<p class="text-sm text-muted-foreground">
				Files still to fetch from earlier imports
			</p>
			<div
				v-for="item in unfinished"
				:key="item.id"
				class="flex items-center justify-between gap-3"
			>
				<span class="min-w-0 truncate font-medium">
					{{ item.board.name }}
					<span class="num font-normal text-muted-foreground">
						· {{ item.done }} of {{ item.total }} files
					</span>
				</span>
				<Button variant="secondary" size="sm" @click="resume(item)">
					Continue
				</Button>
			</div>
		</div>

		<template v-if="phase === 'setup'">
			<!-- 1. The export -->
			<section class="space-y-4">
				<h2 class="flex items-center font-medium">
					<span :class="stepNumber">1</span>The export
				</h2>
				<p class="text-sm text-muted-foreground">
					In Trello, open the board’s menu, then
					<span class="text-foreground"
						>Print, export and share → Export as JSON</span
					>, and save the file.
				</p>

				<input
					ref="picker"
					type="file"
					accept="application/json,.json"
					class="sr-only"
					tabindex="-1"
					aria-label="Trello export"
					@change="pick"
				/>

				<button
					v-if="!summary"
					type="button"
					class="flex w-full items-center gap-3 rounded-[var(--radius-tile)] bg-(--color-paper-2) p-5 text-left transition-colors duration-(--dur-micro) hover:bg-(--color-paper-3) focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-(--color-focus) focus-visible:outline-solid"
					@click="picker?.click()"
				>
					<PhFileArrowUp class="size-6 shrink-0 opacity-70" />
					<span>
						<span class="block font-medium"
							>Choose the JSON file</span
						>
						<span class="block text-sm text-muted-foreground">
							Up to 20 MB
						</span>
					</span>
				</button>

				<div
					v-else
					class="flex items-start justify-between gap-3 rounded-[var(--radius-tile)] bg-(--color-paper-2) p-4"
				>
					<div class="min-w-0 space-y-2">
						<p class="truncate font-medium">
							{{ form.file?.name }}
						</p>
						<div class="flex flex-wrap gap-1.5 text-sm">
							<span
								v-for="chip in chips"
								:key="chip"
								class="num rounded-[var(--radius-sheet)] bg-current/6 px-2 py-0.5"
							>
								{{ chip }}
							</span>
						</div>
					</div>
					<Button variant="ghost" size="sm" @click="picker?.click()">
						Change
					</Button>
				</div>
				<InputError :message="fileError ?? error('file')" />
			</section>

			<template v-if="summary && exportData">
				<!-- 2. The board and its people -->
				<section :class="step">
					<h2 class="flex items-center font-medium">
						<span :class="stepNumber">2</span>Board and people
					</h2>

					<div class="grid gap-1.5">
						<Label for="import-name">Name of the new board</Label>
						<Input
							id="import-name"
							v-model="form.name"
							maxlength="250"
							required
						/>
						<InputError :message="error('name')" />
					</div>

					<div class="space-y-1">
						<p class="text-sm font-medium">Who is who</p>
						<p class="text-sm text-muted-foreground">
							Everyone you pick joins the board.
							<template v-if="canCreateUsers">
								New accounts are verified straight away and get
								no email.
							</template>
							Work by “nobody” is credited to you, with their name
							in front of their comments.
						</p>
					</div>

					<div class="divide-y divide-current/8">
						<div
							v-for="member in exportData.members"
							:key="member.id"
							class="space-y-2 py-3"
						>
							<div
								class="grid items-center gap-2 sm:grid-cols-[minmax(0,1fr)_16rem]"
							>
								<div class="min-w-0">
									<p class="truncate font-medium">
										{{ member.fullName || member.username }}
									</p>
									<p
										class="truncate text-sm text-muted-foreground"
									>
										@{{ member.username }} ·
										{{ involvement(member) }}
									</p>
								</div>
								<select
									:class="field"
									:aria-label="`Stackr account for ${member.fullName}`"
									:value="
										form.people[member.id].choice === 'user'
											? String(
													form.people[member.id]
														.user_id,
												)
											: form.people[member.id].choice
									"
									@change="
										setChoice(
											member.id,
											($event.target as HTMLSelectElement)
												.value,
										)
									"
								>
									<option
										v-for="user in users"
										:key="user.id"
										:value="String(user.id)"
									>
										{{ user.name
										}}{{
											user.id === me.id ? ' (you)' : ''
										}}
									</option>
									<option
										v-if="canCreateUsers"
										value="create"
									>
										New account…
									</option>
									<option value="nobody">Nobody</option>
								</select>
							</div>

							<div
								v-if="
									form.people[member.id].choice === 'create'
								"
								class="grid gap-2 sm:grid-cols-2"
							>
								<div class="grid gap-1">
									<Input
										v-model="form.people[member.id].name"
										placeholder="Name"
										aria-label="Name"
									/>
									<InputError
										:message="
											error(`people.${member.id}.name`)
										"
									/>
								</div>
								<div class="grid gap-1">
									<Input
										v-model="form.people[member.id].email"
										type="email"
										placeholder="Email"
										aria-label="Email"
									/>
									<InputError
										:message="
											error(`people.${member.id}.email`)
										"
									/>
								</div>
							</div>
							<InputError
								:message="
									error(`people.${member.id}.choice`) ??
									error(`people.${member.id}.user_id`)
								"
							/>
						</div>
					</div>
				</section>

				<!-- 3. Files -->
				<section v-if="summary.files > 0" :class="step">
					<h2 class="flex items-center font-medium">
						<span :class="stepNumber">3</span>Files
					</h2>
					<p class="text-sm text-muted-foreground">
						Trello only hands out uploaded files with an API key and
						token. Create a Power-Up at
						<a
							href="https://trello.com/power-ups/admin"
							target="_blank"
							rel="noopener"
							class="underline underline-offset-2"
							>trello.com/power-ups/admin</a
						>, copy its API key from “Trello Auth”, then get a
						token. They stay in this page and are sent along with
						each file, never stored. Leave them empty to import
						without files.
					</p>
					<div class="grid gap-3 sm:grid-cols-2">
						<div class="grid gap-1.5">
							<Label for="trello-key">API key</Label>
							<Input
								id="trello-key"
								v-model="trello.key"
								autocomplete="off"
								spellcheck="false"
							/>
						</div>
						<div class="grid gap-1.5">
							<Label for="trello-token">Token</Label>
							<Input
								id="trello-token"
								v-model="trello.token"
								type="password"
								autocomplete="off"
							/>
						</div>
					</div>
					<a
						v-if="trello.key.trim()"
						:href="tokenUrl"
						target="_blank"
						rel="noopener"
						class="inline-flex items-center gap-1.5 text-sm underline underline-offset-2"
					>
						Get a read-only token for a day
						<PhArrowSquareOut weight="regular" class="size-3.5" />
					</a>
				</section>

				<div
					class="flex items-center gap-3 border-t border-current/10 pt-8"
				>
					<Button
						:disabled="form.processing || !form.name"
						@click="start"
					>
						{{
							form.processing
								? 'Importing…'
								: summary.files > 0 &&
									  !(
											trello.key.trim() &&
											trello.token.trim()
									  )
									? 'Import without files'
									: 'Import'
						}}
					</Button>
					<span
						v-if="form.processing"
						class="text-sm text-muted-foreground"
					>
						Building the board…
					</span>
				</div>
			</template>
		</template>

		<!-- Fetching the files -->
		<section
			v-else-if="phase === 'files' && imported && progress"
			class="space-y-5"
		>
			<p v-if="imported.cards">
				<span class="font-medium">{{ imported.board.name }}</span> is
				in: {{ plural(imported.cards, 'card') }} and
				{{ plural(imported.comments, 'comment') }}. Now the files.
			</p>
			<p v-else>
				Fetching the remaining files of
				<span class="font-medium">{{ imported.board.name }}</span
				>.
			</p>

			<div class="space-y-2">
				<div
					class="h-2 overflow-hidden rounded-full bg-current/8"
					role="progressbar"
					:aria-valuenow="progress.done"
					:aria-valuemax="progress.total"
				>
					<div
						class="h-full rounded-full bg-current/60 transition-[width] duration-300"
						:style="{ width: `${percent}%` }"
					/>
				</div>
				<p
					class="num flex justify-between text-sm text-muted-foreground"
				>
					<span class="truncate">{{ progress.name ?? '' }}</span>
					<span>{{ progress.done }} / {{ progress.total }}</span>
				</p>
			</div>

			<template v-if="!downloading">
				<p v-if="downloadError" class="flex items-center gap-2 text-sm">
					<PhWarningCircle class="size-4 shrink-0" />
					{{ downloadError }}
				</p>
				<div class="grid gap-3 sm:grid-cols-2">
					<div class="grid gap-1.5">
						<Label for="files-key">Trello API key</Label>
						<Input
							id="files-key"
							v-model="trello.key"
							autocomplete="off"
							spellcheck="false"
						/>
					</div>
					<div class="grid gap-1.5">
						<Label for="files-token">Token</Label>
						<Input
							id="files-token"
							v-model="trello.token"
							type="password"
							autocomplete="off"
						/>
					</div>
				</div>
				<a
					v-if="trello.key.trim()"
					:href="tokenUrl"
					target="_blank"
					rel="noopener"
					class="inline-flex items-center gap-1.5 text-sm underline underline-offset-2"
				>
					Get a read-only token for a day
					<PhArrowSquareOut weight="regular" class="size-3.5" />
				</a>
				<div class="flex items-center gap-2">
					<Button
						:disabled="!trello.key.trim() || !trello.token.trim()"
						@click="fetchFiles"
					>
						{{ progress.done > 0 ? 'Continue' : 'Fetch the files' }}
					</Button>
					<Button variant="ghost" @click="finishWithoutFiles">
						Leave the files out
					</Button>
				</div>
			</template>
		</section>

		<!-- Done -->
		<section v-else-if="phase === 'done' && imported" class="space-y-5">
			<p class="flex items-center gap-2 font-medium">
				<PhCheck class="size-5" />
				{{ imported.board.name }} is ready.
			</p>
			<p
				v-if="progress && progress.total > 0"
				class="text-sm text-muted-foreground"
			>
				{{ progress.done - progress.failed.length }} of
				{{ plural(progress.total, 'file') }} came over.
			</p>

			<div v-if="progress?.failed.length" class="space-y-1 text-sm">
				<p class="font-medium">Trello didn’t hand these over</p>
				<p
					v-for="(failed, i) in progress.failed"
					:key="i"
					class="text-muted-foreground"
				>
					{{ failed.name }} on “{{ failed.card }}” ({{
						failed.reason
					}})
				</p>
			</div>

			<div
				v-if="imported.created.length"
				class="space-y-3 rounded-[var(--radius-tile)] bg-(--color-paper-2) p-4"
			>
				<div class="space-y-1">
					<p class="font-medium">New accounts</p>
					<p class="text-sm text-muted-foreground">
						Pass the passwords on now: they won’t be shown again.
						Everyone can change theirs in Settings.
					</p>
				</div>
				<div
					v-for="account in imported.created"
					:key="account.email"
					class="space-y-1"
				>
					<p class="text-sm">
						{{ account.name }}
						<span class="text-muted-foreground"
							>&lt;{{ account.email }}&gt;</span
						>
					</p>
					<CodeSnippet :code="account.password" />
				</div>
			</div>

			<Button as-child>
				<Link :href="showBoard(imported.board.key)"
					>Open the board</Link
				>
			</Button>
		</section>
	</div>
</template>
