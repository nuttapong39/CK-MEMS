<script setup>
import { RouterView } from 'vue-router';
</script>

<template>
    <div class="min-h-screen relative flex items-center justify-center p-6">
        <!-- Background image with overlay -->
        <div
            class="absolute inset-0 bg-cover bg-center bg-no-repeat"
            style="background-image: url('/img/bg.jpg')"
        ></div>
        <!-- Dark overlay to reduce image prominence -->
        <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-[2px]"></div>

        <!-- Content (z-above background) -->
        <div class="relative z-10 w-full flex justify-center lg:justify-end lg:pr-[10%]">
            <RouterView />
        </div>
    </div>
</template>
