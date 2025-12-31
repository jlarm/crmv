<script setup lang="ts" generic="TData, TValue">
import { computed } from 'vue';
import type { ColumnDef, SortingState } from '@tanstack/vue-table';
import {
    FlexRender,
    getCoreRowModel,
    useVueTable,
    getSortedRowModel,
} from '@tanstack/vue-table';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';

const props = defineProps<{
    columns: ColumnDef<TData, TValue>[];
    data: TData[];
    sorting?: { column: string; direction: 'asc' | 'desc' };
}>();

const sorting = computed<SortingState>(() => {
    if (!props.sorting?.column) return [];
    return [
        {
            id: props.sorting.column,
            desc: props.sorting.direction === 'desc',
        },
    ];
});

const table = useVueTable({
    get data() {
        return props.data;
    },
    get columns() {
        return props.columns;
    },
    getCoreRowModel: getCoreRowModel(),
    getSortedRowModel: getSortedRowModel(),
    manualSorting: true,
    state: {
        get sorting() {
            return sorting.value;
        },
    },
    columnResizeMode: 'onChange',
});
</script>

<template>
    <div class="rounded-md border">
        <Table>
            <TableHeader>
                <TableRow
                    v-for="headerGroup in table.getHeaderGroups()"
                    :key="headerGroup.id"
                >
                    <TableHead
                        v-for="header in headerGroup.headers"
                        :key="header.id"
                        :style="{ width: `${header.getSize()}px` }"
                    >
                        <FlexRender
                            v-if="!header.isPlaceholder"
                            :render="header.column.columnDef.header"
                            :props="header.getContext()"
                        />
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
                        <TableCell
                            v-for="cell in row.getVisibleCells()"
                            :key="cell.id"
                            :style="{ width: `${cell.column.getSize()}px` }"
                        >
                            <FlexRender
                                :render="cell.column.columnDef.cell"
                                :props="cell.getContext()"
                            />
                        </TableCell>
                    </TableRow>
                </template>
                <TableRow v-else>
                    <TableCell :colspan="columns.length" class="h-24 text-center">
                        <div class="text-muted-foreground">
                            No dealerships found.
                        </div>
                    </TableCell>
                </TableRow>
            </TableBody>
        </Table>
    </div>
</template>
