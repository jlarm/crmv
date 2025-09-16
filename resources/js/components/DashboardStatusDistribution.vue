<script setup lang="ts">
import { computed } from 'vue'

interface Dealership {
    id: number
    name: string
    location: string
    phone: string
    status: string
    rating: string
    type: string
}

const props = defineProps<{
    dealerships: Dealership[]
}>()

const statusCounts = computed(() => {
    const counts: Record<string, number> = {}

    props.dealerships.forEach(dealership => {
        const status = dealership.status || 'unknown'
        counts[status] = (counts[status] || 0) + 1
    })

    return Object.entries(counts)
        .map(([status, count]) => ({
            status,
            count,
            percentage: Math.round((count / props.dealerships.length) * 100)
        }))
        .sort((a, b) => b.count - a.count)
})

const getStatusColor = (status: string): string => {
    switch (status.toLowerCase()) {
        case 'active':
            return 'bg-green-500'
        case 'inactive':
            return 'bg-gray-500'
        case 'pending':
            return 'bg-yellow-500'
        case 'suspended':
            return 'bg-red-500'
        default:
            return 'bg-blue-500'
    }
}

const getStatusBadgeColor = (status: string): string => {
    switch (status.toLowerCase()) {
        case 'active':
            return 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300'
        case 'inactive':
            return 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-300'
        case 'pending':
            return 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300'
        case 'suspended':
            return 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300'
        default:
            return 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300'
    }
}

const totalDealerships = computed(() => props.dealerships.length)
</script>

<template>
    <div class="flex h-full flex-col justify-between p-4">
        <div class="space-y-1 mb-4">
            <h3 class="text-sm font-medium text-muted-foreground">Status Distribution</h3>
            <div class="text-xs text-muted-foreground">{{ totalDealerships }} total dealerships</div>
        </div>

        <!-- Donut Chart Representation -->
        <div class="flex items-center justify-center mb-4">
            <div class="relative w-24 h-24">
                <svg class="w-24 h-24 transform -rotate-90" viewBox="0 0 36 36">
                    <template v-for="(item, index) in statusCounts" :key="item.status">
                        <circle
                            :class="getStatusColor(item.status)"
                            cx="18"
                            cy="18"
                            r="15.915"
                            fill="transparent"
                            :stroke-dasharray="`${item.percentage} ${100 - item.percentage}`"
                            :stroke-dashoffset="statusCounts.slice(0, index).reduce((acc, prev) => acc - prev.percentage, 25)"
                            stroke-width="3"
                        />
                    </template>
                </svg>
                <div class="absolute inset-0 flex items-center justify-center">
                    <div class="text-center">
                        <div class="text-lg font-bold">{{ statusCounts.length }}</div>
                        <div class="text-xs text-muted-foreground">types</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Status Legend -->
        <div class="space-y-2">
            <template v-for="item in statusCounts" :key="item.status">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <div
                            :class="getStatusColor(item.status)"
                            class="w-3 h-3 rounded-full"
                        ></div>
                        <span
                            :class="getStatusBadgeColor(item.status)"
                            class="inline-flex px-2 py-1 text-xs font-medium rounded-full capitalize"
                        >
                            {{ item.status }}
                        </span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="text-sm font-medium">{{ item.count }}</span>
                        <span class="text-xs text-muted-foreground">({{ item.percentage }}%)</span>
                    </div>
                </div>
            </template>
        </div>

        <!-- Trend Indicator -->
        <div class="mt-4 pt-3 border-t border-border">
            <div class="flex items-center justify-between text-xs">
                <span class="text-muted-foreground">vs last week</span>
                <span class="text-green-600 dark:text-green-400">+3 active</span>
            </div>
        </div>
    </div>
</template>