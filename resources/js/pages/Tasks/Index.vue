<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import CreateTaskDialog from '@/components/tasks/CreateTaskDialog.vue';
import EditTaskDialog from '@/components/tasks/EditTaskDialog.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import type { TaskFormOptions, TaskItem } from '@/pages/Tasks/types';
import { type BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

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

const isEditOpen = ref(false);
const editingTask = ref<TaskItem | null>(null);

const hasNoTasks = computed(() => {
    return (
        props.prioritySummary.every((summary) => summary.count === 0) &&
        props.completedTasks.length === 0
    );
});

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

function openEditDialog(task: TaskItem): void {
    editingTask.value = task;
    isEditOpen.value = true;
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

                <CreateTaskDialog :form-options="createFormOptions">
                    <Button>New Task</Button>
                </CreateTaskDialog>
            </div>

            <EditTaskDialog
                v-model:open="isEditOpen"
                :task="editingTask"
                :form-options="createFormOptions"
            />

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
                                            <div
                                                class="flex items-start gap-3"
                                            >
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
                                            <div
                                                class="flex items-start gap-3"
                                            >
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
