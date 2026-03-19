<script setup lang="ts">
import type { CompanyShowTab } from '@/pages/Company/types';

defineProps<{
    activeTab: CompanyShowTab;
}>();

const emit = defineEmits<{
    (event: 'update:activeTab', value: CompanyShowTab): void;
}>();

function setActiveTab(tab: CompanyShowTab): void {
    emit('update:activeTab', tab);
}
</script>

<template>
    <div class="mt-6 border-b border-slate-200 dark:border-slate-800">
        <nav class="flex gap-8 text-sm font-medium">
            <button
                type="button"
                class="pb-3 transition-colors cursor-pointer"
                :class="
                    activeTab === 'details'
                        ? 'border-b-2 border-orange-500 text-orange-600'
                        : 'text-slate-500 hover:text-slate-700 dark:hover:text-slate-200'
                "
                @click="setActiveTab('details')"
            >
                Info
            </button>
            <button
                type="button"
                class="pb-3 transition-colors cursor-pointer"
                :class="
                    activeTab === 'progress'
                        ? 'border-b-2 border-orange-500 text-orange-600'
                        : 'text-slate-500 hover:text-slate-700 dark:hover:text-slate-200'
                "
                @click="setActiveTab('progress')"
            >
                History
            </button>
            <button
                type="button"
                class="pb-3 transition-colors cursor-pointer"
                :class="
                    activeTab === 'tasks'
                        ? 'border-b-2 border-orange-500 text-orange-600'
                        : 'text-slate-500 hover:text-slate-700 dark:hover:text-slate-200'
                "
                @click="setActiveTab('tasks')"
            >
                Tasks
            </button>
            <button
                type="button"
                class="pb-3 transition-colors cursor-pointer"
                :class="
                    activeTab === 'stores'
                        ? 'border-b-2 border-orange-500 text-orange-600'
                        : 'text-slate-500 hover:text-slate-700 dark:hover:text-slate-200'
                "
                @click="setActiveTab('stores')"
            >
                Stores
            </button>
            <button
                type="button"
                class="pb-3 transition-colors cursor-pointer"
                :class="
                    activeTab === 'contacts'
                        ? 'border-b-2 border-orange-500 text-orange-600'
                        : 'text-slate-500 hover:text-slate-700 dark:hover:text-slate-200'
                "
                @click="setActiveTab('contacts')"
            >
                Contacts
            </button>
        </nav>
    </div>
</template>
