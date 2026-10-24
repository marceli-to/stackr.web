import { router } from '@inertiajs/vue3';
import type { InjectionKey, Ref } from 'vue';
import { inject } from 'vue';
import { show } from '@/routes/projects';
import type { Label, Member, Project } from '@/types';

/** What every card on a board can reach: the project, its labels and members. */
export type BoardContext = {
    project: Ref<Project>;
    labels: Ref<Label[]>;
    members: Ref<Member[]>;
};

export const boardKey: InjectionKey<BoardContext> = Symbol('board');

export function useBoard(): BoardContext {
    const board = inject(boardKey);

    if (!board) {
        throw new Error('useBoard() needs a board to provide its context.');
    }

    return board;
}

/** Opens a card by putting ?card=<id> in the URL; only the card reloads. */
export function openCard(projectId: number, cardId: number) {
    router.get(
        show.url(projectId, { query: { card: cardId } }),
        {},
        { preserveScroll: true, preserveState: true, only: ['card'] },
    );
}

export function closeCard(projectId: number) {
    router.get(
        show.url(projectId),
        {},
        { preserveScroll: true, preserveState: true, only: ['card'] },
    );
}

/** Saves changes to a card and keeps the board and the open card in place. */
export const keep = { preserveScroll: true, preserveState: true } as const;

const dayMonth = new Intl.DateTimeFormat(undefined, {
    day: 'numeric',
    month: 'short',
});
const dayMonthYear = new Intl.DateTimeFormat(undefined, {
    day: 'numeric',
    month: 'short',
    year: 'numeric',
});
const time = new Intl.DateTimeFormat(undefined, {
    hour: '2-digit',
    minute: '2-digit',
});

/** "24 Oct", with the year only when it is not this year, and the time on request. */
export function formatDate(iso: string, withTime = false): string {
    const date = new Date(iso);
    const day =
        date.getFullYear() === new Date().getFullYear()
            ? dayMonth.format(date)
            : dayMonthYear.format(date);

    return withTime ? `${day}, ${time.format(date)}` : day;
}

export type DueState = 'complete' | 'overdue' | 'soon' | 'later';

export function dueState(dueAt: string, completedAt: string | null): DueState {
    if (completedAt) {
        return 'complete';
    }

    const left = new Date(dueAt).getTime() - Date.now();

    if (left < 0) {
        return 'overdue';
    }

    return left < 24 * 60 * 60 * 1000 ? 'soon' : 'later';
}

const relative = new Intl.RelativeTimeFormat(undefined, { numeric: 'auto' });

/** "5 minutes ago", "yesterday", or the date once it is more than a week old. */
export function timeAgo(iso: string): string {
    const seconds = (new Date(iso).getTime() - Date.now()) / 1000;
    const steps: [Intl.RelativeTimeFormatUnit, number][] = [
        ['second', 60],
        ['minute', 60],
        ['hour', 24],
        ['day', 7],
    ];
    let value = seconds;

    for (const [unit, size] of steps) {
        if (Math.abs(value) < size) {
            return relative.format(Math.round(value), unit);
        }

        value /= size;
    }

    return formatDate(iso, true);
}

export function fileSize(bytes: number): string {
    if (bytes < 1024) {
        return `${bytes} B`;
    }

    const units = ['KB', 'MB', 'GB'];
    let value = bytes / 1024;
    let unit = 0;

    while (value >= 1024 && unit < units.length - 1) {
        value /= 1024;
        unit++;
    }

    return `${value.toFixed(value < 10 ? 1 : 0)} ${units[unit]}`;
}
