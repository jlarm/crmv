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

const totalDealerships = computed(() => props.dealerships.length)

const activeDealerships = computed(() =>
    props.dealerships.filter(d => d.status === 'active').length
)

const activePercentage = computed(() =>
    totalDealerships.value > 0 ? Math.round((activeDealerships.value / totalDealerships.value) * 100) : 0
)

const averageRating = computed(() => {
    const hotCount = props.dealerships.filter(d => d.rating === 'hot').length
    const warmCount = props.dealerships.filter(d => d.rating === 'warm').length
    const coldCount = props.dealerships.filter(d => d.rating === 'cold').length

    const totalWithRating = hotCount + warmCount + coldCount

    if (totalWithRating === 0) {
        return 0
    }

    const weightedSum = (hotCount * 5) + (warmCount * 3) + (coldCount * 1)
    return Math.round((weightedSum / totalWithRating) * 10) / 10
})</script>

<template>
    <div class="flex h-full flex-col justify-between p-4">
        <div class="space-y-1">
            <h3 class="text-sm font-medium text-muted-foreground">Total Dealerships</h3>
            <div class="flex items-end gap-2">
                <div class="text-2xl font-bold">{{ totalDealerships }}</div>
                <div class="text-xs text-green-600 dark:text-green-400 mb-1">
                    +12% vs last month
                </div>
            </div>
        </div>

        <div class="space-y-1">
            <h4 class="text-sm font-medium text-muted-foreground">Active Rate</h4>
            <div class="flex items-center gap-2">
                <div class="text-lg font-semibold">{{ activePercentage }}%</div>
                <div class="flex-1 bg-muted rounded-full h-2">
                    <div
                        class="bg-green-500 h-2 rounded-full transition-all duration-300"
                        :style="{ width: `${activePercentage}%` }"
                    ></div>
                </div>
            </div>
            <div class="text-xs text-muted-foreground">
                {{ activeDealerships }} of {{ totalDealerships }} active
            </div>
        </div>

        <div class="space-y-1">
            <h4 class="text-sm font-medium text-muted-foreground">Average Rating</h4>
            <div class="flex items-center gap-2">
                <div class="text-lg font-semibold">{{ averageRating }}/5</div>
                <div class="flex">
                    <template v-for="i in 5" :key="i">
                        <svg
                            class="w-4 h-4"
                            :class="i <= Math.round(averageRating) ? 'text-yellow-400 fill-current' : 'text-gray-300 dark:text-gray-600'"
                            viewBox="0 0 20 20"
                        >
                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.286 3.967a1 1 0 00.95.69h4.169c.969 0 1.371 1.24.588 1.81l-3.372 2.448a1 1 0 00-.364 1.118l1.286 3.967c.3.921-.755 1.688-1.54 1.118L10 13.347l-3.372 2.448c-.784.57-1.838-.197-1.54-1.118l1.286-3.967a1 1 0 00-.364-1.118L2.64 9.394c-.783-.57-.38-1.81.588-1.81h4.169a1 1 0 00.95-.69l1.286-3.967z"/>
                        </svg>
                    </template>
                </div>
            </div>
        </div>
    </div>
</template>