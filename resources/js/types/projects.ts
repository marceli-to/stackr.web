export type ProjectRole = 'owner' | 'member';

export const projectColors = [
    'cobalt',
    'signal',
    'moss',
    'oxblood',
    'lagoon',
    'ochre',
] as const;

export type ProjectColor = (typeof projectColors)[number];

export type Project = {
    id: number;
    name: string;
    description: string | null;
    color: ProjectColor;
};

export type ProjectSummary = Project & {
    role: ProjectRole;
    stacks_count: number;
    cards_count: number;
    updated_at: string;
};

export type ProjectLink = Pick<Project, 'id' | 'name' | 'color'>;

export type BoardCard = {
    id: number;
    title: string;
    due_at: string | null;
};

export type BoardStack = {
    id: number;
    name: string;
    cards: BoardCard[];
};
