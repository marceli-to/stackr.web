import { createInertiaApp } from '@inertiajs/vue3';
import { initializeTheme } from '@/composables/useAppearance';
import AppLayout from '@/layouts/AppLayout.vue';
import AuthLayout from '@/layouts/AuthLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { initializeFlashToast } from '@/lib/flashToast';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

void createInertiaApp({
	title: (title) => (title ? `${title} - ${appName}` : appName),
	layout: (name) => {
		switch (true) {
			// The landing page and legal notice bring their own header and footer.
			case name.startsWith('site/'):
				return null;
			case name.startsWith('auth/'):
				return AuthLayout;
			case name.startsWith('settings/'):
				return [AppLayout, SettingsLayout];
			default:
				return AppLayout;
		}
	},
	withApp: (app) => {
		// Phosphor icons default to bold; small icons inside text pass
		// weight="regular".
		app.provide('weight', 'bold');

		app.directive('focus', {
			mounted: (el: HTMLElement, shouldFocus) => {
				if (shouldFocus.value !== false) {
					el.focus();
				}
			},
		});
	},
	progress: {
		color: '#4B5563',
	},
});

// This will set light / dark mode on page load...
initializeTheme();

// This will listen for flash toast data from the server...
initializeFlashToast();
