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
    DialogTrigger,
} from '@/components/ui/dialog';
import { Field, FieldLabel } from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import type { Company, TaskItem, User } from '@/pages/Company/types';
import { Form, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps<{
    company: Company;
    allUsers: User[];
}>();

const isTaskCreateOpen = ref(false);
const isTaskEditOpen = ref(false);
const editingTask = ref<TaskItem | null>(null);

const priorityOrder = ['High', 'Medium', 'Low'] as const;
const taskTypes = ['Call', 'Email', 'Meeting'];
const priorities = ['Low', 'Medium', 'High'];
const statuses = ['Open', 'In Progress', 'Completed'];

const availableUsers = computed(() => {
    return props.allUsers;
});

const completedTasks = computed(() => {
    return props.company.tasks.filter((task) => task.status === 'Completed');
});

const activeTasksByPriority = computed(() => {
    return {
        High: props.company.tasks.filter((task) => {
            return task.status !== 'Completed' && task.priority === 'High';
        }),
        Medium: props.company.tasks.filter((task) => {
            return task.status !== 'Completed' && task.priority === 'Medium';
        }),
        Low: props.company.tasks.filter((task) => {
            return task.status !== 'Completed' && task.priority === 'Low';
        }),
    };
});

const prioritySummary = computed(() => {
    return priorityOrder.map((label) => ({
        label,
        count: activeTasksByPriority.value[label].length,
    }));
});

const hasNoTasks = computed(() => {
    return (
        prioritySummary.value.every((summary) => summary.count === 0) &&
        completedTasks.value.length === 0
    );
});

function handleTaskCreateSuccess(): void {
    isTaskCreateOpen.value = false;
}

function openTaskEdit(task: TaskItem): void {
    editingTask.value = task;
    isTaskEditOpen.value = true;
}

function deleteTask(companyId: number, task: TaskItem): void {
    if (!window.confirm('Delete this task?')) {
        return;
    }

    router.delete(`/companies/${companyId}/tasks/${task.id}`);
}

function toggleTaskComplete(
    companyId: number,
    task: TaskItem,
    checked: boolean | 'indeterminate',
): void {
    if (checked === 'indeterminate') {
        return;
    }

    router.put(
        `/companies/${companyId}/tasks/${task.id}`,
        {
            name: task.name,
            description: task.description,
            task_type: task.taskType,
            priority: task.priority,
            status: checked ? 'Completed' : 'Open',
            due_date: task.dueDate,
            assigned_to: task.assignedTo?.id ?? null,
            store_id: task.store?.id ?? null,
            contact_id: task.contact?.id ?? null,
        },
        {
            preserveScroll: true,
            preserveState: true,
        },
    );
}

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
</script>

<template>
    <div class="mx-auto mt-6 w-full">
        <div class="flex items-center justify-between">
            <h2
                class="text-lg font-semibold text-slate-900 dark:text-slate-100"
            >
                Tasks
            </h2>
            <Dialog v-model:open="isTaskCreateOpen">
                <DialogTrigger as-child>
                    <Button>Create Task</Button>
                </DialogTrigger>
                <DialogContent class="sm:max-w-2xl">
                    <DialogHeader>
                        <DialogTitle>Create Task</DialogTitle>
                        <DialogDescription>
                            Add a task for this company.
                        </DialogDescription>
                    </DialogHeader>
                    <Form
                        :action="`/companies/${company.id}/tasks`"
                        method="post"
                        class="grid grid-cols-2 gap-4"
                        reset-on-success
                        :on-success="handleTaskCreateSuccess"
                        v-slot="{ errors, processing }"
                    >
                        <Field class="col-span-2">
                            <FieldLabel for="task_name">Name</FieldLabel>
                            <Input id="task_name" name="name" required />
                            <InputError :message="errors.name" />
                        </Field>
                        <Field class="col-span-2">
                            <FieldLabel for="task_description">
                                Description
                            </FieldLabel>
                            <Textarea
                                id="task_description"
                                name="description"
                            />
                            <InputError :message="errors.description" />
                        </Field>
                        <Field>
                            <FieldLabel for="task_type">Task Type</FieldLabel>
                            <select
                                id="task_type"
                                name="task_type"
                                class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs"
                            >
                                <option
                                    v-for="taskType in taskTypes"
                                    :key="taskType"
                                    :value="taskType"
                                >
                                    {{ taskType }}
                                </option>
                            </select>
                            <InputError :message="errors.task_type" />
                        </Field>
                        <Field>
                            <FieldLabel for="task_priority"
                                >Priority</FieldLabel
                            >
                            <select
                                id="task_priority"
                                name="priority"
                                class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs"
                            >
                                <option
                                    v-for="priority in priorities"
                                    :key="priority"
                                    :value="priority"
                                >
                                    {{ priority }}
                                </option>
                            </select>
                            <InputError :message="errors.priority" />
                        </Field>
                        <Field>
                            <FieldLabel for="task_status">Status</FieldLabel>
                            <select
                                id="task_status"
                                name="status"
                                class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs"
                            >
                                <option
                                    v-for="status in statuses"
                                    :key="status"
                                    :value="status"
                                >
                                    {{ status }}
                                </option>
                            </select>
                            <InputError :message="errors.status" />
                        </Field>
                        <Field>
                            <FieldLabel for="task_due_date"
                                >Due Date</FieldLabel
                            >
                            <Input
                                id="task_due_date"
                                name="due_date"
                                type="date"
                            />
                            <InputError :message="errors.due_date" />
                        </Field>
                        <Field>
                            <FieldLabel for="task_assigned_to">
                                Assigned To
                            </FieldLabel>
                            <select
                                id="task_assigned_to"
                                name="assigned_to"
                                class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs"
                            >
                                <option value="">Unassigned</option>
                                <option
                                    v-for="user in availableUsers"
                                    :key="user.id"
                                    :value="user.id"
                                >
                                    {{ user.name }}
                                </option>
                            </select>
                            <InputError :message="errors.assigned_to" />
                        </Field>
                        <Field>
                            <FieldLabel for="task_store">Store</FieldLabel>
                            <select
                                id="task_store"
                                name="store_id"
                                class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs"
                            >
                                <option value="">No Store</option>
                                <option
                                    v-for="store in company.stores"
                                    :key="store.id"
                                    :value="store.id"
                                >
                                    {{ store.name }}
                                </option>
                            </select>
                            <InputError :message="errors.store_id" />
                        </Field>
                        <Field>
                            <FieldLabel for="task_contact">Contact</FieldLabel>
                            <select
                                id="task_contact"
                                name="contact_id"
                                class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs"
                            >
                                <option value="">No Contact</option>
                                <option
                                    v-for="contact in company.contacts"
                                    :key="contact.id"
                                    :value="contact.id"
                                >
                                    {{ contact.name }}
                                </option>
                            </select>
                            <InputError :message="errors.contact_id" />
                        </Field>
                        <DialogFooter class="col-span-2">
                            <Button :disabled="processing">Create</Button>
                        </DialogFooter>
                    </Form>
                </DialogContent>
            </Dialog>
        </div>

        <div class="mt-6 space-y-6">
            <div class="grid gap-4 md:grid-cols-3">
                <Card
                    v-for="summary in prioritySummary"
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
                <Card v-for="priority in priorityOrder" :key="priority">
                    <CardHeader
                        class="flex flex-row items-center justify-between"
                    >
                        <CardTitle>{{ priority }} Priority</CardTitle>
                        <Badge variant="secondary">
                            {{ activeTasksByPriority[priority].length }}
                        </Badge>
                    </CardHeader>
                    <CardContent class="p-0">
                        <div
                            v-if="activeTasksByPriority[priority].length === 0"
                            class="px-6 py-10 text-center text-sm text-muted-foreground"
                        >
                            No {{ priority.toLowerCase() }} priority tasks for
                            this company.
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
                                            Assignee
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
                                        v-for="task in activeTasksByPriority[
                                            priority
                                        ]"
                                        :key="task.id"
                                        class="cursor-pointer border-t border-border/60 transition hover:bg-muted/30"
                                        @click="openTaskEdit(task)"
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
                                                                company.id,
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
                                                        v-if="task.description"
                                                        class="text-muted-foreground"
                                                    >
                                                        {{ task.description }}
                                                    </p>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 align-top">
                                            {{ task.taskType }}
                                        </td>
                                        <td class="px-6 py-4 align-top">
                                            {{ task.status }}
                                        </td>
                                        <td class="px-6 py-4 align-top">
                                            {{
                                                task.assignedTo?.name ||
                                                'Unassigned'
                                            }}
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
                                            {{ formatDueDate(task.dueDate) }}
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
                        This company does not have any tasks yet.
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader
                        class="flex flex-row items-center justify-between"
                    >
                        <CardTitle>Completed</CardTitle>
                        <Badge variant="secondary">
                            {{ completedTasks.length }}
                        </Badge>
                    </CardHeader>
                    <CardContent class="p-0">
                        <div
                            v-if="completedTasks.length === 0"
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
                                            Assignee
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
                                        v-for="task in completedTasks"
                                        :key="task.id"
                                        class="cursor-pointer border-t border-border/60 transition hover:bg-muted/30"
                                        @click="openTaskEdit(task)"
                                    >
                                        <td class="px-6 py-4 align-top">
                                            <div class="flex items-start gap-3">
                                                <div @click.stop>
                                                    <Checkbox
                                                        :model-value="true"
                                                        @update:model-value="
                                                            toggleTaskComplete(
                                                                company.id,
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
                                                        v-if="task.description"
                                                        class="text-muted-foreground"
                                                    >
                                                        {{ task.description }}
                                                    </p>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 align-top">
                                            {{ task.taskType }}
                                        </td>
                                        <td class="px-6 py-4 align-top">
                                            {{ task.status }}
                                        </td>
                                        <td class="px-6 py-4 align-top">
                                            {{
                                                task.assignedTo?.name ||
                                                'Unassigned'
                                            }}
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
                                            {{ formatDueDate(task.dueDate) }}
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>

        <Dialog v-model:open="isTaskEditOpen">
            <DialogContent class="sm:max-w-2xl">
                <DialogHeader>
                    <DialogTitle>Edit Task</DialogTitle>
                    <DialogDescription>
                        Update task details.
                    </DialogDescription>
                </DialogHeader>
                <Form
                    v-if="editingTask"
                    :action="`/companies/${company.id}/tasks/${editingTask.id}`"
                    method="put"
                    class="grid grid-cols-2 gap-4"
                    v-slot="{ errors, processing }"
                >
                    <Field class="col-span-2">
                        <FieldLabel for="task_edit_name">Name</FieldLabel>
                        <Input
                            id="task_edit_name"
                            name="name"
                            :default-value="editingTask.name"
                            required
                        />
                        <InputError :message="errors.name" />
                    </Field>
                    <Field class="col-span-2">
                        <FieldLabel for="task_edit_description">
                            Description
                        </FieldLabel>
                        <Textarea
                            id="task_edit_description"
                            name="description"
                            :default-value="editingTask.description || ''"
                        />
                        <InputError :message="errors.description" />
                    </Field>
                    <Field>
                        <FieldLabel for="task_edit_type">Task Type</FieldLabel>
                        <select
                            id="task_edit_type"
                            name="task_type"
                            class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs"
                        >
                            <option
                                v-for="taskType in taskTypes"
                                :key="taskType"
                                :value="taskType"
                                :selected="editingTask.taskType === taskType"
                            >
                                {{ taskType }}
                            </option>
                        </select>
                        <InputError :message="errors.task_type" />
                    </Field>
                    <Field>
                        <FieldLabel for="task_edit_priority"
                            >Priority</FieldLabel
                        >
                        <select
                            id="task_edit_priority"
                            name="priority"
                            class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs"
                        >
                            <option
                                v-for="priority in priorities"
                                :key="priority"
                                :value="priority"
                                :selected="editingTask.priority === priority"
                            >
                                {{ priority }}
                            </option>
                        </select>
                        <InputError :message="errors.priority" />
                    </Field>
                    <Field>
                        <FieldLabel for="task_edit_status">Status</FieldLabel>
                        <select
                            id="task_edit_status"
                            name="status"
                            class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs"
                        >
                            <option
                                v-for="status in statuses"
                                :key="status"
                                :value="status"
                                :selected="editingTask.status === status"
                            >
                                {{ status }}
                            </option>
                        </select>
                        <InputError :message="errors.status" />
                    </Field>
                    <Field>
                        <FieldLabel for="task_edit_due_date"
                            >Due Date</FieldLabel
                        >
                        <Input
                            id="task_edit_due_date"
                            name="due_date"
                            type="date"
                            :default-value="editingTask.dueDate || ''"
                        />
                        <InputError :message="errors.due_date" />
                    </Field>
                    <Field>
                        <FieldLabel for="task_edit_assigned_to">
                            Assigned To
                        </FieldLabel>
                        <select
                            id="task_edit_assigned_to"
                            name="assigned_to"
                            class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs"
                        >
                            <option value="">Unassigned</option>
                            <option
                                v-for="user in availableUsers"
                                :key="user.id"
                                :value="user.id"
                                :selected="
                                    editingTask.assignedTo?.id === user.id
                                "
                            >
                                {{ user.name }}
                            </option>
                        </select>
                        <InputError :message="errors.assigned_to" />
                    </Field>
                    <Field>
                        <FieldLabel for="task_edit_store">Store</FieldLabel>
                        <select
                            id="task_edit_store"
                            name="store_id"
                            class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs"
                        >
                            <option value="">No Store</option>
                            <option
                                v-for="store in company.stores"
                                :key="store.id"
                                :value="store.id"
                                :selected="editingTask.store?.id === store.id"
                            >
                                {{ store.name }}
                            </option>
                        </select>
                        <InputError :message="errors.store_id" />
                    </Field>
                    <Field>
                        <FieldLabel for="task_edit_contact">Contact</FieldLabel>
                        <select
                            id="task_edit_contact"
                            name="contact_id"
                            class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs"
                        >
                            <option value="">No Contact</option>
                            <option
                                v-for="contact in company.contacts"
                                :key="contact.id"
                                :value="contact.id"
                                :selected="
                                    editingTask.contact?.id === contact.id
                                "
                            >
                                {{ contact.name }}
                            </option>
                        </select>
                        <InputError :message="errors.contact_id" />
                    </Field>
                    <DialogFooter class="col-span-2 justify-between">
                        <Button
                            type="button"
                            variant="destructive"
                            @click="deleteTask(company.id, editingTask)"
                        >
                            Delete Task
                        </Button>
                        <Button :disabled="processing">Save</Button>
                    </DialogFooter>
                </Form>
            </DialogContent>
        </Dialog>
    </div>
</template>
