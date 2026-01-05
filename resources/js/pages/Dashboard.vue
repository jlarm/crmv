<script setup lang="ts">
import { computed } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes';
import { type BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import { useTableFilters } from '@/composables/useTableFilters';
import LoadingOverlay from '@/components/LoadingOverlay.vue';
import DealershipFilters from '@/components/DealershipFilters.vue';
import { Button } from '@/components/ui/button';
import {
    ChevronLeft,
    ChevronRight,
    ChevronsLeft,
    ChevronsRight,
} from 'lucide-vue-next';
import {
    type Dealership,
    createColumns,
} from '@/components/dealerships/columns';
import DataTable from '@/components/dealerships/DataTable.vue';

interface FilterOption {
    value: string;
    label: string;
}

interface Props {
    dealerships: {
        data: Dealership[];
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
    onlyProps: ['dealerships', 'filters'],
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
    const firstPageLink = props.dealerships.links.find(
        (link) => link.label === '1',
    );
    return firstPageLink?.url;
});

const lastPageUrl = computed(() => {
    const lastPageLink = props.dealerships.links
        .slice(0, -1)
        .reverse()
        .find((link) => !link.label.includes('Previous'));
    return lastPageLink?.url;
});

const prevPageUrl = computed(() => {
    const prevLink = props.dealerships.links.find((link) =>
        link.label.includes('Previous'),
    );
    return prevLink?.url;
});

const nextPageUrl = computed(() => {
    const nextLink = props.dealerships.links.find((link) =>
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

            <DealershipFilters
                v-model="filters"
                :statuses="filterOptions.statuses"
                :ratings="filterOptions.ratings"
                :has-active-filters="hasActiveFilters(['sort', 'direction'])"
                @reset="resetFilters"
            />

            <DataTable
                :columns="columns"
                :data="dealerships.data"
                :sorting="currentSorting"
            />

            <div class="flex items-center justify-between">
                <div class="text-sm text-muted-foreground">
                    Showing {{ dealerships.from || 0 }} to
                    {{ dealerships.to || 0 }} of {{ dealerships.total }} results
                </div>

                <div class="flex items-center gap-2">
                    <Button
                        variant="outline"
                        size="icon"
                        :disabled="
                            !firstPageUrl || dealerships.current_page === 1
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
                            Page {{ dealerships.current_page }} of
                            {{ dealerships.last_page }}
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
                            dealerships.current_page === dealerships.last_page
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
