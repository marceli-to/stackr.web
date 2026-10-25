<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { PhCheck } from '@phosphor-icons/vue';
import { computed } from 'vue';
import CardController from '@/actions/App/Http/Controllers/CardController';
import { colorNames, keep } from '@/components/card/board';
import { Button } from '@/components/ui/button';
import {
	Popover,
	PopoverContent,
	PopoverTrigger,
} from '@/components/ui/popover';
import { labelColors } from '@/types';
import type { CardDetail, LabelColor } from '@/types';

const props = defineProps<{
	card: CardDetail;
}>();

const images = computed(() =>
	props.card.attachments.filter((attachment) => attachment.is_image),
);

function save(data: {
	cover_color?: LabelColor | null;
	cover_attachment_id?: number | null;
}) {
	router.patch(CardController.update(props.card.id).url, data, keep);
}

function remove() {
	save({ cover_color: null, cover_attachment_id: null });
}
</script>

<template>
	<Popover>
		<PopoverTrigger as-child>
			<slot />
		</PopoverTrigger>
		<PopoverContent class="w-72">
			<p class="mb-3 font-medium">Cover</p>

			<p class="mb-1.5 text-sm font-medium text-muted-foreground">
				Colours
			</p>
			<div class="grid grid-cols-4 gap-1.5">
				<button
					v-for="color in labelColors"
					:key="color"
					type="button"
					:data-label="color"
					class="flex h-9 items-center justify-center rounded-[var(--radius-sheet)] bg-label text-label-ink hover:brightness-95 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ring focus-visible:outline-solid"
					:aria-label="colorNames[color]"
					:aria-pressed="card.cover_color === color"
					@click="save({ cover_color: color })"
				>
					<PhCheck v-if="card.cover_color === color" class="size-4" />
				</button>
			</div>

			<template v-if="images.length">
				<p
					class="mt-4 mb-1.5 text-sm font-medium text-muted-foreground"
				>
					Images
				</p>
				<div class="grid grid-cols-3 gap-1.5">
					<button
						v-for="image in images"
						:key="image.id"
						type="button"
						class="relative aspect-[4/3] overflow-hidden rounded-[var(--radius-sheet)] bg-(--color-paper-3) focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ring focus-visible:outline-solid"
						:aria-label="`Use ${image.name}`"
						:aria-pressed="card.cover_attachment_id === image.id"
						@click="save({ cover_attachment_id: image.id })"
					>
						<img
							:src="image.thumb_url ?? image.url"
							alt=""
							loading="lazy"
							class="size-full object-cover"
						/>
						<span
							v-if="card.cover_attachment_id === image.id"
							class="absolute inset-0 flex items-center justify-center bg-black/35 text-white"
						>
							<PhCheck class="size-5" />
						</span>
					</button>
				</div>
			</template>

			<Button
				v-if="card.cover_color || card.cover_attachment_id"
				variant="secondary"
				class="mt-4 w-full"
				@click="remove"
			>
				Remove cover
			</Button>
		</PopoverContent>
	</Popover>
</template>
