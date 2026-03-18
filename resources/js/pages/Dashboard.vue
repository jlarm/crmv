<script setup lang="ts">
import { type Company, createColumns } from '@/components/companies/columns';
import DataTable from '@/components/companies/DataTable.vue';
import CompanyFilters from '@/components/CompanyFilters.vue';
import DashboardPagination from '@/components/dashboard/DashboardPagination.vue';
import DashboardTaskCard, {
    type DashboardTask,
} from '@/components/dashboard/DashboardTaskCard.vue';
import {
    NativeSelect,
    NativeSelectOption,
} from '@/components/ui/native-select';
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
import { Form, Head } from '@inertiajs/vue3';
import { Plus } from 'lucide-vue-next';
import { computed, ref } from 'vue';

interface FilterOption {
    value: string;
    label: string;
}

interface Props {
    upcomingTasks: DashboardTask[];
    pastDueTasks: DashboardTask[];
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
        upcoming_days?: string;
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
        upcoming_days:
            typeof props.filters.upcoming_days === 'string' &&
            ['7', '14', '30'].includes(props.filters.upcoming_days)
                ? props.filters.upcoming_days
                : '7',
    },
    debounceMs: 500,
    onlyProps: ['companies', 'filters', 'upcomingTasks', 'pastDueTasks'],
    storageKey: 'dashboard-company-filters',
    persistedKeys: ['scope'],
});

function handleSort(column: string): void {
    if (filters.value.sort === column) {
        filters.value.direction =
            filters.value.direction === 'asc' ? 'desc' : 'asc';
    } else {
        filters.value.sort = column;
        filters.value.direction = 'asc';
    }
}

const currentSorting = computed(() => ({
    column: filters.value.sort || '',
    direction: (filters.value.direction || 'asc') as 'asc' | 'desc',
}));

const columns = createColumns(handleSort);

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
                <DashboardTaskCard
                    title="Upcoming Tasks"
                    :description="`Open tasks due within the next ${filters.upcoming_days} days.`"
                    :empty-message="`No upcoming tasks due in the next ${filters.upcoming_days} days.`"
                    :tasks="upcomingTasks"
                >
                    <template #header-action>
                        <NativeSelect
                            v-model="filters.upcoming_days"
                            class="h-7 py-0 text-xs"
                        >
                            <NativeSelectOption value="7">7 days</NativeSelectOption>
                            <NativeSelectOption value="14">14 days</NativeSelectOption>
                            <NativeSelectOption value="30">30 days</NativeSelectOption>
                        </NativeSelect>
                    </template>
                </DashboardTaskCard>
                <DashboardTaskCard
                    title="Past Due Tasks"
                    description="Open tasks with due dates before today."
                    empty-message="No past due tasks."
                    :tasks="pastDueTasks"
                    variant="destructive"
                />
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

            <DashboardPagination
                :current-page="companies.current_page"
                :last-page="companies.last_page"
                :from="companies.from"
                :to="companies.to"
                :total="companies.total"
                :links="companies.links"
            />
        </div>
    </AppLayout>
</template>
