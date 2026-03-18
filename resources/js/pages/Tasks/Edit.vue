<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, useForm } from '@inertiajs/vue3';

interface SelectOption {
    id: number;
    name: string;
}

interface TaskItem {
    id: number;
    name: string;
    description: string | null;
    task_type: string;
    priority: string;
    status: string;
    due_date: string | null;
    assigned_to: number | null;
    store_id: number | null;
    contact_id: number | null;
}

interface FormOptions {
    taskTypes: string[];
    priorities: string[];
    statuses: string[];
    users: SelectOption[];
    stores: SelectOption[];
    contacts: SelectOption[];
}

const props = defineProps<{
    task: TaskItem;
    formOptions: FormOptions;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Tasks',
        href: '/tasks',
    },
    {
        title: 'Edit',
        href: `/tasks/${props.task.id}/edit`,
    },
];

const form = useForm({
    name: props.task.name,
    description: props.task.description ?? '',
    task_type: props.task.task_type,
    priority: props.task.priority,
    status: props.task.status,
    due_date: props.task.due_date ?? '',
    assigned_to: props.task.assigned_to ? String(props.task.assigned_to) : '',
    store_id: props.task.store_id ? String(props.task.store_id) : '',
    contact_id: props.task.contact_id ? String(props.task.contact_id) : '',
});

function submit(): void {
    form.transform((data) => ({
        ...data,
        description: data.description || null,
        due_date: data.due_date || null,
        assigned_to: data.assigned_to || null,
        store_id: data.store_id || null,
        contact_id: data.contact_id || null,
    })).put(`/tasks/${props.task.id}`);
}
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Edit Task" />

        <div class="space-y-6 p-6">
            <div class="flex items-center justify-between gap-3">
                <div>
                    <h1 class="text-2xl font-semibold tracking-tight">
                        Edit Task
                    </h1>
                    <p class="text-sm text-muted-foreground">
                        Update the task details and relationships.
                    </p>
                </div>

                <Button variant="outline" as-child>
                    <Link href="/tasks">Back to Tasks</Link>
                </Button>
            </div>

            <Card>
                <CardHeader>
                    <CardTitle>Task Details</CardTitle>
                </CardHeader>
                <CardContent>
                    <form
                        class="grid gap-6 md:grid-cols-2"
                        @submit.prevent="submit"
                    >
                        <div class="grid gap-2 md:col-span-2">
                            <label for="name" class="text-sm font-medium">
                                Name
                            </label>
                            <Input id="name" v-model="form.name" required />
                            <InputError :message="form.errors.name" />
                        </div>

                        <div class="grid gap-2 md:col-span-2">
                            <label
                                for="description"
                                class="text-sm font-medium"
                            >
                                Description
                            </label>
                            <Textarea
                                id="description"
                                v-model="form.description"
                            />
                            <InputError :message="form.errors.description" />
                        </div>

                        <div class="grid gap-2">
                            <label for="task_type" class="text-sm font-medium">
                                Task Type
                            </label>
                            <select
                                id="task_type"
                                v-model="form.task_type"
                                class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs"
                            >
                                <option
                                    v-for="taskType in props.formOptions
                                        .taskTypes"
                                    :key="taskType"
                                    :value="taskType"
                                >
                                    {{ taskType }}
                                </option>
                            </select>
                            <InputError :message="form.errors.task_type" />
                        </div>

                        <div class="grid gap-2">
                            <label for="priority" class="text-sm font-medium">
                                Priority
                            </label>
                            <select
                                id="priority"
                                v-model="form.priority"
                                class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs"
                            >
                                <option
                                    v-for="priority in props.formOptions
                                        .priorities"
                                    :key="priority"
                                    :value="priority"
                                >
                                    {{ priority }}
                                </option>
                            </select>
                            <InputError :message="form.errors.priority" />
                        </div>

                        <div class="grid gap-2">
                            <label for="status" class="text-sm font-medium">
                                Status
                            </label>
                            <select
                                id="status"
                                v-model="form.status"
                                class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs"
                            >
                                <option
                                    v-for="status in props.formOptions.statuses"
                                    :key="status"
                                    :value="status"
                                >
                                    {{ status }}
                                </option>
                            </select>
                            <InputError :message="form.errors.status" />
                        </div>

                        <div class="grid gap-2">
                            <label for="due_date" class="text-sm font-medium">
                                Due Date
                            </label>
                            <Input
                                id="due_date"
                                v-model="form.due_date"
                                type="date"
                            />
                            <InputError :message="form.errors.due_date" />
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
                                v-model="form.assigned_to"
                                class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs"
                            >
                                <option value="">Unassigned</option>
                                <option
                                    v-for="user in props.formOptions.users"
                                    :key="user.id"
                                    :value="String(user.id)"
                                >
                                    {{ user.name }}
                                </option>
                            </select>
                            <InputError :message="form.errors.assigned_to" />
                        </div>

                        <div class="grid gap-2">
                            <label for="store_id" class="text-sm font-medium">
                                Store
                            </label>
                            <select
                                id="store_id"
                                v-model="form.store_id"
                                class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs"
                            >
                                <option value="">No Store</option>
                                <option
                                    v-for="store in props.formOptions.stores"
                                    :key="store.id"
                                    :value="String(store.id)"
                                >
                                    {{ store.name }}
                                </option>
                            </select>
                            <InputError :message="form.errors.store_id" />
                        </div>

                        <div class="grid gap-2">
                            <label for="contact_id" class="text-sm font-medium">
                                Contact
                            </label>
                            <select
                                id="contact_id"
                                v-model="form.contact_id"
                                class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs"
                            >
                                <option value="">No Contact</option>
                                <option
                                    v-for="contact in props.formOptions
                                        .contacts"
                                    :key="contact.id"
                                    :value="String(contact.id)"
                                >
                                    {{ contact.name }}
                                </option>
                            </select>
                            <InputError :message="form.errors.contact_id" />
                        </div>

                        <div class="flex items-center gap-3 md:col-span-2">
                            <Button :disabled="form.processing" type="submit">
                                Save Changes
                            </Button>
                            <Button variant="outline" as-child>
                                <Link href="/tasks">Cancel</Link>
                            </Button>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
