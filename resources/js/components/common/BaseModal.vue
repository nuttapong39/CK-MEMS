<script setup>
import {
    Dialog, DialogPanel, DialogTitle,
    TransitionRoot, TransitionChild,
} from '@headlessui/vue';
import { XMarkIcon } from '@heroicons/vue/24/outline';

defineProps({
    open: { type: Boolean, default: false },
    title: { type: String, default: '' },
    subtitle: { type: String, default: '' },
    size: { type: String, default: 'md' }, // sm | md | lg | xl | 2xl | 3xl
});
const emit = defineEmits(['close']);

const sizeMap = {
    sm: 'max-w-sm', md: 'max-w-md', lg: 'max-w-lg',
    xl: 'max-w-xl', '2xl': 'max-w-2xl', '3xl': 'max-w-3xl',
};
</script>

<template>
    <TransitionRoot :show="open" as="template">
        <Dialog as="div" class="relative z-50" @close="emit('close')">
            <TransitionChild
                as="template"
                enter="duration-200 ease-out" enter-from="opacity-0" enter-to="opacity-100"
                leave="duration-150 ease-in" leave-from="opacity-100" leave-to="opacity-0"
            >
                <div class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm" />
            </TransitionChild>

            <div class="fixed inset-0 overflow-y-auto">
                <div class="flex min-h-full items-center justify-center p-4">
                    <TransitionChild
                        as="template"
                        enter="duration-200 ease-out" enter-from="opacity-0 scale-95"
                        enter-to="opacity-100 scale-100"
                        leave="duration-150 ease-in" leave-from="opacity-100 scale-100"
                        leave-to="opacity-0 scale-95"
                    >
                        <DialogPanel :class="['w-full transform overflow-hidden rounded-2xl bg-white shadow-xl ring-1 ring-slate-100 transition-all', sizeMap[size] ?? 'max-w-md']">
                            <div class="flex items-start justify-between p-5 border-b border-slate-100">
                                <div>
                                    <DialogTitle class="text-lg font-bold text-slate-800">{{ title }}</DialogTitle>
                                    <p v-if="subtitle" class="text-xs text-slate-500 mt-1">{{ subtitle }}</p>
                                </div>
                                <button
                                    @click="emit('close')"
                                    class="p-1.5 rounded-lg text-slate-400 hover:bg-slate-50 hover:text-slate-600 transition-colors"
                                >
                                    <XMarkIcon class="w-5 h-5" />
                                </button>
                            </div>
                            <div class="p-5 max-h-[70vh] overflow-y-auto">
                                <slot />
                            </div>
                            <div v-if="$slots.footer" class="p-4 border-t border-slate-100 bg-slate-50/50 flex justify-end gap-2">
                                <slot name="footer" />
                            </div>
                        </DialogPanel>
                    </TransitionChild>
                </div>
            </div>
        </Dialog>
    </TransitionRoot>
</template>
