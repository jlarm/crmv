<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, useForm } from '@inertiajs/vue3';

interface SelectOption {
    id: number;
    name: string;
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
    formOptions: FormOptions;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Tasks',
        href: '/tasks',
    },
    {
        title: 'Create',
        href: '/tasks/create',
    },
];

const form = useForm({
    name: '',
    description: '',
    task_type: 'Call',
    priority: 'Medium',
    status: 'Open',
    due_date: '',
    assigned_to: '',
    store_id: '',
    contact_id: '',
});

function submit(): void {
    form.transform((data) => ({
        ...data,
        description: data.description || null,
        due_date: data.due_date || null,
        assigned_to: data.assigned_to || null,
        store_id: data.store_id || null,
        contact_id: data.contact_id || null,
    })).post('/tasks');
}
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Create Task" />

        <div class="space-y-6 p-6">
            <div class="flex items-center justify-between gap-3">
                <div>
                    <h1 class="text-2xl font-semibold tracking-tight">
                        Create Task
                    </h1>
                    <p class="text-sm text-muted-foreground">
                        Add a task and optionally assign related records.
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
                            <Select v-model="form.task_type">
                                <SelectTrigger id="task_type">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem
                                        v-for="taskType in props.formOptions.taskTypes"
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
                            <label for="priority" class="text-sm font-medium">
                                Priority
                            </label>
                            <Select v-model="form.priority">
                                <SelectTrigger id="priority">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem
                                        v-for="priority in props.formOptions.priorities"
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
                            <label for="status" class="text-sm font-medium">
                                Status
                            </label>
                            <Select v-model="form.status">
                                <SelectTrigger id="status">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem
                                        v-for="status in props.formOptions.statuses"
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
                            <Select
                                :model-value="form.assigned_to || '_unassigned_'"
                                @update:model-value="form.assigned_to = $event === '_unassigned_' ? '' : $event"
                            >
                                <SelectTrigger id="assigned_to">
                                    <SelectValue placeholder="Unassigned" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="_unassigned_">Unassigned</SelectItem>
                                    <SelectItem
                                        v-for="user in props.formOptions.users"
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
                            <label for="store_id" class="text-sm font-medium">
                                Store
                            </label>
                            <Select
                                :model-value="form.store_id || '_none_'"
                                @update:model-value="form.store_id = $event === '_none_' ? '' : $event"
                            >
                                <SelectTrigger id="store_id">
                                    <SelectValue placeholder="No Store" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="_none_">No Store</SelectItem>
                                    <SelectItem
                                        v-for="store in props.formOptions.stores"
                                        :key="store.id"
                                        :value="String(store.id)"
                                    >
                                        {{ store.name }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <InputError :message="form.errors.store_id" />
                        </div>

                        <div class="grid gap-2">
                            <label for="contact_id" class="text-sm font-medium">
                                Contact
                            </label>
                            <Select
                                :model-value="form.contact_id || '_none_'"
                                @update:model-value="form.contact_id = $event === '_none_' ? '' : $event"
                            >
                                <SelectTrigger id="contact_id">
                                    <SelectValue placeholder="No Contact" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="_none_">No Contact</SelectItem>
                                    <SelectItem
                                        v-for="contact in props.formOptions.contacts"
                                        :key="contact.id"
                                        :value="String(contact.id)"
                                    >
                                        {{ contact.name }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <InputError :message="form.errors.contact_id" />
                        </div>

                        <div class="flex items-center gap-3 md:col-span-2">
                            <Button :disabled="form.processing" type="submit">
                                Create Task
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
