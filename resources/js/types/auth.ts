export type User = {
	id: number;
	name: string;
	email: string;
	avatar?: string;
	email_verified_at: string | null;
	email_notifications?: boolean;
	two_factor_enabled?: boolean;
	created_at: string;
	updated_at: string;
	[key: string]: unknown;
};

export type Auth = {
	user: User;
};

export type Passkey = {
	id: number;
	name: string;
	authenticator: string | null;
	created_at_diff: string;
	last_used_at_diff: string | null;
};

export type TwoFactorConfigContent = {
	title: string;
	description: string;
	buttonText: string;
};

/** A personal access token for an agent, as Settings → Agents lists it. */
export type AgentToken = {
	id: number;
	name: string;
	created_at_diff: string | null;
	last_used_at_diff: string | null;
};

/** A token just created; the plain text is flashed once. */
export type NewAgentToken = {
	id: number;
	name: string;
	token: string;
};
