<script setup>
import { ref } from 'vue';
import { RouterView } from 'vue-router';
import Sidebar from '../components/layout/Sidebar.vue';
import Topbar from '../components/layout/Topbar.vue';

const sidebarOpen = ref(true);
</script>

<template>
    <div class="min-h-screen flex bg-slate-50">
        <Sidebar :open="sidebarOpen" @toggle="sidebarOpen = !sidebarOpen" />

        <div class="flex-1 flex flex-col min-w-0">
            <Topbar />
            <main class="flex-1 p-6 lg:p-8 overflow-y-auto">
                <RouterView />
            </main>
        </div>
    </div>
</template>
