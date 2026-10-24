import { onKeyStroke } from '@vueuse/core';

/** True while typing, or while a dialog or menu is open over the board. */
export function busy(event: KeyboardEvent): boolean {
	const target = event.target as HTMLElement;

	return (
		event.metaKey ||
		event.ctrlKey ||
		event.altKey ||
		target.isContentEditable ||
		['INPUT', 'TEXTAREA', 'SELECT'].includes(target.tagName) ||
		document.querySelector(
			'[role="dialog"], [data-slot="popover-content"], [role="menu"]',
		) !== null
	);
}

function visibleCards(list: Element): HTMLElement[] {
	return Array.from(
		list.querySelectorAll<HTMLElement>('[data-card-id]'),
	).filter((card) => card.offsetParent !== null);
}

/**
 * Board shortcuts: n adds a card to the list you're in (or the first one),
 * arrow keys move between cards, Enter opens one (on the card itself).
 */
export function useBoardKeys() {
	onKeyStroke('n', (event) => {
		if (busy(event)) {
			return;
		}

		const lists = document.querySelectorAll('[data-stack]');
		const current =
			(document.activeElement?.closest(
				'[data-stack]',
			) as Element | null) ?? lists[0];
		const button = current?.querySelector<HTMLElement>('[data-add-card]');

		if (button) {
			event.preventDefault();
			button.click();
		}
	});

	onKeyStroke(
		['ArrowUp', 'ArrowDown', 'ArrowLeft', 'ArrowRight'],
		(event) => {
			if (busy(event) && !(event.target as HTMLElement).dataset.cardId) {
				return;
			}

			const lists = Array.from(
				document.querySelectorAll('[data-stack]'),
			).filter((list) => visibleCards(list).length > 0);
			const focused = (
				document.activeElement as HTMLElement | null
			)?.closest<HTMLElement>('[data-card-id]');

			if (lists.length === 0) {
				return;
			}

			event.preventDefault();

			if (!focused) {
				visibleCards(lists[0])[0]?.focus();

				return;
			}

			const list = focused.closest('[data-stack]');
			const column = lists.findIndex((candidate) => candidate === list);
			const cards = list ? visibleCards(list) : [];
			const row = cards.indexOf(focused);

			if (event.key === 'ArrowUp' || event.key === 'ArrowDown') {
				const next = cards[row + (event.key === 'ArrowDown' ? 1 : -1)];
				next?.focus();
				next?.scrollIntoView({ block: 'nearest' });

				return;
			}

			const target =
				lists[column + (event.key === 'ArrowRight' ? 1 : -1)];

			if (target) {
				const targetCards = visibleCards(target);
				const next = targetCards[Math.min(row, targetCards.length - 1)];
				next?.focus();
				next?.scrollIntoView({ block: 'nearest', inline: 'nearest' });
			}
		},
	);
}
