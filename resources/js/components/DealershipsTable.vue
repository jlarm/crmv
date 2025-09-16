<script setup lang="ts">
import { ref, computed, h } from 'vue'
import {
    FlexRender,
    getCoreRowModel,
    getFilteredRowModel,
    getPaginationRowModel,
    getSortedRowModel,
    useVueTable,
    type ColumnDef,
    type ColumnFiltersState,
    type SortingState,
} from '@tanstack/vue-table'

// Add these missing imports
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table'
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/components/ui/popover'
import {
    Collapsible,
    CollapsibleContent,
    CollapsibleTrigger,
} from '@/components/ui/collapsible'

interface Dealership {
    id: number
    name: string
    location: string
    phone: string
    email: string
    status: string
    rating: string
    type: string
}

const props = defineProps<{
    dealerships: Dealership[]
}>()

// Table state - make sure these are defined
const sorting = ref<SortingState>([])
const columnFilters = ref<ColumnFiltersState>([])
const globalFilter = ref('')

// Filter states
const selectedRating = ref<string>('all')
const selectedStatus = ref<string>('all')
const selectedType = ref<string>('all')

// Collapsible states for filter sections
const ratingOpen = ref(false)
const statusOpen = ref(false)
const typeOpen = ref(false)
const pageOpen = ref(false)

// Get unique values
const uniqueRatings = computed(() => {
    const ratings = Array.from(new Set(props.dealerships.map(d => d.rating)))
    return ratings.filter(Boolean).sort()
})

const uniqueStatuses = computed(() => {
    const statuses = Array.from(new Set(props.dealerships.map(d => d.status)))
    return statuses.filter(Boolean).sort()
})

const uniqueTypes = computed(() => {
    const types = Array.from(new Set(props.dealerships.map(d => d.type)))
    return types.filter(Boolean).sort()
})

// Column definitions
const columns: ColumnDef<Dealership>[] = [
    {
        accessorKey: 'name',
        header: 'Name',
    },
    {
        accessorKey: 'location',
        header: 'Location',
    },
    {
        accessorKey: 'phone',
        header: 'Phone',
        enableSorting: false,
    },
    {
        accessorKey: 'status',
        header: 'Status',
        cell: ({ row }) => {
            const status = row.getValue('status') as string
            return h('div', {
                class: `inline-flex px-2 py-1 text-xs font-semibold rounded-full ${
                    status === 'active'
                        ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300'
                        : 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-300'
                }`
            }, status)
        },
    },
    {
        accessorKey: 'rating',
        header: 'Rating',
        cell: ({ row }) => {
            const rating = row.getValue('rating') as string
            let colorClasses = ''

            if (rating === 'hot') {
                colorClasses = 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300'
            } else if (rating === 'warm') {
                colorClasses = 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300'
            } else if (rating === 'cold') {
                colorClasses = 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300'
            } else {
                colorClasses = 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-300'
            }

            return h('div', {
                class: `inline-flex px-2 py-1 text-xs font-semibold rounded-full ${colorClasses}`
            }, rating)
        },
    },
    // Hidden type column for filtering
    {
        accessorKey: 'type',
        header: 'Type',
        meta: {
            hidden: true,
        },
    },
]

// Create table instance
const table = useVueTable({
    get data() { return props.dealerships },
    columns,
    getCoreRowModel: getCoreRowModel(),
    getPaginationRowModel: getPaginationRowModel(),
    getSortedRowModel: getSortedRowModel(),
    getFilteredRowModel: getFilteredRowModel(),
    onSortingChange: updaterOrValue => valueUpdater(updaterOrValue, sorting),
    onColumnFiltersChange: updaterOrValue => valueUpdater(updaterOrValue, columnFilters),
    onGlobalFilterChange: updaterOrValue => valueUpdater(updaterOrValue, globalFilter),
    initialState: {
        pagination: {
            pageSize: 25,
        },
        columnVisibility: {
            type: false, // Hide the type column
        },
    },
    state: {
        get sorting() { return sorting.value },
        get columnFilters() { return columnFilters.value },
        get globalFilter() { return globalFilter.value },
    },
})

function valueUpdater<T>(updaterOrValue: any, ref: any) {
    ref.value = typeof updaterOrValue === 'function'
        ? updaterOrValue(ref.value)
        : updaterOrValue
}

// Filter functions
const updateRatingFilter = (value: string) => {
    selectedRating.value = value
    if (value === 'all') {
        table.getColumn('rating')?.setFilterValue(undefined)
    } else {
        table.getColumn('rating')?.setFilterValue(value)
    }
}

const updateStatusFilter = (value: string) => {
    selectedStatus.value = value
    if (value === 'all') {
        table.getColumn('status')?.setFilterValue(undefined)
    } else {
        table.getColumn('status')?.setFilterValue(value)
    }
}

const updateTypeFilter = (value: string) => {
    selectedType.value = value
    if (value === 'all') {
        table.getColumn('type')?.setFilterValue(undefined)
    } else {
        table.getColumn('type')?.setFilterValue(value)
    }
}

// Clear all filters
const clearAllFilters = () => {
    selectedRating.value = 'all'
    selectedStatus.value = 'all'
    selectedType.value = 'all'
    table.getColumn('rating')?.setFilterValue(undefined)
    table.getColumn('status')?.setFilterValue(undefined)
    table.getColumn('type')?.setFilterValue(undefined)
    globalFilter.value = ''
}
</script>

<template>
    <div class="w-full p-4">
        <!-- Search and Filters -->
        <div class="flex items-center justify-between py-4">
            <div class="flex items-center space-x-4">
                <!-- Search Input -->
                <Input
                    v-model="globalFilter"
                    placeholder="Filter dealerships..."
                    class="max-w-sm"
                />

                <!-- Filters Popover -->
                <Popover>
                    <PopoverTrigger as-child>
                        <Button variant="outline" class="ml-auto">
                            Filters
                            <svg width="15" height="15" viewBox="0 0 15 15" fill="none" xmlns="http://www.w3.org/2000/svg" class="ml-2 h-4 w-4">
                                <path d="m4.5 6.5 3 3 3-3" fill="currentColor"/>
                            </svg>
                        </Button>
                    </PopoverTrigger>
                    <PopoverContent align="end" class="w-[240px] p-2">
                        <!-- Rating Filter -->
                        <Collapsible v-model:open="ratingOpen">
                            <CollapsibleTrigger class="flex w-full items-center justify-between rounded-md px-3 py-2 hover:bg-accent hover:text-accent-foreground">
                                <span class="text-sm font-medium">Rating</span>
                                <div class="flex items-center space-x-2">
                                    <span class="text-xs text-muted-foreground">
                                        {{ selectedRating === 'all' ? 'All' : selectedRating }}
                                    </span>
                                    <svg width="15" height="15" viewBox="0 0 15 15" fill="none" xmlns="http://www.w3.org/2000/svg" :class="ratingOpen ? 'rotate-180' : ''" class="h-4 w-4 transition-transform">
                                        <path d="m4.5 6.5 3 3 3-3" fill="currentColor"/>
                                    </svg>
                                </div>
                            </CollapsibleTrigger>
                            <CollapsibleContent class="space-y-1 px-3 pb-2">
                                <button
                                    @click="updateRatingFilter('all')"
                                    class="w-full rounded-sm px-2 py-1 text-left text-sm hover:bg-accent hover:text-accent-foreground"
                                    :class="selectedRating === 'all' ? 'font-medium bg-accent' : ''"
                                >
                                    All ratings
                                </button>
                                <button
                                    v-for="rating in uniqueRatings"
                                    :key="rating"
                                    @click="updateRatingFilter(rating)"
                                    class="w-full rounded-sm px-2 py-1 text-left text-sm hover:bg-accent hover:text-accent-foreground"
                                    :class="selectedRating === rating ? 'font-medium bg-accent' : ''"
                                >
                                    {{ rating }}
                                </button>
                            </CollapsibleContent>
                        </Collapsible>

                        <!-- Status Filter -->
                        <Collapsible v-model:open="statusOpen">
                            <CollapsibleTrigger class="flex w-full items-center justify-between rounded-md px-3 py-2 hover:bg-accent hover:text-accent-foreground">
                                <span class="text-sm font-medium">Status</span>
                                <div class="flex items-center space-x-2">
                                    <span class="text-xs text-muted-foreground">
                                        {{ selectedStatus === 'all' ? 'All' : selectedStatus }}
                                    </span>
                                    <svg width="15" height="15" viewBox="0 0 15 15" fill="none" xmlns="http://www.w3.org/2000/svg" :class="statusOpen ? 'rotate-180' : ''" class="h-4 w-4 transition-transform">
                                        <path d="m4.5 6.5 3 3 3-3" fill="currentColor"/>
                                    </svg>
                                </div>
                            </CollapsibleTrigger>
                            <CollapsibleContent class="space-y-1 px-3 pb-2">
                                <button
                                    @click="updateStatusFilter('all')"
                                    class="w-full rounded-sm px-2 py-1 text-left text-sm hover:bg-accent hover:text-accent-foreground"
                                    :class="selectedStatus === 'all' ? 'font-medium bg-accent' : ''"
                                >
                                    All statuses
                                </button>
                                <button
                                    v-for="status in uniqueStatuses"
                                    :key="status"
                                    @click="updateStatusFilter(status)"
                                    class="w-full rounded-sm px-2 py-1 text-left text-sm hover:bg-accent hover:text-accent-foreground"
                                    :class="selectedStatus === status ? 'font-medium bg-accent' : ''"
                                >
                                    {{ status }}
                                </button>
                            </CollapsibleContent>
                        </Collapsible>

                        <!-- Type Filter -->
                        <Collapsible v-model:open="typeOpen">
                            <CollapsibleTrigger class="flex w-full items-center justify-between rounded-md px-3 py-2 hover:bg-accent hover:text-accent-foreground">
                                <span class="text-sm font-medium">Type</span>
                                <div class="flex items-center space-x-2">
                                    <span class="text-xs text-muted-foreground">
                                        {{ selectedType === 'all' ? 'All' : selectedType }}
                                    </span>
                                    <svg width="15" height="15" viewBox="0 0 15 15" fill="none" xmlns="http://www.w3.org/2000/svg" :class="typeOpen ? 'rotate-180' : ''" class="h-4 w-4 transition-transform">
                                        <path d="m4.5 6.5 3 3 3-3" fill="currentColor"/>
                                    </svg>
                                </div>
                            </CollapsibleTrigger>
                            <CollapsibleContent class="space-y-1 px-3 pb-2">
                                <button
                                    @click="updateTypeFilter('all')"
                                    class="w-full rounded-sm px-2 py-1 text-left text-sm hover:bg-accent hover:text-accent-foreground"
                                    :class="selectedType === 'all' ? 'font-medium bg-accent' : ''"
                                >
                                    All types
                                </button>
                                <button
                                    v-for="type in uniqueTypes"
                                    :key="type"
                                    @click="updateTypeFilter(type)"
                                    class="w-full rounded-sm px-2 py-1 text-left text-sm hover:bg-accent hover:text-accent-foreground"
                                    :class="selectedType === type ? 'font-medium bg-accent' : ''"
                                >
                                    {{ type }}
                                </button>
                            </CollapsibleContent>
                        </Collapsible>

                        <!-- Rows per page -->
                        <Collapsible v-model:open="pageOpen">
                            <CollapsibleTrigger class="flex w-full items-center justify-between rounded-md px-3 py-2 hover:bg-accent hover:text-accent-foreground">
                                <span class="text-sm font-medium">Per page</span>
                                <div class="flex items-center space-x-2">
                                    <span class="text-xs text-muted-foreground">
                                        {{ table.getState().pagination.pageSize }}
                                    </span>
                                    <svg width="15" height="15" viewBox="0 0 15 15" fill="none" xmlns="http://www.w3.org/2000/svg" :class="pageOpen ? 'rotate-180' : ''" class="h-4 w-4 transition-transform">
                                        <path d="m4.5 6.5 3 3 3-3" fill="currentColor"/>
                                    </svg>
                                </div>
                            </CollapsibleTrigger>
                            <CollapsibleContent class="space-y-1 px-3 pb-2">
                                <button
                                    @click="table.setPageSize(10)"
                                    class="w-full rounded-sm px-2 py-1 text-left text-sm hover:bg-accent hover:text-accent-foreground"
                                    :class="table.getState().pagination.pageSize === 10 ? 'font-medium bg-accent' : ''"
                                >
                                    10
                                </button>
                                <button
                                    @click="table.setPageSize(25)"
                                    class="w-full rounded-sm px-2 py-1 text-left text-sm hover:bg-accent hover:text-accent-foreground"
                                    :class="table.getState().pagination.pageSize === 25 ? 'font-medium bg-accent' : ''"
                                >
                                    25
                                </button>
                                <button
                                    @click="table.setPageSize(50)"
                                    class="w-full rounded-sm px-2 py-1 text-left text-sm hover:bg-accent hover:text-accent-foreground"
                                    :class="table.getState().pagination.pageSize === 50 ? 'font-medium bg-accent' : ''"
                                >
                                    50
                                </button>
                                <button
                                    @click="table.setPageSize(100)"
                                    class="w-full rounded-sm px-2 py-1 text-left text-sm hover:bg-accent hover:text-accent-foreground"
                                    :class="table.getState().pagination.pageSize === 100 ? 'font-medium bg-accent' : ''"
                                >
                                    100
                                </button>
                            </CollapsibleContent>
                        </Collapsible>

                        <div class="border-t pt-2 mt-2">
                            <button
                                @click="clearAllFilters"
                                class="w-full rounded-sm px-2 py-2 text-left text-sm text-red-600 hover:bg-red-50 hover:text-red-700 dark:text-red-400 dark:hover:bg-red-950 dark:hover:text-red-300"
                            >
                                Clear all filters
                            </button>
                        </div>
                    </PopoverContent>
                </Popover>
            </div>
        </div>

        <!-- Table -->
        <div class="rounded-md border">
            <Table>
                <TableHeader>
                    <TableRow v-for="headerGroup in table.getHeaderGroups()" :key="headerGroup.id">
                        <TableHead v-for="header in headerGroup.headers" :key="header.id" class="cursor-pointer">
                            <div
                                v-if="!header.isPlaceholder"
                                @click="header.column.getToggleSortingHandler()?.($event)"
                                class="flex items-center space-x-2"
                            >
                                <FlexRender
                                    :render="header.column.columnDef.header"
                                    :props="header.getContext()"
                                />
                                <span v-if="header.column.getIsSorted() === 'asc'">↑</span>
                                <span v-else-if="header.column.getIsSorted() === 'desc'">↓</span>
                            </div>
                        </TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    <template v-if="table.getRowModel().rows?.length">
                        <TableRow
                            v-for="row in table.getRowModel().rows"
                            :key="row.id"
                            :data-state="row.getIsSelected() && 'selected'"
                        >
                            <TableCell v-for="cell in row.getVisibleCells()" :key="cell.id">
                                <FlexRender
                                    :render="cell.column.columnDef.cell"
                                    :props="cell.getContext()"
                                />
                            </TableCell>
                        </TableRow>
                    </template>
                    <template v-else>
                        <TableRow>
                            <TableCell :colSpan="columns.length" class="h-24 text-center">
                                No dealerships found.
                            </TableCell>
                        </TableRow>
                    </template>
                </TableBody>
            </Table>
        </div>

        <!-- Pagination Controls -->
        <div class="flex items-center justify-between space-x-2 py-4">
            <div class="text-sm text-muted-foreground">
                Showing {{ table.getState().pagination.pageIndex * table.getState().pagination.pageSize + 1 }} to
                {{ Math.min((table.getState().pagination.pageIndex + 1) * table.getState().pagination.pageSize, table.getFilteredRowModel().rows.length) }}
                of {{ table.getFilteredRowModel().rows.length }} dealerships
            </div>
            <div class="flex items-center space-x-2">
                <Button
                    variant="outline"
                    size="sm"
                    :disabled="!table.getCanPreviousPage()"
                    @click="table.previousPage()"
                >
                    Previous
                </Button>
                <Button
                    variant="outline"
                    size="sm"
                    :disabled="!table.getCanNextPage()"
                    @click="table.nextPage()"
                >
                    Next
                </Button>
            </div>
        </div>
    </div>
</template>
