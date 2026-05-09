<script setup>
import { ref } from 'vue';
import { RouterView } from 'vue-router';
import Sidebar from '../components/layout/Sidebar.vue';
import Topbar  from '../components/layout/Topbar.vue';

// Open by default on desktop, closed on mobile
const sidebarOpen = ref(typeof window !== 'undefined' ? window.innerWidth >= 1024 : true);

function toggleSidebar() {
    sidebarOpen.value = !sidebarOpen.value;
}
</script>

<template>
    <div class="min-h-screen flex th-body overflow-hidden">

        <!-- Mobile backdrop (click to close) -->
        <Transition
            enter-active-class="transition-opacity duration-300"
            leave-active-class="transition-opacity duration-300"
            enter-from-class="opacity-0"
            leave-to-class="opacity-0"
        >
            <div
                v-if="sidebarOpen"
                class="fixed inset-0 bg-black/50 z-30 lg:hidden"
                @click="sidebarOpen = false"
            />
        </Transition>

        <Sidebar :open="sidebarOpen" @close="sidebarOpen = false" />

        <!-- Main content -->
        <div class="flex-1 flex flex-col min-w-0 overflow-hidden">
            <Topbar @toggle="toggleSidebar" />
            <main class="flex-1 p-4 sm:p-6 lg:p-8 overflow-y-auto">
                <RouterView />
            </main>
        </div>
    </div>
</template>
