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

const typeDistribution = computed(() => {
    const counts: Record<string, number> = {}

    props.dealerships.forEach(dealership => {
        const type = dealership.type || 'unknown'
        counts[type] = (counts[type] || 0) + 1
    })

    const entries = Object.entries(counts)
        .map(([type, count]) => ({
            type,
            count,
            percentage: Math.round((count / props.dealerships.length) * 100)
        }))
        .sort((a, b) => b.count - a.count)

    return entries
})

const topPerformingLocations = computed(() => {
    const locationRatings: Record<string, { total: number; hot: number; warm: number; cold: number }> = {}

    props.dealerships.forEach(dealership => {
        const location = dealership.location || 'unknown'
        if (!locationRatings[location]) {
            locationRatings[location] = { total: 0, hot: 0, warm: 0, cold: 0 }
        }

        locationRatings[location].total++

        switch (dealership.rating) {
            case 'hot':
                locationRatings[location].hot++
                break
            case 'warm':
                locationRatings[location].warm++
                break
            case 'cold':
                locationRatings[location].cold++
                break
        }
    })

    return Object.entries(locationRatings)
        .map(([location, ratings]) => {
            const score = (ratings.hot * 5 + ratings.warm * 3 + ratings.cold * 1) / ratings.total
            return {
                location,
                score: Math.round(score * 10) / 10,
                total: ratings.total
            }
        })
        .sort((a, b) => b.score - a.score)
        .slice(0, 3)
})

const maxTypeCount = computed(() => {
    return Math.max(...typeDistribution.value.map(item => item.count))
})

const getTypeColor = (index: number): string => {
    const colors = [
        'bg-blue-500',
        'bg-purple-500',
        'bg-green-500',
        'bg-yellow-500',
        'bg-red-500',
        'bg-indigo-500',
        'bg-pink-500',
        'bg-gray-500'
    ]
    return colors[index % colors.length]
}
</script>

<template>
    <div class="flex h-full flex-col justify-between p-4">
        <div class="space-y-1 mb-4">
            <h3 class="text-sm font-medium text-muted-foreground">Type & Performance Analytics</h3>
            <div class="text-xs text-muted-foreground">Distribution and top locations</div>
        </div>

        <!-- Type Distribution -->
        <div class="space-y-3 mb-4">
            <h4 class="text-xs font-medium text-muted-foreground uppercase tracking-wide">By Type</h4>
            <div class="space-y-2">
                <template v-for="(item, index) in typeDistribution.slice(0, 4)" :key="item.type">
                    <div class="flex items-center gap-3">
                        <div class="flex items-center gap-2 flex-1 min-w-0">
                            <div
                                :class="getTypeColor(index)"
                                class="w-2 h-2 rounded-full flex-shrink-0"
                            ></div>
                            <span class="text-sm font-medium capitalize truncate">{{ item.type }}</span>
                        </div>
                        <div class="flex items-center gap-2 flex-shrink-0">
                            <div class="w-16 bg-muted rounded-full h-2">
                                <div
                                    :class="getTypeColor(index)"
                                    class="h-2 rounded-full transition-all duration-300"
                                    :style="{ width: `${(item.count / maxTypeCount) * 100}%` }"
                                ></div>
                            </div>
                            <span class="text-xs font-medium w-6 text-right">{{ item.count }}</span>
                        </div>
                    </div>
                </template>
            </div>
        </div>

        <!-- Top Performing Locations -->
        <div class="space-y-3">
            <h4 class="text-xs font-medium text-muted-foreground uppercase tracking-wide">Top Locations</h4>
            <div class="space-y-2">
                <template v-for="(location, index) in topPerformingLocations" :key="location.location">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2 flex-1 min-w-0">
                            <div class="flex items-center justify-center w-5 h-5 rounded-full bg-muted text-xs font-medium">
                                {{ index + 1 }}
                            </div>
                            <div class="min-w-0 flex-1">
                                <div class="text-sm font-medium truncate">{{ location.location }}</div>
                                <div class="text-xs text-muted-foreground">{{ location.total }} dealerships</div>
                            </div>
                        </div>
                        <div class="flex items-center gap-1 flex-shrink-0">
                            <div class="text-sm font-semibold">{{ location.score }}</div>
                            <svg class="w-3 h-3 text-yellow-400 fill-current" viewBox="0 0 20 20">
                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.286 3.967a1 1 0 00.95.69h4.169c.969 0 1.371 1.24.588 1.81l-3.372 2.448a1 1 0 00-.364 1.118l1.286 3.967c.3.921-.755 1.688-1.54 1.118L10 13.347l-3.372 2.448c-.784.57-1.838-.197-1.54-1.118l1.286-3.967a1 1 0 00-.364-1.118L2.64 9.394c-.783-.57-.38-1.81.588-1.81h4.169a1 1 0 00.95-.69l1.286-3.967z"/>
                            </svg>
                        </div>
                    </div>
                </template>
            </div>
        </div>

        <!-- Regional Summary -->
        <div class="mt-4 pt-3 border-t border-border">
            <div class="grid grid-cols-2 gap-4 text-center">
                <div>
                    <div class="text-lg font-bold">{{ new Set(props.dealerships.map(d => d.location)).size }}</div>
                    <div class="text-xs text-muted-foreground">regions</div>
                </div>
                <div>
                    <div class="text-lg font-bold">{{ typeDistribution.length }}</div>
                    <div class="text-xs text-muted-foreground">types</div>
                </div>
            </div>
        </div>
    </div>
</template>