<script setup lang="ts">
import { Head, usePage } from '@inertiajs/vue3';
import { computed, nextTick, ref } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import { show } from '@/routes/company';
import CompanyShowTabs from '@/components/company/show/CompanyShowTabs.vue';
import CompanyDetailsTab from '@/components/company/show/CompanyDetailsTab.vue';
import CompanyProgressTab from '@/components/company/show/CompanyProgressTab.vue';
import CompanyStoresTab from '@/components/company/show/CompanyStoresTab.vue';
import CompanyContactsTab from '@/components/company/show/CompanyContactsTab.vue';
import { Badge } from '@/components/ui/badge';
import type { Company, CompanyShowTab, User } from '@/pages/Company/types';

interface Props {
    company: Company;
    allUsers: User[];
}

const props = defineProps<Props>();

const page = usePage();

const company = computed(() => {
    return (
        props.company ??
        ((page.props.value?.company as Company | undefined) ?? null)
    );
});

const breadcrumbItems = computed<BreadcrumbItem[]>(() => {
    if (!company.value?.id) {
        return [];
    }

    return [
        {
            title: company.value.name,
            href: show(company.value.id).url,
        },
    ];
});

const activeTab = ref<CompanyShowTab>('details');
const currentCompany = computed(() => company.value as Company);
const tabScrollPositions = ref<Record<CompanyShowTab, number>>({
    details: 0,
    progress: 0,
    stores: 0,
    contacts: 0,
});

function setActiveTab(tab: CompanyShowTab): void {
    tabScrollPositions.value[activeTab.value] = window.scrollY;
    const targetScrollTop = tabScrollPositions.value[tab] ?? window.scrollY;
    activeTab.value = tab;

    nextTick(() => {
        requestAnimationFrame(() => {
            window.scrollTo({ top: targetScrollTop, behavior: 'auto' });
        });
    });
}
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <div v-if="!company?.id" class="px-8 py-6 text-sm text-slate-500">
            Loading company...
        </div>

        <Head :title="company?.name ?? 'Company'" />

        <div v-if="company?.id" class="px-8 py-3">
            <div class="flex shrink-0 items-center justify-between gap-4">
                <div class="flex flex-col">
                    <h1 class="text-2xl font-black text-slate-900 dark:text-slate-100">
                        {{ company.name }}
                    </h1>
                    <div class="mt-1 flex items-center gap-1">
                        <p class="text-xs text-zinc-400 dark:text-zinc-500">
                            ID: {{ company.id }}
                        </p>
                        <Badge variant="secondary" class="ml-2">{{ company.status }}</Badge>
                        <Badge variant="secondary" class="ml-2">{{ company.rating }}</Badge>
                    </div>
                </div>
            </div>

            <CompanyShowTabs
                :active-tab="activeTab"
                @update:active-tab="setActiveTab"
            />

            <CompanyDetailsTab
                v-if="activeTab === 'details'"
                :company="currentCompany"
                :all-users="props.allUsers"
            />

            <CompanyProgressTab
                v-if="activeTab === 'progress'"
                :company="currentCompany"
            />

            <CompanyStoresTab
                v-if="activeTab === 'stores'"
                :company="currentCompany"
            />

            <CompanyContactsTab
                v-if="activeTab === 'contacts'"
                :company="currentCompany"
            />
        </div>
    </AppLayout>
</template>
