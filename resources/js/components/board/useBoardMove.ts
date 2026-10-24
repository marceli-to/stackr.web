import { router } from '@inertiajs/vue3';
import { toast } from 'vue-sonner';
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
		// A rejected move gets a toast instead of Inertia's full-page error overlay.
		onHttpException: (response) => {
			rejected(
				response.status === 403
					? "You can't move that here."
					: "Couldn't save the move.",
			);

			return false;
		},
		onNetworkError: () => {
			rejected("Couldn't save the move. Check your connection.");

			return false;
		},
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

function rejected(message: string) {
	// One toast, however many moves fail at once.
	toast.error(message, { id: 'move-rejected' });
}
