<script setup lang="ts">
import { type Company, createColumns } from '@/components/companies/columns';
import DataTable from '@/components/companies/DataTable.vue';
import CompanyFilters from '@/components/CompanyFilters.vue';
import InputError from '@/components/InputError.vue';
import LoadingOverlay from '@/components/LoadingOverlay.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Field, FieldLabel } from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import { useTableFilters } from '@/composables/useTableFilters';
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes';
import { show as showCompany } from '@/routes/company';
import { type BreadcrumbItem } from '@/types';
import { Form, Head, router } from '@inertiajs/vue3';
import {
    ChevronLeft,
    ChevronRight,
    ChevronsLeft,
    ChevronsRight,
    Plus,
} from 'lucide-vue-next';
import { computed, ref } from 'vue';

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
        type?: string;
        scope?: string;
        include_imported?: string;
        sort?: string;
        direction?: string;
    };
    filterOptions: {
        statuses: FilterOption[];
        ratings: FilterOption[];
        types: FilterOption[];
    };
}

const props = defineProps<Props>();

const { filters, resetFilters } = useTableFilters({
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
        type: typeof props.filters.type === 'string' ? props.filters.type : '',
        scope:
            typeof props.filters.scope === 'string' ? props.filters.scope : '',
        include_imported:
            typeof props.filters.include_imported === 'string'
                ? props.filters.include_imported
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

const isCreateCompanyOpen = ref(false);
</script>

<template>
    <Head title="Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="space-y-6 p-6">
            <LoadingOverlay />

            <div class="flex flex-wrap items-center justify-between gap-3">
                <CompanyFilters
                    v-model="filters"
                    :statuses="filterOptions.statuses"
                    :ratings="filterOptions.ratings"
                    :types="filterOptions.types"
                    @reset="resetFilters"
                />
                <Dialog v-model:open="isCreateCompanyOpen">
                    <DialogTrigger as-child>
                        <Button>
                            <Plus class="h-4 w-4" />
                            New Company
                        </Button>
                    </DialogTrigger>
                    <DialogContent class="sm:max-w-md">
                        <DialogHeader>
                            <DialogTitle>Create Company</DialogTitle>
                            <DialogDescription>
                                Enter the required info to create a company.
                            </DialogDescription>
                        </DialogHeader>
                        <Form
                            action="/company"
                            method="post"
                            class="grid gap-4"
                            :on-success="() => (isCreateCompanyOpen = false)"
                            v-slot="{ errors, processing }"
                        >
                            <Field>
                                <FieldLabel for="create_company_name"
                                    >Company Name</FieldLabel
                                >
                                <Input
                                    id="create_company_name"
                                    name="name"
                                    required
                                    placeholder="Acme Motors"
                                />
                                <InputError :message="errors.name" />
                            </Field>
                            <DialogFooter>
                                <Button :disabled="processing">Create</Button>
                            </DialogFooter>
                        </Form>
                    </DialogContent>
                </Dialog>
            </div>

            <DataTable
                :columns="columns"
                :data="companies.data"
                :sorting="currentSorting"
                :row-href="(company) => showCompany.url(company.id)"
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
