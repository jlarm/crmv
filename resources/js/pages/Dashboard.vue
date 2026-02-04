<script setup lang="ts">
import { computed } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes';
import { type BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import { useTableFilters } from '@/composables/useTableFilters';
import LoadingOverlay from '@/components/LoadingOverlay.vue';
import CompanyFilters from '@/components/CompanyFilters.vue';
import { Button } from '@/components/ui/button';
import {
    ChevronLeft,
    ChevronRight,
    ChevronsLeft,
    ChevronsRight,
} from 'lucide-vue-next';
import {
    type Company,
    createColumns,
} from '@/components/companies/columns';
import DataTable from '@/components/companies/DataTable.vue';

interface FilterOption {
    value: string;
    label: string;
}

interface Props {
    companies: {
        data: Company[];
        links: Array<{ url: string | null; label: string; active: boolean }>;
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
        from: number;
        to: number;
    };
    filters: {
        search?: string;
        status?: string;
        rating?: string;
        sort?: string;
        direction?: string;
    };
    filterOptions: {
        statuses: FilterOption[];
        ratings: FilterOption[];
    };
}

const props = defineProps<Props>();

const { filters, resetFilters, hasActiveFilters } = useTableFilters({
    routeUrl: dashboard().url,
    initialFilters: {
        search:
            typeof props.filters.search === 'string'
                ? props.filters.search
                : '',
        status:
            typeof props.filters.status === 'string'
                ? props.filters.status
                : '',
        rating:
            typeof props.filters.rating === 'string'
                ? props.filters.rating
                : '',
        sort: typeof props.filters.sort === 'string' ? props.filters.sort : '',
        direction:
            typeof props.filters.direction === 'string'
                ? props.filters.direction
                : 'asc',
    },
    debounceMs: 500,
    onlyProps: ['companies', 'filters'],
});

function handleSort(column: string): void {
    if (filters.value.sort === column) {
        // Already sorting by this column, toggle direction
        filters.value.direction =
            filters.value.direction === 'asc' ? 'desc' : 'asc';
    } else {
        // New column, start with asc
        filters.value.sort = column;
        filters.value.direction = 'asc';
    }
}

const currentSorting = computed(() => ({
    column: filters.value.sort || '',
    direction: (filters.value.direction || 'asc') as 'asc' | 'desc',
}));

const columns = createColumns(handleSort);

function goToPage(url: string | null): void {
    if (!url) return;
    router.visit(url, {
        preserveState: true,
        preserveScroll: true,
    });
}

const firstPageUrl = computed(() => {
    const firstPageLink = props.companies.links.find(
        (link) => link.label === '1',
    );
    return firstPageLink?.url;
});

const lastPageUrl = computed(() => {
    const lastPageLink = props.companies.links
        .slice(0, -1)
        .reverse()
        .find((link) => !link.label.includes('Previous'));
    return lastPageLink?.url;
});

const prevPageUrl = computed(() => {
    const prevLink = props.companies.links.find((link) =>
        link.label.includes('Previous'),
    );
    return prevLink?.url;
});

const nextPageUrl = computed(() => {
    const nextLink = props.companies.links.find((link) =>
        link.label.includes('Next'),
    );
    return nextLink?.url;
});

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: dashboard().url,
    },
];
</script>

<template>
    <Head title="Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="space-y-6 p-6">
            <LoadingOverlay />

            <CompanyFilters
                v-model="filters"
                :statuses="filterOptions.statuses"
                :ratings="filterOptions.ratings"
                :has-active-filters="hasActiveFilters(['sort', 'direction'])"
                @reset="resetFilters"
            />

            <DataTable
                :columns="columns"
                :data="companies.data"
                :sorting="currentSorting"
            />

            <div class="flex items-center justify-between">
                <div class="text-sm text-muted-foreground">
                    Showing {{ companies.from || 0 }} to
                    {{ companies.to || 0 }} of {{ companies.total }} results
                </div>

                <div class="flex items-center gap-2">
                    <Button
                        variant="outline"
                        size="icon"
                        :disabled="
                            !firstPageUrl || companies.current_page === 1
                        "
                        @click="goToPage(firstPageUrl)"
                    >
                        <ChevronsLeft class="h-4 w-4" />
                    </Button>
                    <Button
                        variant="outline"
                        size="icon"
                        :disabled="!prevPageUrl"
                        @click="goToPage(prevPageUrl)"
                    >
                        <ChevronLeft class="h-4 w-4" />
                    </Button>

                    <div class="flex items-center gap-1">
                        <span class="text-sm">
                            Page {{ companies.current_page }} of
                            {{ companies.last_page }}
                        </span>
                    </div>

                    <Button
                        variant="outline"
                        size="icon"
                        :disabled="!nextPageUrl"
                        @click="goToPage(nextPageUrl)"
                    >
                        <ChevronRight class="h-4 w-4" />
                    </Button>
                    <Button
                        variant="outline"
                        size="icon"
                        :disabled="
                            !lastPageUrl ||
                            companies.current_page === companies.last_page
                        "
                        @click="goToPage(lastPageUrl)"
                    >
                        <ChevronsRight class="h-4 w-4" />
                    </Button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
