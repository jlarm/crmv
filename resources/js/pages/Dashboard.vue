<script setup lang="ts">
import { type Company, createColumns } from '@/components/companies/columns';
import DataTable from '@/components/companies/DataTable.vue';
import CompanyFilters from '@/components/CompanyFilters.vue';
import InputError from '@/components/InputError.vue';
import LoadingOverlay from '@/components/LoadingOverlay.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
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
import { Form, Head, Link, router } from '@inertiajs/vue3';
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
    upcomingProgresses: DashboardProgress[];
    pastDueProgresses: DashboardProgress[];
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

interface DashboardProgress {
    id: number;
    details: string;
    date: string | null;
    company: {
        id: number;
        name: string;
    };
    contact: {
        id: number;
        name: string;
    } | null;
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
            typeof props.filters.scope === 'string' &&
            ['mine', 'all'].includes(props.filters.scope)
                ? props.filters.scope
                : 'mine',
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
    storageKey: 'dashboard-company-filters',
    persistedKeys: ['scope'],
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

function formatProgressDate(value: string | null): string {
    if (!value) {
        return 'No due date';
    }

    const date = new Date(`${value}T00:00:00`);

    if (Number.isNaN(date.getTime())) {
        return value;
    }

    return new Intl.DateTimeFormat('en-US', {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
    }).format(date);
}

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

            <div class="grid gap-4 xl:grid-cols-2">
                <Card>
                    <CardHeader class="space-y-1">
                        <CardTitle>Upcoming History</CardTitle>
                        <p class="text-sm text-muted-foreground">
                            Open history items due within the next 7 days.
                        </p>
                    </CardHeader>
                    <CardContent class="max-h-[16rem] overflow-y-auto pr-2">
                        <div
                            v-if="upcomingProgresses.length > 0"
                            class="space-y-3"
                        >
                            <Link
                                v-for="progress in upcomingProgresses"
                                :key="progress.id"
                                :href="showCompany.url(progress.company.id)"
                                class="block rounded-lg border border-border p-4 transition hover:bg-muted/40"
                            >
                                <div
                                    class="flex items-start justify-between gap-3"
                                >
                                    <div class="space-y-1">
                                        <p class="text-sm font-medium">
                                            {{ progress.details }}
                                        </p>
                                        <p
                                            class="text-sm text-muted-foreground"
                                        >
                                            {{ progress.company.name }}
                                            <span v-if="progress.contact">
                                                · {{ progress.contact.name }}
                                            </span>
                                        </p>
                                    </div>
                                    <div
                                        class="shrink-0 text-xs font-medium tracking-wide text-muted-foreground uppercase"
                                    >
                                        {{ formatProgressDate(progress.date) }}
                                    </div>
                                </div>
                            </Link>
                        </div>
                        <p v-else class="text-sm text-muted-foreground">
                            No upcoming history items due in the next 7 days.
                        </p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="space-y-1">
                        <CardTitle>Past Due History</CardTitle>
                        <p class="text-sm text-muted-foreground">
                            Open history items with due dates before today.
                        </p>
                    </CardHeader>
                    <CardContent class="max-h-[16rem] overflow-y-auto pr-2">
                        <div
                            v-if="pastDueProgresses.length > 0"
                            class="space-y-3"
                        >
                            <Link
                                v-for="progress in pastDueProgresses"
                                :key="progress.id"
                                :href="showCompany.url(progress.company.id)"
                                class="block rounded-lg border border-destructive/20 bg-destructive/5 p-4 transition hover:bg-destructive/10"
                            >
                                <div
                                    class="flex items-start justify-between gap-3"
                                >
                                    <div class="space-y-1">
                                        <p class="text-sm font-medium">
                                            {{ progress.details }}
                                        </p>
                                        <p
                                            class="text-sm text-muted-foreground"
                                        >
                                            {{ progress.company.name }}
                                            <span v-if="progress.contact">
                                                · {{ progress.contact.name }}
                                            </span>
                                        </p>
                                    </div>
                                    <div
                                        class="shrink-0 text-xs font-medium tracking-wide text-destructive uppercase"
                                    >
                                        {{ formatProgressDate(progress.date) }}
                                    </div>
                                </div>
                            </Link>
                        </div>
                        <p v-else class="text-sm text-muted-foreground">
                            No past due history items.
                        </p>
                    </CardContent>
                </Card>
            </div>

            <div class="flex flex-wrap items-center justify-between gap-3">
                <div class="flex flex-wrap items-center gap-3">
                    <div
                        class="inline-flex rounded-md border border-border bg-background p-1"
                    >
                        <Button
                            type="button"
                            size="sm"
                            :variant="
                                filters.scope === 'all' ? 'ghost' : 'secondary'
                            "
                            @click="filters.scope = 'mine'"
                        >
                            My companies
                        </Button>
                        <Button
                            type="button"
                            size="sm"
                            :variant="
                                filters.scope === 'all' ? 'secondary' : 'ghost'
                            "
                            @click="filters.scope = 'all'"
                        >
                            All companies
                        </Button>
                    </div>

                    <CompanyFilters
                        v-model="filters"
                        :statuses="filterOptions.statuses"
                        :ratings="filterOptions.ratings"
                        :types="filterOptions.types"
                        @reset="resetFilters"
                    />
                </div>

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
