import { h } from 'vue';
import type { ColumnDef } from '@tanstack/vue-table';
import { Link } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { ArrowUpDown } from 'lucide-vue-next';
import { show } from '@/routes/company'

export interface Company {
    id: number;
    name: string;
    city: string;
    state: string;
    status: string;
    statusLabel: string;
    statusVariant: 'default' | 'secondary';
    rating: string;
    ratingLabel: string;
    ratingVariant: 'outline' | 'destructive';
}

export function createColumns(onSort: (column: string) => void): ColumnDef<Company>[] {
    return [
        {
            accessorKey: 'name',
            size: 400,
            header: () => {
                return h(
                    Button,
                    {
                        variant: 'ghost',
                        class: 'h-auto p-0 hover:bg-transparent font-medium',
                        onClick: () => onSort('name'),
                    },
                    () => [
                        'Name',
                        h(ArrowUpDown, { class: 'ml-2 h-4 w-4 opacity-50' }),
                    ],
                );
            },
            cell: ({ row }) => {
                return h('div', { class: 'font-medium' }, row.getValue('name'));
            },
        },
        {
            accessorKey: 'city',
            size: 150,
            header: 'City',
            cell: ({ row }) => {
                return h('div', {}, row.getValue('city'));
            },
        },
        {
            accessorKey: 'state',
            size: 100,
            header: 'State',
            cell: ({ row }) => {
                return h('div', {}, row.getValue('state'));
            },
        },
        {
            accessorKey: 'status',
            size: 150,
            header: () => {
                return h(
                    Button,
                    {
                        variant: 'ghost',
                        class: 'h-auto p-0 hover:bg-transparent font-medium',
                        onClick: () => onSort('status'),
                    },
                    () => [
                        'Status',
                        h(ArrowUpDown, { class: 'ml-2 h-4 w-4 opacity-50' }),
                    ],
                );
            },
            cell: ({ row }) => {
                const company = row.original;
                return h(
                    Badge,
                    { variant: company.statusVariant },
                    () => company.statusLabel,
                );
            },
        },
        {
            accessorKey: 'rating',
            size: 150,
            header: () => {
                return h(
                    Button,
                    {
                        variant: 'ghost',
                        class: 'h-auto p-0 hover:bg-transparent font-medium',
                        onClick: () => onSort('rating'),
                    },
                    () => [
                        'Rating',
                        h(ArrowUpDown, { class: 'ml-2 h-4 w-4 opacity-50' }),
                    ],
                );
            },
            cell: ({ row }) => {
                const company = row.original;
                return h(
                    Badge,
                    { variant: company.ratingVariant },
                    () => company.ratingLabel,
                );
            },
        },
        {
            accessorKey: 'actions',
            size: 100,
            header: '',
            cell: ({ row }) => {
                const company = row.original;
                return h(
                    Link,
                    { href: show.url(company.id) },
                    () => h(
                        Button,
                        { variant: 'ghost', size: 'sm', as: 'span' },
                        () => 'View',
                    ),
                );
            },
        }
    ];
}
