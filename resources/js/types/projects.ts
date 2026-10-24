export type ProjectRole = 'owner' | 'member';

export const projectColors = [
	'cobalt',
	'rust',
	'moss',
	'oxblood',
	'lagoon',
	'plum',
] as const;

export type ProjectColor = (typeof projectColors)[number];

export type Project = {
	id: number;
	name: string;
	description: string | null;
	url: string | null;
	color: ProjectColor;
	/** A photo behind the board, or null for the plain colour. */
	background_url: string | null;
};

export type ProjectSummary = Project & {
	role: ProjectRole;
	stacks_count: number;
	cards_count: number;
	updated_at: string;
};

export const labelColors = [
	'green',
	'yellow',
	'orange',
	'red',
	'purple',
	'blue',
	'sky',
	'gray',
] as const;

export type LabelColor = (typeof labelColors)[number];

export type Label = {
	id: number;
	name: string | null;
	color: LabelColor;
};

export type Member = {
	id: number;
	name: string;
};

/** Someone on the project, as the members dialog lists them. */
export type ProjectMember = Member & {
	email: string;
	role: 'owner' | 'member';
};

/** An emailed invitation nobody has accepted yet. */
export type ProjectInvitation = {
	id: number;
	email: string;
	expired: boolean;
};

/** A card as its face on the board shows it. */
export type BoardCard = {
	id: number;
	title: string;
	has_description: boolean;
	is_template: boolean;
	/** Whether the viewer watches it. */
	watching: boolean;
	start_at: string | null;
	due_at: string | null;
	completed_at: string | null;
	labels: number[];
	members: number[];
	cover: { url: string; width: number | null; height: number | null } | null;
	cover_color: LabelColor | null;
	attachments_count: number;
	comments_count: number;
	checklist: { done: number; total: number } | null;
};

export type ChecklistItem = {
	id: number;
	title: string;
	done: boolean;
};

export type Checklist = {
	id: number;
	title: string;
	items: ChecklistItem[];
};

export type CardAttachment = {
	id: number;
	name: string;
	mime_type: string;
	size: number;
	is_image: boolean;
	url: string;
	/** A small copy for previews; null for files that aren't images. */
	thumb_url: string | null;
	download_url: string;
	width: number | null;
	height: number | null;
	user: string | null;
	created_at: string;
};

export type CardComment = {
	id: number;
	body: string;
	body_html: string;
	user: Member | null;
	mine: boolean;
	created_at: string;
	edited: boolean;
};

/** An entry in a card's activity log; `data` depends on the type. */
export type CardActivity = {
	id: number;
	type: string;
	data: Record<string, string | number | undefined>;
	user: Member | null;
	created_at: string;
};

/** An opened card: everything the card dialog shows. */
export type CardDetail = {
	id: number;
	title: string;
	description: string | null;
	description_html: string | null;
	start_at: string | null;
	due_at: string | null;
	completed_at: string | null;
	is_template: boolean;
	watching: boolean;
	/** Archived itself, or in an archived list. */
	archived: boolean;
	created_at: string;
	stack: { id: number; name: string };
	/** Its place among the list's cards on the board; null when archived. */
	index: number | null;
	labels: number[];
	members: number[];
	cover_attachment_id: number | null;
	cover_color: LabelColor | null;
	checklists: Checklist[];
	attachments: CardAttachment[];
	comments: CardComment[];
	activities: CardActivity[];
};

/** A checklist on the board whose items a new checklist can copy. */
export type BoardChecklist = {
	id: number;
	title: string;
	card: string;
	items_count: number;
};

/** A board a card can be moved or copied to. */
export type Destination = {
	id: number;
	name: string;
	stacks: { id: number; name: string; cards_count: number }[];
};

/** What the board's archive lists. */
export type BoardArchive = {
	cards: { id: number; title: string; stack: string; archived_at: string }[];
	stacks: {
		id: number;
		name: string;
		cards_count: number;
		archived_at: string;
	}[];
};

export type BoardStack = {
	id: number;
	name: string;
	/** How many cards the list should hold at most, or null. */
	wip_limit: number | null;
	cards: BoardCard[];
};

/** An entry under the bell. */
export type AppNotification = {
	id: string;
	card_id: number;
	card_title: string;
	project_id: number;
	project_name: string;
	actor: Member | null;
	/** Follows the actor's name, e.g. "mentioned you on". */
	sentence: string;
	excerpt: string | null;
	read: boolean;
	created_at: string;
};
