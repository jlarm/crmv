<script setup lang="ts">
import InputError from '@/components/InputError.vue';
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
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import { Calendar } from '@/components/ui/calendar';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';
import type { CompanySearchResult, SelectOption, TaskFormOptions } from '@/pages/Tasks/types';
import { useForm } from '@inertiajs/vue3';
import { getLocalTimeZone } from '@internationalized/date';
import { CalendarIcon } from 'lucide-vue-next';
import type { DateValue } from 'reka-ui';
import { toDate } from 'reka-ui/date';
import { computed, onBeforeUnmount, ref, watch } from 'vue';

const props = defineProps<{
    formOptions: TaskFormOptions;
}>();

const isOpen = ref(false);
const dueDateValue = ref<DateValue | undefined>(undefined);

const dueDateLabel = computed(() => {
    if (!dueDateValue.value) {
        return 'Pick a date';
    }

    return new Intl.DateTimeFormat('en-US', {
        month: 'short',
        day: '2-digit',
        year: 'numeric',
    }).format(toDate(dueDateValue.value, getLocalTimeZone()));
});

const companySearch = ref('');
const companyResults = ref<CompanySearchResult[]>([]);
const selectedCompany = ref<CompanySearchResult | null>(null);
const availableStores = ref<SelectOption[]>([]);
const isSearchingCompanies = ref(false);

let searchTimeout: number | null = null;
let searchRequestId = 0;

const form = useForm({
    name: '',
    description: '',
    task_type: props.formOptions.taskTypes[0] ?? 'Call',
    priority: 'Medium',
    status: 'Open',
    due_date: '',
    company_id: '',
    assigned_to: '',
    store_id: '',
});

function formatCompanyLabel(company: CompanySearchResult): string {
    const location = [company.city, company.state].filter(Boolean).join(', ');
    return location ? `${company.name} (${location})` : company.name;
}

function resetCompanySelection(): void {
    selectedCompany.value = null;
    availableStores.value = [];
    form.company_id = '';
    form.store_id = '';
}

function reset(): void {
    form.reset();
    form.clearErrors();
    form.task_type = props.formOptions.taskTypes[0] ?? 'Call';
    form.priority = 'Medium';
    form.status = 'Open';
    companySearch.value = '';
    companyResults.value = [];
    dueDateValue.value = undefined;
    resetCompanySelection();
}

async function searchCompanies(term: string, requestId: number): Promise<void> {
    isSearchingCompanies.value = true;

    try {
        const response = await fetch(`/tasks/company-search?search=${encodeURIComponent(term)}`, {
            headers: { Accept: 'application/json' },
        });

        if (!response.ok || requestId !== searchRequestId) {
            return;
        }

        companyResults.value = (await response.json()) as CompanySearchResult[];
    } finally {
        if (requestId === searchRequestId) {
            isSearchingCompanies.value = false;
        }
    }
}

async function loadCompanyOptions(company: CompanySearchResult): Promise<void> {
    const response = await fetch(`/tasks/companies/${company.id}/options`, {
        headers: { Accept: 'application/json' },
    });

    if (!response.ok) {
        availableStores.value = [];
        form.store_id = '';
        return;
    }

    const payload = (await response.json()) as { id: number; name: string; stores: SelectOption[] };
    availableStores.value = payload.stores;

    if (form.store_id && !payload.stores.some((store) => String(store.id) === form.store_id)) {
        form.store_id = '';
    }
}

async function selectCompany(company: CompanySearchResult): Promise<void> {
    selectedCompany.value = company;
    companySearch.value = formatCompanyLabel(company);
    companyResults.value = [];
    form.company_id = String(company.id);
    form.store_id = '';
    form.clearErrors('company_id', 'store_id');
    await loadCompanyOptions(company);
}

function submit(): void {
    if (!form.company_id) {
        form.setError('company_id', 'Please select a company.');
        return;
    }

    form.transform((data) => ({
        ...data,
        description: data.description || null,
        due_date: data.due_date || null,
        company_id: data.company_id || null,
        assigned_to: data.assigned_to || null,
        store_id: data.store_id || null,
        contact_id: null,
    })).post('/tasks', {
        preserveScroll: true,
        onSuccess: () => {
            isOpen.value = false;
        },
    });
}

watch(dueDateValue, (val) => {
    form.due_date = val ? val.toString() : '';
});

watch(isOpen, (open) => {
    if (!open) {
        reset();
    }
});

watch(companySearch, (value) => {
    if (selectedCompany.value && value !== formatCompanyLabel(selectedCompany.value)) {
        resetCompanySelection();
    }

    if (searchTimeout) {
        window.clearTimeout(searchTimeout);
    }

    const term = value.trim();

    if (term.length < 2 || form.company_id === String(selectedCompany.value?.id)) {
        companyResults.value = [];
        isSearchingCompanies.value = false;
        return;
    }

    const requestId = ++searchRequestId;
    searchTimeout = window.setTimeout(() => void searchCompanies(term, requestId), 250);
});

onBeforeUnmount(() => {
    if (searchTimeout) {
        window.clearTimeout(searchTimeout);
    }
});
</script>

<template>
    <Dialog v-model:open="isOpen">
        <DialogTrigger as-child>
            <slot />
        </DialogTrigger>
        <DialogContent class="sm:max-w-2xl">
            <DialogHeader>
                <DialogTitle>Create Task</DialogTitle>
                <DialogDescription>
                    Add a task and link it to the right company before choosing
                    an optional store.
                </DialogDescription>
            </DialogHeader>

            <form
                class="grid gap-4 md:grid-cols-2"
                @submit.prevent="submit"
            >
                <div class="grid gap-2 md:col-span-2">
                    <label for="create_task_name" class="text-sm font-medium">
                        Name
                    </label>
                    <Input
                        id="create_task_name"
                        v-model="form.name"
                        required
                    />
                    <InputError :message="form.errors.name" />
                </div>

                <div class="grid gap-2 md:col-span-2">
                    <label
                        for="create_task_description"
                        class="text-sm font-medium"
                    >
                        Description
                    </label>
                    <Textarea
                        id="create_task_description"
                        v-model="form.description"
                    />
                    <InputError :message="form.errors.description" />
                </div>

                <div class="grid gap-2 md:col-span-2">
                    <label
                        for="create_task_company_search"
                        class="text-sm font-medium"
                    >
                        Company
                    </label>
                    <div class="relative">
                        <Input
                            id="create_task_company_search"
                            v-model="companySearch"
                            autocomplete="off"
                            placeholder="Search companies by name or city"
                        />
                        <div
                            v-if="
                                companySearch.trim().length >= 2 &&
                                !selectedCompany
                            "
                            class="absolute z-10 mt-2 max-h-64 w-full overflow-y-auto rounded-md border border-border bg-background shadow-lg"
                        >
                            <div
                                v-if="isSearchingCompanies"
                                class="px-3 py-2 text-sm text-muted-foreground"
                            >
                                Searching companies...
                            </div>
                            <div
                                v-else-if="companyResults.length === 0"
                                class="px-3 py-2 text-sm text-muted-foreground"
                            >
                                No matching companies found.
                            </div>
                            <button
                                v-for="company in companyResults"
                                :key="company.id"
                                class="flex w-full flex-col items-start gap-1 px-3 py-2 text-left text-sm hover:bg-muted"
                                type="button"
                                @click="selectCompany(company)"
                            >
                                <span class="font-medium">
                                    {{ company.name }}
                                </span>
                                <span class="text-muted-foreground">
                                    {{
                                        [company.city, company.state]
                                            .filter(Boolean)
                                            .join(', ') || 'No location'
                                    }}
                                </span>
                            </button>
                        </div>
                    </div>
                    <InputError :message="form.errors.company_id" />
                </div>

                <div class="grid gap-2">
                    <label
                        for="create_task_type"
                        class="text-sm font-medium"
                    >
                        Task Type
                    </label>
                    <Select v-model="form.task_type">
                        <SelectTrigger class="w-full" id="create_task_type">
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem
                                v-for="taskType in formOptions.taskTypes"
                                :key="taskType"
                                :value="taskType"
                            >
                                {{ taskType }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                    <InputError :message="form.errors.task_type" />
                </div>

                <div class="grid gap-2">
                    <label
                        for="create_priority"
                        class="text-sm font-medium"
                    >
                        Priority
                    </label>
                    <Select v-model="form.priority">
                        <SelectTrigger class="w-full" id="create_priority">
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem
                                v-for="priority in formOptions.priorities"
                                :key="priority"
                                :value="priority"
                            >
                                {{ priority }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                    <InputError :message="form.errors.priority" />
                </div>

                <div class="grid gap-2">
                    <label for="create_status" class="text-sm font-medium">
                        Status
                    </label>
                    <Select v-model="form.status">
                        <SelectTrigger class="w-full" id="create_status">
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem
                                v-for="status in formOptions.statuses"
                                :key="status"
                                :value="status"
                            >
                                {{ status }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                    <InputError :message="form.errors.status" />
                </div>

                <div class="grid gap-2">
                    <label
                        for="create_due_date"
                        class="text-sm font-medium"
                    >
                        Due Date
                    </label>
                    <Popover>
                        <PopoverTrigger as-child>
                            <Button
                                id="create_due_date"
                                variant="outline"
                                type="button"
                                class="w-full justify-between text-left font-normal"
                            >
                                <span :class="!dueDateValue ? 'text-muted-foreground' : ''">
                                    {{ dueDateLabel }}
                                </span>
                                <CalendarIcon class="h-4 w-4 text-muted-foreground" />
                            </Button>
                        </PopoverTrigger>
                        <PopoverContent class="w-auto p-0" align="start">
                            <Calendar v-model="dueDateValue" layout="month-and-year" />
                        </PopoverContent>
                    </Popover>
                    <InputError :message="form.errors.due_date" />
                </div>

                <div class="grid gap-2">
                    <label
                        for="create_assigned_to"
                        class="text-sm font-medium"
                    >
                        Assigned To
                    </label>
                    <Select
                        :model-value="form.assigned_to || '_unassigned_'"
                        @update:model-value="form.assigned_to = $event === '_unassigned_' ? '' : $event"
                    >
                        <SelectTrigger class="w-full" id="create_assigned_to">
                            <SelectValue placeholder="Unassigned" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="_unassigned_">Unassigned</SelectItem>
                            <SelectItem
                                v-for="user in formOptions.users"
                                :key="user.id"
                                :value="String(user.id)"
                            >
                                {{ user.name }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                    <InputError :message="form.errors.assigned_to" />
                </div>

                <div class="grid gap-2">
                    <label
                        for="create_store_id"
                        class="text-sm font-medium"
                    >
                        Store
                    </label>
                    <Select
                        :model-value="form.store_id || '_none_'"
                        :disabled="!form.company_id"
                        @update:model-value="form.store_id = $event === '_none_' ? '' : $event"
                    >
                        <SelectTrigger class="w-full" id="create_store_id">
                            <SelectValue
                                :placeholder="
                                    form.company_id
                                        ? 'No Store'
                                        : 'Select a company first'
                                "
                            />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="_none_">No Store</SelectItem>
                            <SelectItem
                                v-for="store in availableStores"
                                :key="store.id"
                                :value="String(store.id)"
                            >
                                {{ store.name }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                    <InputError :message="form.errors.store_id" />
                </div>

                <DialogFooter class="md:col-span-2">
                    <Button
                        type="button"
                        variant="outline"
                        @click="isOpen = false"
                    >
                        Cancel
                    </Button>
                    <Button :disabled="form.processing" type="submit">
                        Create Task
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
