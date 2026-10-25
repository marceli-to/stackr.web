<script setup lang="ts">
import type { User } from '@/types';

type Props = {
	user: User;
	showEmail?: boolean;
};

withDefaults(defineProps<Props>(), {
	showEmail: false,
});
</script>

<template>
	<div class="grid flex-1 text-left text-sm leading-tight">
		<span class="truncate font-medium">{{ user.name }}</span>
		<span v-if="showEmail" class="truncate text-sm opacity-70">{{
			user.email
		}}</span>
	</div>
</template>
