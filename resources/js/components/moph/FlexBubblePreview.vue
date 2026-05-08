<script setup>
import { computed } from 'vue';

const props = defineProps({ payload: { type: Object, required: true } });

const bubble = computed(() => props.payload?.contents || props.payload);

function renderBox(box) {
    return box;
}
</script>

<template>
    <div class="max-w-[300px] mx-auto bg-white rounded-2xl overflow-hidden shadow-2xl ring-1 ring-black/5">
        <!-- Header -->
        <div v-if="bubble?.header"
            :style="{ backgroundColor: bubble.header.backgroundColor || 'transparent' }"
            class="text-center"
        >
            <div v-if="bubble.header.contents" class="p-3">
                <template v-for="(c, i) in bubble.header.contents" :key="i">
                    <div v-if="c.type === 'text'"
                        :style="{ color: c.color || '#FFFFFF', fontWeight: c.weight === 'bold' ? '700' : '400', fontSize: c.size === 'lg' ? '17px' : '14px' }">
                        {{ c.text }}
                    </div>
                    <img v-else-if="c.type === 'image'" :src="c.url" class="w-full block" :style="{ aspectRatio: (c.aspectRatio || '1:1').replace(':','/') }" />
                </template>
            </div>
        </div>

        <!-- Body -->
        <div v-if="bubble?.body" class="p-4 space-y-3">
            <template v-for="(c, i) in (bubble.body.contents || [])" :key="i">
                <div v-if="c.type === 'text'"
                    :class="[c.weight === 'bold' ? 'font-bold' : '']"
                    :style="{
                        color: c.color || '#1f2937',
                        fontSize: c.size === 'sm' ? '13px' : c.size === 'lg' ? '17px' : c.size === 'xl' ? '20px' : '14px',
                        textAlign: c.align || 'left',
                    }"
                >
                    {{ c.text }}
                </div>
                <hr v-else-if="c.type === 'separator'" class="border-slate-200" />
                <img v-else-if="c.type === 'image'" :src="c.url" class="rounded-md" :style="{ maxWidth: '100%' }" />
                <div v-else-if="c.type === 'box'"
                    :class="['flex', c.layout === 'horizontal' ? 'flex-row gap-2 items-center' : 'flex-col gap-1']"
                    :style="{
                        backgroundColor: c.backgroundColor || 'transparent',
                        borderRadius: c.cornerRadius || 0,
                        padding: ['paddingAll','paddingTop','paddingBottom','paddingStart','paddingEnd'].some(p => c[p]) ? '0.5rem' : '0',
                    }"
                >
                    <template v-for="(cc, j) in (c.contents || [])" :key="j">
                        <div v-if="cc.type === 'text'"
                            :class="[cc.weight === 'bold' ? 'font-bold' : '']"
                            :style="{
                                color: cc.color || '#1f2937',
                                fontSize: cc.size === 'sm' ? '13px' : cc.size === 'lg' ? '17px' : '14px',
                                textAlign: cc.align || 'left',
                                flex: cc.flex,
                            }"
                        >
                            {{ cc.text }}
                        </div>
                        <hr v-else-if="cc.type === 'separator'" class="border-slate-200" />
                    </template>
                </div>
            </template>
        </div>

        <!-- Footer -->
        <div v-if="bubble?.footer" class="border-t border-slate-100 p-3 text-xs text-slate-500">
            <template v-for="(c, i) in (bubble.footer.contents || [])" :key="i">
                <div v-if="c.type === 'button'"
                    class="px-3 py-2 rounded-lg text-center font-medium"
                    :style="{
                        backgroundColor: c.style === 'primary' ? '#3B82F6' : 'transparent',
                        color: c.style === 'primary' ? 'white' : '#3B82F6',
                        border: c.style === 'secondary' ? '1px solid #3B82F6' : 'none',
                    }"
                >
                    {{ c.action?.label || 'Button' }}
                </div>
            </template>
        </div>
    </div>
</template>
