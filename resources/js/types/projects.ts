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
	start_at: string | null;
	due_at: string | null;
	completed_at: string | null;
	labels: number[];
	members: number[];
	cover: { url: string; width: number | null; height: number | null } | null;
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

/** An opened card: everything the card dialog shows. */
export type CardDetail = {
	id: number;
	title: string;
	description: string | null;
	description_html: string | null;
	start_at: string | null;
	due_at: string | null;
	completed_at: string | null;
	created_at: string;
	stack: { id: number; name: string };
	labels: number[];
	members: number[];
	cover_attachment_id: number | null;
	checklists: Checklist[];
	attachments: CardAttachment[];
	comments: CardComment[];
};

export type BoardStack = {
	id: number;
	name: string;
	cards: BoardCard[];
};
