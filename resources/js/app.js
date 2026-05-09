import './bootstrap';

import { createApp } from 'vue';
import { createPinia } from 'pinia';
import App from './App.vue';
import router from './router';
import { useAuthStore } from './stores/auth';
import { useSettingsStore } from './stores/settings';

// ── Apply saved theme immediately (before Vue mounts) to avoid flash ──
const savedTheme = localStorage.getItem('ck-theme') || 'light';
document.documentElement.setAttribute('data-theme', savedTheme);

const pinia = createPinia();
const app = createApp(App);

app.use(pinia);
app.use(router);

// Hydrate auth state from localStorage before mount
const auth = useAuthStore();
auth.hydrate();

// Load public system settings (system name, logo, theme) — no auth needed
const settings = useSettingsStore();
settings.fetchPublic();

app.mount('#app');
