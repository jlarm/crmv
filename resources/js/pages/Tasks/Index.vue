<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';

interface TaskItem {
    id: number;
    name: string;
    description: string | null;
    task_type: string;
    priority: string;
    status: string;
    due_date: string | null;
    assignedTo: { id: number; name: string } | null;
    store: { id: number; name: string } | null;
    contact: { id: number; name: string } | null;
}

const props = defineProps<{
    tasks: TaskItem[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Tasks',
        href: '/tasks',
    },
];

function deleteTask(taskId: number): void {
    if (!window.confirm('Delete this task?')) {
        return;
    }

    router.delete(`/tasks/${taskId}`, {
        preserveScroll: true,
    });
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
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Tasks" />

        <div class="space-y-6 p-6">
            <div class="flex items-center justify-between gap-3">
                <div>
                    <h1 class="text-2xl font-semibold tracking-tight">Tasks</h1>
                    <p class="text-sm text-muted-foreground">
                        Track calls, emails, and meetings across the CRM.
                    </p>
                </div>

                <Button as-child>
                    <Link href="/tasks/create">New Task</Link>
                </Button>
            </div>

            <div class="space-y-4">
                <Card v-for="task in props.tasks" :key="task.id">
                    <CardHeader
                        class="flex flex-row items-start justify-between gap-4"
                    >
                        <div class="space-y-2">
                            <div class="flex items-center gap-2">
                                <CardTitle class="text-base">
                                    {{ task.name }}
                                </CardTitle>
                                <Badge variant="outline">
                                    {{ task.task_type }}
                                </Badge>
                                <Badge variant="secondary">
                                    {{ task.priority }}
                                </Badge>
                                <Badge>{{ task.status }}</Badge>
                            </div>
                            <p
                                v-if="task.description"
                                class="text-sm text-muted-foreground"
                            >
                                {{ task.description }}
                            </p>
                        </div>

                        <div class="text-right text-sm text-muted-foreground">
                            {{ formatDueDate(task.due_date) }}
                        </div>
                    </CardHeader>

                    <CardContent
                        class="flex flex-wrap items-center justify-between gap-4"
                    >
                        <div
                            class="grid gap-1 text-sm text-muted-foreground sm:grid-cols-3 sm:gap-6"
                        >
                            <span>
                                Assigned:
                                {{ task.assignedTo?.name || 'Unassigned' }}
                            </span>
                            <span>Store: {{ task.store?.name || 'None' }}</span>
                            <span>
                                Contact: {{ task.contact?.name || 'None' }}
                            </span>
                        </div>

                        <div class="flex items-center gap-2">
                            <Button variant="outline" size="sm" as-child>
                                <Link :href="`/tasks/${task.id}/edit`">
                                    Edit
                                </Link>
                            </Button>
                            <Button
                                variant="destructive"
                                size="sm"
                                type="button"
                                @click="deleteTask(task.id)"
                            >
                                Delete
                            </Button>
                        </div>
                    </CardContent>
                </Card>

                <Card v-if="props.tasks.length === 0">
                    <CardContent
                        class="py-10 text-center text-sm text-muted-foreground"
                    >
                        No tasks have been created yet.
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
