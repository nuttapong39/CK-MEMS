import './bootstrap';

import { createApp } from 'vue';
import { createPinia } from 'pinia';
import App from './App.vue';
import router from './router';
import { useAuthStore } from './stores/auth';

const pinia = createPinia();
const app = createApp(App);

app.use(pinia);
app.use(router);

// Hydrate auth state from localStorage before mount
const auth = useAuthStore();
auth.hydrate();

app.mount('#app');
