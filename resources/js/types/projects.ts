export type ProjectRole = 'owner' | 'member';

export type Project = {
    id: number;
    name: string;
    description: string | null;
};

export type ProjectSummary = Project & {
    role: ProjectRole;
    stacks_count: number;
    cards_count: number;
    updated_at: string;
};

export type ProjectLink = Pick<Project, 'id' | 'name'>;
