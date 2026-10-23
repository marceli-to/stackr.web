import { router } from '@inertiajs/vue3';
import type { RouteDefinition } from '@/wayfinder';

let pending = 0;

/**
 * Saves a drag & drop move in the background. The board has already moved
 * the item, so `resync` only runs once every move has finished: it adopts
 * the server's order, which also undoes a move the server rejected.
 */
export function saveMove(
    route: RouteDefinition<'patch'>,
    data: { index: number; stack_id?: number },
    resync: () => void,
) {
    pending++;

    router.patch(route.url, data, {
        async: true,
        preserveScroll: true,
        preserveState: true,
        onFinish: () => {
            pending--;

            if (pending === 0) {
                resync();
            }
        },
    });
}

export function hasPendingMoves() {
    return pending > 0;
}
