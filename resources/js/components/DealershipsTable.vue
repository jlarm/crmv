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
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
    DropdownMenuSub,
    DropdownMenuSubContent,
    DropdownMenuSubTrigger,
} from '@/components/ui/dropdown-menu'

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

// Get unique values
const uniqueRatings = computed(() => {
    const ratings = Array.from(new Set(props.dealerships.map(d => d.rating)))
    return ratings.filter(Boolean).sort()
})

const uniqueStatuses = computed(() => {
    const statuses = Array.from(new Set(props.dealerships.map(d => d.status)))
    return statuses.filter(Boolean).sort()
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

// Clear all filters
const clearAllFilters = () => {
    selectedRating.value = 'all'
    selectedStatus.value = 'all'
    table.getColumn('rating')?.setFilterValue(undefined)
    table.getColumn('status')?.setFilterValue(undefined)
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

                <!-- Filters Dropdown -->
                <DropdownMenu>
                    <DropdownMenuTrigger as-child>
                        <Button variant="outline" class="ml-auto">
                            Filters
                            <svg width="15" height="15" viewBox="0 0 15 15" fill="none" xmlns="http://www.w3.org/2000/svg" class="ml-2 h-4 w-4">
                                <path d="m4.5 6.5 3 3 3-3" fill="currentColor"/>
                            </svg>
                        </Button>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent align="end" class="w-[200px]">
                        <!-- Rating Filter -->
                        <DropdownMenuSub>
                            <DropdownMenuSubTrigger>
                                <span>Rating</span>
                                <span class="ml-auto text-xs text-muted-foreground">
                    {{ selectedRating === 'all' ? 'All' : selectedRating }}
                  </span>
                            </DropdownMenuSubTrigger>
                            <DropdownMenuSubContent>
                                <DropdownMenuItem @click="updateRatingFilter('all')">
                    <span :class="selectedRating === 'all' ? 'font-medium' : ''">
                      All ratings
                    </span>
                                </DropdownMenuItem>
                                <DropdownMenuSeparator />
                                <DropdownMenuItem
                                    v-for="rating in uniqueRatings"
                                    :key="rating"
                                    @click="updateRatingFilter(rating)"
                                >
                    <span :class="selectedRating === rating ? 'font-medium' : ''">
                      {{ rating }}
                    </span>
                                </DropdownMenuItem>
                            </DropdownMenuSubContent>
                        </DropdownMenuSub>

                        <!-- Status Filter -->
                        <DropdownMenuSub>
                            <DropdownMenuSubTrigger>
                                <span>Status</span>
                                <span class="ml-auto text-xs text-muted-foreground">
                    {{ selectedStatus === 'all' ? 'All' : selectedStatus }}
                  </span>
                            </DropdownMenuSubTrigger>
                            <DropdownMenuSubContent>
                                <DropdownMenuItem @click="updateStatusFilter('all')">
                    <span :class="selectedStatus === 'all' ? 'font-medium' : ''">
                      All statuses
                    </span>
                                </DropdownMenuItem>
                                <DropdownMenuSeparator />
                                <DropdownMenuItem
                                    v-for="status in uniqueStatuses"
                                    :key="status"
                                    @click="updateStatusFilter(status)"
                                >
                    <span :class="selectedStatus === status ? 'font-medium' : ''">
                      {{ status }}
                    </span>
                                </DropdownMenuItem>
                            </DropdownMenuSubContent>
                        </DropdownMenuSub>

                        <!-- Rows per page -->
                        <DropdownMenuSub>
                            <DropdownMenuSubTrigger>
                                <span>Rows per page</span>
                                <span class="ml-auto text-xs text-muted-foreground">
                    {{ table.getState().pagination.pageSize }}
                  </span>
                            </DropdownMenuSubTrigger>
                            <DropdownMenuSubContent>
                                <DropdownMenuItem @click="table.setPageSize(10)">
                    <span :class="table.getState().pagination.pageSize === 10 ? 'font-medium' : ''">
                      10
                    </span>
                                </DropdownMenuItem>
                                <DropdownMenuItem @click="table.setPageSize(25)">
                    <span :class="table.getState().pagination.pageSize === 25 ? 'font-medium' : ''">
                      25
                    </span>
                                </DropdownMenuItem>
                                <DropdownMenuItem @click="table.setPageSize(50)">
                    <span :class="table.getState().pagination.pageSize === 50 ? 'font-medium' : ''">
                      50
                    </span>
                                </DropdownMenuItem>
                                <DropdownMenuItem @click="table.setPageSize(100)">
                    <span :class="table.getState().pagination.pageSize === 100 ? 'font-medium' : ''">
                      100
                    </span>
                                </DropdownMenuItem>
                            </DropdownMenuSubContent>
                        </DropdownMenuSub>

                        <DropdownMenuSeparator />

                        <!-- Clear All Filters -->
                        <DropdownMenuItem @click="clearAllFilters">
                            <span class="text-red-600 dark:text-red-400">Clear all filters</span>
                        </DropdownMenuItem>
                    </DropdownMenuContent>
                </DropdownMenu>
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
