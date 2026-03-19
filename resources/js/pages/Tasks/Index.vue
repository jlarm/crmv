<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, router, useForm } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, ref, watch } from 'vue';

interface SelectOption {
    id: number;
    name: string;
}

interface CompanySearchResult {
    id: number;
    name: string;
    city: string | null;
    state: string | null;
}

interface TaskItem {
    id: number;
    name: string;
    description: string | null;
    task_type: string;
    priority: string;
    status: string;
    due_date: string | null;
    company_id: number | null;
    assigned_to: number | null;
    store_id: number | null;
    contact_id: number | null;
    company: { id: number; name: string } | null;
    assignedTo: { id: number; name: string } | null;
    store: { id: number; name: string } | null;
    contact: { id: number; name: string } | null;
}

interface TaskFormOptions {
    taskTypes: string[];
    priorities: string[];
    statuses: string[];
    users: SelectOption[];
}

const props = defineProps<{
    tasksByPriority: Record<'High' | 'Medium' | 'Low', TaskItem[]>;
    completedTasks: TaskItem[];
    prioritySummary: Array<{
        label: 'High' | 'Medium' | 'Low';
        count: number;
    }>;
    createFormOptions: TaskFormOptions;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Tasks',
        href: '/tasks',
    },
];

const isCreateOpen = ref(false);
const isEditOpen = ref(false);
const editingTask = ref<TaskItem | null>(null);

const createCompanySearch = ref('');
const createCompanyResults = ref<CompanySearchResult[]>([]);
const createSelectedCompany = ref<CompanySearchResult | null>(null);
const createAvailableStores = ref<SelectOption[]>([]);
const isSearchingCreateCompanies = ref(false);

const editCompanySearch = ref('');
const editCompanyResults = ref<CompanySearchResult[]>([]);
const editSelectedCompany = ref<CompanySearchResult | null>(null);
const editAvailableStores = ref<SelectOption[]>([]);
const isSearchingEditCompanies = ref(false);

const createForm = useForm({
    name: '',
    description: '',
    task_type: props.createFormOptions.taskTypes[0] ?? 'Call',
    priority: 'Medium',
    status: 'Open',
    due_date: '',
    company_id: '',
    assigned_to: '',
    store_id: '',
});

const editForm = useForm({
    name: '',
    description: '',
    task_type: props.createFormOptions.taskTypes[0] ?? 'Call',
    priority: 'Medium',
    status: 'Open',
    due_date: '',
    company_id: '',
    assigned_to: '',
    store_id: '',
});

const hasNoTasks = computed(() => {
    return (
        props.prioritySummary.every((summary) => summary.count === 0) &&
        props.completedTasks.length === 0
    );
});

let createCompanySearchTimeout: number | null = null;
let createCompanySearchRequestId = 0;
let editCompanySearchTimeout: number | null = null;
let editCompanySearchRequestId = 0;

function formatDueDate(value: string | null): string {
    if (!value) {
        return 'No due date';
    }

    const date = new Date(value);

    if (Number.isNaN(date.getTime())) {
        return value;
    }

    return new Intl.DateTimeFormat('en-US', {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
    }).format(date);
}

function formatCompanyLabel(company: CompanySearchResult): string {
    const location = [company.city, company.state].filter(Boolean).join(', ');

    return location ? `${company.name} (${location})` : company.name;
}

function normalizeCompanySearchResult(
    task: TaskItem,
): CompanySearchResult | null {
    if (!task.company) {
        return null;
    }

    return {
        id: task.company.id,
        name: task.company.name,
        city: null,
        state: null,
    };
}

function resetCreateCompanySelection(): void {
    createSelectedCompany.value = null;
    createAvailableStores.value = [];
    createForm.company_id = '';
    createForm.store_id = '';
}

function resetEditCompanySelection(): void {
    editSelectedCompany.value = null;
    editAvailableStores.value = [];
    editForm.company_id = '';
    editForm.store_id = '';
}

function resetCreateForm(): void {
    createForm.reset();
    createForm.clearErrors();
    createForm.task_type = props.createFormOptions.taskTypes[0] ?? 'Call';
    createForm.priority = 'Medium';
    createForm.status = 'Open';
    createCompanySearch.value = '';
    createCompanyResults.value = [];
    resetCreateCompanySelection();
}

function resetEditForm(): void {
    editForm.reset();
    editForm.clearErrors();
    editForm.task_type = props.createFormOptions.taskTypes[0] ?? 'Call';
    editForm.priority = 'Medium';
    editForm.status = 'Open';
    editCompanySearch.value = '';
    editCompanyResults.value = [];
    editSelectedCompany.value = null;
    editAvailableStores.value = [];
}

async function searchCompanies(
    term: string,
    requestId: number,
    results: typeof createCompanyResults,
    loading: typeof isSearchingCreateCompanies,
    currentRequestId: () => number,
): Promise<void> {
    loading.value = true;

    try {
        const response = await fetch(
            `/tasks/company-search?search=${encodeURIComponent(term)}`,
            {
                headers: {
                    Accept: 'application/json',
                },
            },
        );

        if (!response.ok || requestId !== currentRequestId()) {
            return;
        }

        results.value = (await response.json()) as CompanySearchResult[];
    } finally {
        if (requestId === currentRequestId()) {
            loading.value = false;
        }
    }
}

async function loadCompanyOptions(
    company: CompanySearchResult,
    stores: typeof createAvailableStores,
    form: typeof createForm,
): Promise<void> {
    const response = await fetch(`/tasks/companies/${company.id}/options`, {
        headers: {
            Accept: 'application/json',
        },
    });

    if (!response.ok) {
        stores.value = [];
        form.store_id = '';

        return;
    }

    const payload = (await response.json()) as {
        id: number;
        name: string;
        stores: SelectOption[];
    };

    stores.value = payload.stores;

    if (
        form.store_id &&
        !payload.stores.some((store) => String(store.id) === form.store_id)
    ) {
        form.store_id = '';
    }
}

async function selectCreateCompany(
    company: CompanySearchResult,
): Promise<void> {
    createSelectedCompany.value = company;
    createCompanySearch.value = formatCompanyLabel(company);
    createCompanyResults.value = [];
    createForm.company_id = String(company.id);
    createForm.store_id = '';
    createForm.clearErrors('company_id', 'store_id');

    await loadCompanyOptions(company, createAvailableStores, createForm);
}

async function selectEditCompany(company: CompanySearchResult): Promise<void> {
    editSelectedCompany.value = company;
    editCompanySearch.value = formatCompanyLabel(company);
    editCompanyResults.value = [];
    editForm.company_id = String(company.id);
    editForm.store_id = '';
    editForm.clearErrors('company_id', 'store_id');

    await loadCompanyOptions(company, editAvailableStores, editForm);
}

function handleCreateDialogChange(open: boolean): void {
    isCreateOpen.value = open;

    if (!open && !createForm.processing) {
        resetCreateForm();
    }
}

function handleEditDialogChange(open: boolean): void {
    isEditOpen.value = open;

    if (!open && !editForm.processing) {
        editingTask.value = null;
        resetEditForm();
    }
}

function submitCreateTask(): void {
    if (!createForm.company_id) {
        createForm.setError('company_id', 'Please select a company.');

        return;
    }

    createForm
        .transform((data) => ({
            ...data,
            description: data.description || null,
            due_date: data.due_date || null,
            company_id: data.company_id || null,
            assigned_to: data.assigned_to || null,
            store_id: data.store_id || null,
            contact_id: null,
        }))
        .post('/tasks', {
            preserveScroll: true,
            onSuccess: () => {
                handleCreateDialogChange(false);
            },
            onError: () => {
                isCreateOpen.value = true;
            },
        });
}

function openEditDialog(task: TaskItem): void {
    editingTask.value = task;
    editForm.clearErrors();
    editForm.name = task.name;
    editForm.description = task.description ?? '';
    editForm.task_type = task.task_type;
    editForm.priority = task.priority;
    editForm.status = task.status;
    editForm.due_date = task.due_date ?? '';
    editForm.company_id = task.company_id ? String(task.company_id) : '';
    editForm.assigned_to = task.assigned_to ? String(task.assigned_to) : '';
    editForm.store_id = task.store_id ? String(task.store_id) : '';

    const company = normalizeCompanySearchResult(task);
    editSelectedCompany.value = company;
    editCompanySearch.value = company ? company.name : '';
    editCompanyResults.value = [];
    editAvailableStores.value = [];
    isEditOpen.value = true;

    if (company) {
        void loadCompanyOptions(company, editAvailableStores, editForm);
    }
}

function submitEditTask(): void {
    if (!editingTask.value) {
        return;
    }

    if (!editForm.company_id) {
        editForm.setError('company_id', 'Please select a company.');

        return;
    }

    editForm
        .transform((data) => ({
            ...data,
            description: data.description || null,
            due_date: data.due_date || null,
            company_id: data.company_id || null,
            assigned_to: data.assigned_to || null,
            store_id: data.store_id || null,
            contact_id: editingTask.value?.contact_id ?? null,
        }))
        .put(`/tasks/${editingTask.value.id}`, {
            preserveScroll: true,
            onSuccess: () => {
                handleEditDialogChange(false);
            },
            onError: () => {
                isEditOpen.value = true;
            },
        });
}

function deleteTask(taskId: number): void {
    router.delete(`/tasks/${taskId}`, {
        preserveScroll: true,
        onSuccess: () => {
            if (editingTask.value?.id === taskId) {
                handleEditDialogChange(false);
            }
        },
    });
}

function toggleTaskComplete(
    task: TaskItem,
    checked: boolean | 'indeterminate',
): void {
    if (checked === 'indeterminate') {
        return;
    }

    router.put(
        `/tasks/${task.id}`,
        {
            name: task.name,
            description: task.description,
            task_type: task.task_type,
            priority: task.priority,
            status: checked ? 'Completed' : 'Open',
            due_date: task.due_date,
            company_id: task.company_id,
            assigned_to: task.assigned_to,
            store_id: task.store_id,
            contact_id: task.contact_id,
        },
        {
            preserveScroll: true,
            preserveState: true,
        },
    );
}

watch(createCompanySearch, (value) => {
    if (
        createSelectedCompany.value &&
        value !== formatCompanyLabel(createSelectedCompany.value)
    ) {
        resetCreateCompanySelection();
    }

    if (createCompanySearchTimeout) {
        window.clearTimeout(createCompanySearchTimeout);
    }

    const term = value.trim();

    if (
        term.length < 2 ||
        createForm.company_id === String(createSelectedCompany.value?.id)
    ) {
        createCompanyResults.value = [];
        isSearchingCreateCompanies.value = false;

        return;
    }

    const requestId = ++createCompanySearchRequestId;

    createCompanySearchTimeout = window.setTimeout(() => {
        void searchCompanies(
            term,
            requestId,
            createCompanyResults,
            isSearchingCreateCompanies,
            () => createCompanySearchRequestId,
        );
    }, 250);
});

watch(editCompanySearch, (value) => {
    if (
        editSelectedCompany.value &&
        value !== formatCompanyLabel(editSelectedCompany.value)
    ) {
        resetEditCompanySelection();
    }

    if (editCompanySearchTimeout) {
        window.clearTimeout(editCompanySearchTimeout);
    }

    const term = value.trim();

    if (
        term.length < 2 ||
        editForm.company_id === String(editSelectedCompany.value?.id)
    ) {
        editCompanyResults.value = [];
        isSearchingEditCompanies.value = false;

        return;
    }

    const requestId = ++editCompanySearchRequestId;

    editCompanySearchTimeout = window.setTimeout(() => {
        void searchCompanies(
            term,
            requestId,
            editCompanyResults,
            isSearchingEditCompanies,
            () => editCompanySearchRequestId,
        );
    }, 250);
});

onBeforeUnmount(() => {
    if (createCompanySearchTimeout) {
        window.clearTimeout(createCompanySearchTimeout);
    }

    if (editCompanySearchTimeout) {
        window.clearTimeout(editCompanySearchTimeout);
    }
});
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Tasks" />

        <div class="space-y-6 p-6">
            <div class="flex items-center justify-between gap-3">
                <div>
                    <h1 class="text-2xl font-semibold tracking-tight">Tasks</h1>
                    <p class="text-sm text-muted-foreground">
                        Review your assigned tasks, grouped by priority.
                    </p>
                </div>

                <Dialog
                    :open="isCreateOpen"
                    @update:open="handleCreateDialogChange"
                >
                    <Button @click="isCreateOpen = true">New Task</Button>

                    <DialogContent class="sm:max-w-2xl">
                        <DialogHeader>
                            <DialogTitle>Create Task</DialogTitle>
                            <DialogDescription>
                                Add a task and link it to the right company
                                before choosing an optional store.
                            </DialogDescription>
                        </DialogHeader>

                        <form
                            class="grid gap-4 md:grid-cols-2"
                            @submit.prevent="submitCreateTask"
                        >
                            <div class="grid gap-2 md:col-span-2">
                                <label
                                    for="task_name"
                                    class="text-sm font-medium"
                                >
                                    Name
                                </label>
                                <Input
                                    id="task_name"
                                    v-model="createForm.name"
                                    required
                                />
                                <InputError :message="createForm.errors.name" />
                            </div>

                            <div class="grid gap-2 md:col-span-2">
                                <label
                                    for="task_description"
                                    class="text-sm font-medium"
                                >
                                    Description
                                </label>
                                <Textarea
                                    id="task_description"
                                    v-model="createForm.description"
                                />
                                <InputError
                                    :message="createForm.errors.description"
                                />
                            </div>

                            <div class="grid gap-2 md:col-span-2">
                                <label
                                    for="task_company_search"
                                    class="text-sm font-medium"
                                >
                                    Company
                                </label>
                                <div class="relative">
                                    <Input
                                        id="task_company_search"
                                        v-model="createCompanySearch"
                                        autocomplete="off"
                                        placeholder="Search companies by name or city"
                                    />

                                    <div
                                        v-if="
                                            createCompanySearch.trim().length >=
                                                2 && !createSelectedCompany
                                        "
                                        class="absolute z-10 mt-2 max-h-64 w-full overflow-y-auto rounded-md border border-border bg-background shadow-lg"
                                    >
                                        <div
                                            v-if="isSearchingCreateCompanies"
                                            class="px-3 py-2 text-sm text-muted-foreground"
                                        >
                                            Searching companies...
                                        </div>

                                        <div
                                            v-else-if="
                                                createCompanyResults.length ===
                                                0
                                            "
                                            class="px-3 py-2 text-sm text-muted-foreground"
                                        >
                                            No matching companies found.
                                        </div>

                                        <button
                                            v-for="company in createCompanyResults"
                                            :key="company.id"
                                            class="flex w-full flex-col items-start gap-1 px-3 py-2 text-left text-sm hover:bg-muted"
                                            type="button"
                                            @click="
                                                selectCreateCompany(company)
                                            "
                                        >
                                            <span class="font-medium">
                                                {{ company.name }}
                                            </span>
                                            <span class="text-muted-foreground">
                                                {{
                                                    [
                                                        company.city,
                                                        company.state,
                                                    ]
                                                        .filter(Boolean)
                                                        .join(', ') ||
                                                    'No location'
                                                }}
                                            </span>
                                        </button>
                                    </div>
                                </div>
                                <InputError
                                    :message="createForm.errors.company_id"
                                />
                            </div>

                            <div class="grid gap-2">
                                <label
                                    for="task_type"
                                    class="text-sm font-medium"
                                >
                                    Task Type
                                </label>
                                <select
                                    id="task_type"
                                    v-model="createForm.task_type"
                                    class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs"
                                >
                                    <option
                                        v-for="taskType in props
                                            .createFormOptions.taskTypes"
                                        :key="taskType"
                                        :value="taskType"
                                    >
                                        {{ taskType }}
                                    </option>
                                </select>
                                <InputError
                                    :message="createForm.errors.task_type"
                                />
                            </div>

                            <div class="grid gap-2">
                                <label
                                    for="priority"
                                    class="text-sm font-medium"
                                >
                                    Priority
                                </label>
                                <select
                                    id="priority"
                                    v-model="createForm.priority"
                                    class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs"
                                >
                                    <option
                                        v-for="priority in props
                                            .createFormOptions.priorities"
                                        :key="priority"
                                        :value="priority"
                                    >
                                        {{ priority }}
                                    </option>
                                </select>
                                <InputError
                                    :message="createForm.errors.priority"
                                />
                            </div>

                            <div class="grid gap-2">
                                <label for="status" class="text-sm font-medium">
                                    Status
                                </label>
                                <select
                                    id="status"
                                    v-model="createForm.status"
                                    class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs"
                                >
                                    <option
                                        v-for="status in props.createFormOptions
                                            .statuses"
                                        :key="status"
                                        :value="status"
                                    >
                                        {{ status }}
                                    </option>
                                </select>
                                <InputError
                                    :message="createForm.errors.status"
                                />
                            </div>

                            <div class="grid gap-2">
                                <label
                                    for="due_date"
                                    class="text-sm font-medium"
                                >
                                    Due Date
                                </label>
                                <Input
                                    id="due_date"
                                    v-model="createForm.due_date"
                                    type="date"
                                />
                                <InputError
                                    :message="createForm.errors.due_date"
                                />
                            </div>

                            <div class="grid gap-2">
                                <label
                                    for="assigned_to"
                                    class="text-sm font-medium"
                                >
                                    Assigned To
                                </label>
                                <select
                                    id="assigned_to"
                                    v-model="createForm.assigned_to"
                                    class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs"
                                >
                                    <option value="">Unassigned</option>
                                    <option
                                        v-for="user in props.createFormOptions
                                            .users"
                                        :key="user.id"
                                        :value="String(user.id)"
                                    >
                                        {{ user.name }}
                                    </option>
                                </select>
                                <InputError
                                    :message="createForm.errors.assigned_to"
                                />
                            </div>

                            <div class="grid gap-2">
                                <label
                                    for="store_id"
                                    class="text-sm font-medium"
                                >
                                    Store
                                </label>
                                <select
                                    id="store_id"
                                    v-model="createForm.store_id"
                                    :disabled="!createForm.company_id"
                                    class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs disabled:cursor-not-allowed disabled:opacity-60"
                                >
                                    <option value="">
                                        {{
                                            createForm.company_id
                                                ? 'No Store'
                                                : 'Select a company first'
                                        }}
                                    </option>
                                    <option
                                        v-for="store in createAvailableStores"
                                        :key="store.id"
                                        :value="String(store.id)"
                                    >
                                        {{ store.name }}
                                    </option>
                                </select>
                                <InputError
                                    :message="createForm.errors.store_id"
                                />
                            </div>

                            <DialogFooter class="md:col-span-2">
                                <Button
                                    type="button"
                                    variant="outline"
                                    @click="handleCreateDialogChange(false)"
                                >
                                    Cancel
                                </Button>
                                <Button
                                    :disabled="createForm.processing"
                                    type="submit"
                                >
                                    Create Task
                                </Button>
                            </DialogFooter>
                        </form>
                    </DialogContent>
                </Dialog>
            </div>

            <Dialog :open="isEditOpen" @update:open="handleEditDialogChange">
                <DialogContent class="sm:max-w-2xl">
                    <DialogHeader>
                        <DialogTitle>Edit Task</DialogTitle>
                        <DialogDescription>
                            Update the task details or remove the task entirely.
                        </DialogDescription>
                    </DialogHeader>

                    <form
                        class="grid gap-4 md:grid-cols-2"
                        @submit.prevent="submitEditTask"
                    >
                        <div class="grid gap-2 md:col-span-2">
                            <label
                                for="edit_task_name"
                                class="text-sm font-medium"
                            >
                                Name
                            </label>
                            <Input
                                id="edit_task_name"
                                v-model="editForm.name"
                                required
                            />
                            <InputError :message="editForm.errors.name" />
                        </div>

                        <div class="grid gap-2 md:col-span-2">
                            <label
                                for="edit_task_description"
                                class="text-sm font-medium"
                            >
                                Description
                            </label>
                            <Textarea
                                id="edit_task_description"
                                v-model="editForm.description"
                            />
                            <InputError
                                :message="editForm.errors.description"
                            />
                        </div>

                        <div class="grid gap-2 md:col-span-2">
                            <label
                                for="edit_task_company_search"
                                class="text-sm font-medium"
                            >
                                Company
                            </label>
                            <div class="relative">
                                <Input
                                    id="edit_task_company_search"
                                    v-model="editCompanySearch"
                                    autocomplete="off"
                                    placeholder="Search companies by name or city"
                                />

                                <div
                                    v-if="
                                        editCompanySearch.trim().length >= 2 &&
                                        !editSelectedCompany
                                    "
                                    class="absolute z-10 mt-2 max-h-64 w-full overflow-y-auto rounded-md border border-border bg-background shadow-lg"
                                >
                                    <div
                                        v-if="isSearchingEditCompanies"
                                        class="px-3 py-2 text-sm text-muted-foreground"
                                    >
                                        Searching companies...
                                    </div>

                                    <div
                                        v-else-if="
                                            editCompanyResults.length === 0
                                        "
                                        class="px-3 py-2 text-sm text-muted-foreground"
                                    >
                                        No matching companies found.
                                    </div>

                                    <button
                                        v-for="company in editCompanyResults"
                                        :key="company.id"
                                        class="flex w-full flex-col items-start gap-1 px-3 py-2 text-left text-sm hover:bg-muted"
                                        type="button"
                                        @click="selectEditCompany(company)"
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
                            <InputError :message="editForm.errors.company_id" />
                        </div>

                        <div class="grid gap-2">
                            <label
                                for="edit_task_type"
                                class="text-sm font-medium"
                            >
                                Task Type
                            </label>
                            <select
                                id="edit_task_type"
                                v-model="editForm.task_type"
                                class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs"
                            >
                                <option
                                    v-for="taskType in props.createFormOptions
                                        .taskTypes"
                                    :key="taskType"
                                    :value="taskType"
                                >
                                    {{ taskType }}
                                </option>
                            </select>
                            <InputError :message="editForm.errors.task_type" />
                        </div>

                        <div class="grid gap-2">
                            <label
                                for="edit_priority"
                                class="text-sm font-medium"
                            >
                                Priority
                            </label>
                            <select
                                id="edit_priority"
                                v-model="editForm.priority"
                                class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs"
                            >
                                <option
                                    v-for="priority in props.createFormOptions
                                        .priorities"
                                    :key="priority"
                                    :value="priority"
                                >
                                    {{ priority }}
                                </option>
                            </select>
                            <InputError :message="editForm.errors.priority" />
                        </div>

                        <div class="grid gap-2">
                            <label
                                for="edit_status"
                                class="text-sm font-medium"
                            >
                                Status
                            </label>
                            <select
                                id="edit_status"
                                v-model="editForm.status"
                                class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs"
                            >
                                <option
                                    v-for="status in props.createFormOptions
                                        .statuses"
                                    :key="status"
                                    :value="status"
                                >
                                    {{ status }}
                                </option>
                            </select>
                            <InputError :message="editForm.errors.status" />
                        </div>

                        <div class="grid gap-2">
                            <label
                                for="edit_due_date"
                                class="text-sm font-medium"
                            >
                                Due Date
                            </label>
                            <Input
                                id="edit_due_date"
                                v-model="editForm.due_date"
                                type="date"
                            />
                            <InputError :message="editForm.errors.due_date" />
                        </div>

                        <div class="grid gap-2">
                            <label
                                for="edit_assigned_to"
                                class="text-sm font-medium"
                            >
                                Assigned To
                            </label>
                            <select
                                id="edit_assigned_to"
                                v-model="editForm.assigned_to"
                                class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs"
                            >
                                <option value="">Unassigned</option>
                                <option
                                    v-for="user in props.createFormOptions
                                        .users"
                                    :key="user.id"
                                    :value="String(user.id)"
                                >
                                    {{ user.name }}
                                </option>
                            </select>
                            <InputError
                                :message="editForm.errors.assigned_to"
                            />
                        </div>

                        <div class="grid gap-2">
                            <label
                                for="edit_store_id"
                                class="text-sm font-medium"
                            >
                                Store
                            </label>
                            <select
                                id="edit_store_id"
                                v-model="editForm.store_id"
                                :disabled="!editForm.company_id"
                                class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs disabled:cursor-not-allowed disabled:opacity-60"
                            >
                                <option value="">
                                    {{
                                        editForm.company_id
                                            ? 'No Store'
                                            : 'Select a company first'
                                    }}
                                </option>
                                <option
                                    v-for="store in editAvailableStores"
                                    :key="store.id"
                                    :value="String(store.id)"
                                >
                                    {{ store.name }}
                                </option>
                            </select>
                            <InputError :message="editForm.errors.store_id" />
                        </div>

                        <DialogFooter class="md:col-span-2">
                            <Button
                                v-if="editingTask"
                                type="button"
                                variant="destructive"
                                @click="deleteTask(editingTask.id)"
                            >
                                Delete Task
                            </Button>
                            <div class="flex gap-2">
                                <Button
                                    type="button"
                                    variant="outline"
                                    @click="handleEditDialogChange(false)"
                                >
                                    Cancel
                                </Button>
                                <Button
                                    :disabled="editForm.processing"
                                    type="submit"
                                >
                                    Save Changes
                                </Button>
                            </div>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            <div class="grid gap-4 md:grid-cols-3">
                <Card
                    v-for="summary in props.prioritySummary"
                    :key="summary.label"
                    class="border-border/70"
                >
                    <CardHeader class="space-y-2">
                        <CardTitle class="text-base">
                            {{ summary.label }} Priority
                        </CardTitle>
                        <p class="text-3xl font-semibold tracking-tight">
                            {{ summary.count }}
                        </p>
                    </CardHeader>
                </Card>
            </div>

            <div class="space-y-6">
                <Card
                    v-for="priority in ['High', 'Medium', 'Low']"
                    :key="priority"
                >
                    <CardHeader
                        class="flex flex-row items-center justify-between"
                    >
                        <CardTitle>{{ priority }} Priority</CardTitle>
                        <Badge variant="secondary">
                            {{ props.tasksByPriority[priority].length }}
                        </Badge>
                    </CardHeader>
                    <CardContent class="p-0">
                        <div
                            v-if="props.tasksByPriority[priority].length === 0"
                            class="px-6 py-10 text-center text-sm text-muted-foreground"
                        >
                            No {{ priority.toLowerCase() }} priority tasks
                            assigned to you.
                        </div>

                        <div v-else class="overflow-x-auto">
                            <table class="min-w-full text-sm">
                                <thead
                                    class="bg-muted/40 text-left text-muted-foreground"
                                >
                                    <tr>
                                        <th class="px-6 py-3 font-medium">
                                            Task
                                        </th>
                                        <th class="px-6 py-3 font-medium">
                                            Type
                                        </th>
                                        <th class="px-6 py-3 font-medium">
                                            Status
                                        </th>
                                        <th class="px-6 py-3 font-medium">
                                            Store
                                        </th>
                                        <th class="px-6 py-3 font-medium">
                                            Contact
                                        </th>
                                        <th class="px-6 py-3 font-medium">
                                            Due Date
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr
                                        v-for="task in props.tasksByPriority[
                                            priority
                                        ]"
                                        :key="task.id"
                                        class="cursor-pointer border-t border-border/60 transition hover:bg-muted/30"
                                        @click="openEditDialog(task)"
                                    >
                                        <td class="px-6 py-4 align-top">
                                            <div class="flex items-start gap-3">
                                                <div @click.stop>
                                                    <Checkbox
                                                        :model-value="
                                                            task.status ===
                                                            'Completed'
                                                        "
                                                        @update:model-value="
                                                            toggleTaskComplete(
                                                                task,
                                                                $event,
                                                            )
                                                        "
                                                    />
                                                </div>
                                                <div class="space-y-1">
                                                    <div class="font-medium">
                                                        {{ task.name }}
                                                    </div>
                                                    <p
                                                        class="text-muted-foreground"
                                                    >
                                                        {{
                                                            task.company
                                                                ?.name ||
                                                            'No company selected'
                                                        }}
                                                    </p>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 align-top">
                                            {{ task.task_type }}
                                        </td>
                                        <td class="px-6 py-4 align-top">
                                            {{ task.status }}
                                        </td>
                                        <td class="px-6 py-4 align-top">
                                            {{ task.store?.name || 'None' }}
                                        </td>
                                        <td class="px-6 py-4 align-top">
                                            {{ task.contact?.name || 'None' }}
                                        </td>
                                        <td
                                            class="px-6 py-4 align-top text-muted-foreground"
                                        >
                                            {{ formatDueDate(task.due_date) }}
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </CardContent>
                </Card>

                <Card v-if="hasNoTasks">
                    <CardContent
                        class="py-12 text-center text-sm text-muted-foreground"
                    >
                        You do not have any assigned tasks yet.
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader
                        class="flex flex-row items-center justify-between"
                    >
                        <CardTitle>Completed</CardTitle>
                        <Badge variant="secondary">
                            {{ props.completedTasks.length }}
                        </Badge>
                    </CardHeader>
                    <CardContent class="p-0">
                        <div
                            v-if="props.completedTasks.length === 0"
                            class="px-6 py-10 text-center text-sm text-muted-foreground"
                        >
                            No completed tasks yet.
                        </div>

                        <div v-else class="overflow-x-auto">
                            <table class="min-w-full text-sm">
                                <thead
                                    class="bg-muted/40 text-left text-muted-foreground"
                                >
                                    <tr>
                                        <th class="px-6 py-3 font-medium">
                                            Task
                                        </th>
                                        <th class="px-6 py-3 font-medium">
                                            Type
                                        </th>
                                        <th class="px-6 py-3 font-medium">
                                            Status
                                        </th>
                                        <th class="px-6 py-3 font-medium">
                                            Store
                                        </th>
                                        <th class="px-6 py-3 font-medium">
                                            Contact
                                        </th>
                                        <th class="px-6 py-3 font-medium">
                                            Due Date
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr
                                        v-for="task in props.completedTasks"
                                        :key="task.id"
                                        class="cursor-pointer border-t border-border/60 transition hover:bg-muted/30"
                                        @click="openEditDialog(task)"
                                    >
                                        <td class="px-6 py-4 align-top">
                                            <div class="flex items-start gap-3">
                                                <div @click.stop>
                                                    <Checkbox
                                                        :model-value="true"
                                                        @update:model-value="
                                                            toggleTaskComplete(
                                                                task,
                                                                $event,
                                                            )
                                                        "
                                                    />
                                                </div>
                                                <div class="space-y-1">
                                                    <div
                                                        class="font-medium text-muted-foreground line-through"
                                                    >
                                                        {{ task.name }}
                                                    </div>
                                                    <p
                                                        class="text-muted-foreground"
                                                    >
                                                        {{
                                                            task.company
                                                                ?.name ||
                                                            'No company selected'
                                                        }}
                                                    </p>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 align-top">
                                            {{ task.task_type }}
                                        </td>
                                        <td class="px-6 py-4 align-top">
                                            {{ task.status }}
                                        </td>
                                        <td class="px-6 py-4 align-top">
                                            {{ task.store?.name || 'None' }}
                                        </td>
                                        <td class="px-6 py-4 align-top">
                                            {{ task.contact?.name || 'None' }}
                                        </td>
                                        <td
                                            class="px-6 py-4 align-top text-muted-foreground"
                                        >
                                            {{ formatDueDate(task.due_date) }}
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
