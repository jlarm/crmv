<script setup lang="ts">
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Skeleton } from '@/components/ui/skeleton';
</script>

<template>
    <div class="rounded-md border">
        <Table>
            <TableHeader>
                <TableRow>
                    <TableHead>Name</TableHead>
                    <TableHead>City</TableHead>
                    <TableHead>Status</TableHead>
                </TableRow>
            </TableHeader>
            <TableBody>
                <TableRow v-for="i in 5" :key="i">
                    <TableCell>
                        <Skeleton class="h-4 w-48" />
                        <Skeleton class="h-4 w-32" />
                        <Skeleton class="h-5 w-16 rounded-full" />
                    </TableCell>
                </TableRow>
            </TableBody>
        </Table>
    </div>
</template>

<style scoped></style>
