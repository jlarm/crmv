<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { show as showCompany } from '@/routes/company';
import { Link } from '@inertiajs/vue3';

export interface DashboardTask {
    id: number;
    name: string;
    status: string;
    priority: string;
    taskType: string;
    dueDate: string | null;
    company: {
        id: number;
        name: string;
    };
    contact: {
        id: number;
        name: string;
    } | null;
    assignedTo: {
        id: number;
        name: string;
    } | null;
}

defineProps<{
    title: string;
    description: string;
    emptyMessage: string;
    tasks: DashboardTask[];
    variant?: 'default' | 'destructive';
}>();


function formatTaskDate(value: string | null): string {
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
</script>

<template>
    <Card>
        <CardHeader>
            <div class="flex items-center justify-between gap-2">
                <CardTitle>{{ title }}</CardTitle>
                <slot name="header-action" />
            </div>
            <p class="text-sm text-muted-foreground">{{ description }}</p>
        </CardHeader>
        <CardContent>
            <div class="max-h-[16rem] overflow-y-auto pr-2">
            <div v-if="tasks.length > 0" class="space-y-3">
                <Link
                    v-for="task in tasks"
                    :key="task.id"
                    :href="showCompany.url(task.company.id)"
                    :class="[
                        'block rounded-lg border p-4 transition',
                        variant === 'destructive'
                            ? 'border-destructive/20 bg-destructive/5 hover:bg-destructive/10'
                            : 'border-border hover:bg-muted/40',
                    ]"
                >
                    <div class="flex items-start justify-between gap-3">
                        <div class="space-y-1">
                            <p class="text-sm font-medium">{{ task.name }}</p>
                            <p class="text-sm text-muted-foreground">
                                {{ task.company.name }}
                                <span v-if="task.contact">
                                    · {{ task.contact.name }}
                                </span>
                                <span v-if="task.assignedTo">
                                    · {{ task.assignedTo.name }}
                                </span>
                            </p>
                        </div>
                        <div class="flex shrink-0 flex-col items-end gap-1">
                            <span
                                :class="[
                                    'text-xs font-medium tracking-wide uppercase',
                                    variant === 'destructive'
                                        ? 'text-destructive'
                                        : 'text-muted-foreground',
                                ]"
                            >
                                {{ formatTaskDate(task.dueDate) }}
                            </span>
                            <Badge
                                variant="outline"
                                :class="
                                    task.priority === 'High'
                                        ? 'border-red-500 text-red-500'
                                        : task.priority === 'Medium'
                                          ? 'border-amber-500 text-amber-500'
                                          : 'border-sky-500 text-sky-500'
                                "
                            >
                                {{ task.priority }}
                            </Badge>
                        </div>
                    </div>
                </Link>
            </div>
            <p v-else class="text-sm text-muted-foreground">
                {{ emptyMessage }}
            </p>
            </div>
        </CardContent>
    </Card>
</template>
