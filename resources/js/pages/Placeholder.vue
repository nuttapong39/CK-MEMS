<script setup>
import { useRoute } from 'vue-router';
import { computed } from 'vue';

const route = useRoute();
const title = computed(() => route.meta.title || 'หน้านี้');
</script>

<template>
    <div class="card-base p-12 text-center">
        <div class="w-16 h-16 mx-auto rounded-2xl bg-slate-100 flex items-center justify-center mb-4">
            🛠️
        </div>
        <h2 class="text-lg font-semibold text-slate-800">{{ title }}</h2>
        <p class="text-sm text-slate-500 mt-2">หน้านี้กำลังพัฒนา จะพร้อมใน Phase ถัดไป</p>
    </div>
</template>
