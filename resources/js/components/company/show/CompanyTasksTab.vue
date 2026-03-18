<script setup lang="ts">
import InputError from '@/components/InputError.vue';
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
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Field, FieldLabel } from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import type { Company, TaskItem, User } from '@/pages/Company/types';
import { Form, router } from '@inertiajs/vue3';
import { MoreVertical } from 'lucide-vue-next';
import { computed, ref } from 'vue';

const props = defineProps<{
    company: Company;
    allUsers: User[];
}>();

const isTaskCreateOpen = ref(false);
const isTaskEditOpen = ref(false);
const editingTask = ref<TaskItem | null>(null);

const taskTypes = ['Call', 'Email', 'Meeting'];
const priorities = ['Low', 'Medium', 'High'];
const statuses = ['Open', 'In Progress', 'Completed'];

const availableUsers = computed(() => {
    return props.allUsers;
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

        <div class="mt-6 grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            <Card
                v-for="task in company.tasks"
                :key="task.id"
                class="rounded-2xl border border-slate-200 shadow-sm transition hover:shadow-md dark:border-slate-800"
            >
                <CardHeader>
                    <div class="flex w-full items-start justify-between gap-4">
                        <div class="space-y-2">
                            <CardTitle
                                class="text-base font-semibold text-slate-900 dark:text-slate-100"
                            >
                                {{ task.name }}
                            </CardTitle>
                            <div
                                class="flex flex-wrap gap-2 text-xs text-slate-500"
                            >
                                <span>{{ task.taskType }}</span>
                                <span>·</span>
                                <span>{{ task.priority }}</span>
                                <span>·</span>
                                <span>{{ task.status }}</span>
                            </div>
                        </div>
                        <DropdownMenu>
                            <DropdownMenuTrigger as-child>
                                <Button
                                    variant="ghost"
                                    size="icon"
                                    class="text-slate-500 hover:text-slate-700"
                                    aria-label="Task actions"
                                >
                                    <MoreVertical class="h-4 w-4" />
                                </Button>
                            </DropdownMenuTrigger>
                            <DropdownMenuContent align="end">
                                <DropdownMenuItem @click="openTaskEdit(task)">
                                    Edit
                                </DropdownMenuItem>
                                <DropdownMenuItem
                                    class="text-red-600 focus:text-red-600"
                                    @click="deleteTask(company.id, task)"
                                >
                                    Delete
                                </DropdownMenuItem>
                            </DropdownMenuContent>
                        </DropdownMenu>
                    </div>
                </CardHeader>
                <CardContent class="space-y-2 text-sm text-slate-600">
                    <p v-if="task.description">{{ task.description }}</p>
                    <p class="text-xs text-slate-500">
                        Due: {{ formatDueDate(task.dueDate) }}
                    </p>
                    <p class="text-xs text-slate-500">
                        Assigned: {{ task.assignedTo?.name || 'Unassigned' }}
                    </p>
                    <p class="text-xs text-slate-500">
                        Store: {{ task.store?.name || 'None' }}
                    </p>
                    <p class="text-xs text-slate-500">
                        Contact: {{ task.contact?.name || 'None' }}
                    </p>
                </CardContent>
            </Card>

            <p
                v-if="company.tasks.length === 0"
                class="text-xs text-muted-foreground"
            >
                No tasks yet.
            </p>
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
                    <DialogFooter class="col-span-2">
                        <Button :disabled="processing">Save</Button>
                    </DialogFooter>
                </Form>
            </DialogContent>
        </Dialog>
    </div>
</template>
