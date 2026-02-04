<script setup lang="ts">
import { Search, X } from 'lucide-vue-next';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Button } from '@/components/ui/button';
interface FilterOption {
    value: string;
    label: string;
}

interface Props {
    modelValue: {
        search: string;
        status: string;
        rating: string;
    };
    statuses: FilterOption[];
    ratings: FilterOption[];
    hasActiveFilters: boolean;
}

interface Emits {
    (e: 'update:modelValue', value: Props['modelValue']): void;
    (e: 'reset'): void;
}

const props = defineProps<Props>();
const emit = defineEmits<Emits>();

function updateFilter(key: keyof Props['modelValue'], value: string): void {
    emit('update:modelValue', {
        ...props.modelValue,
        [key]: value,
    });
}
</script>

<template>
    <div class="flex flex-wrap gap-4">
        <div class="relative min-w-64 flex-1">
            <Search
                class="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-muted-foreground"
            />
            <Input
                :model-value="modelValue.search"
                @update:model-value="updateFilter('search', $event)"
                type="text"
                placeholder="Search companies..."
                class="pl-9"
            />
        </div>
        <Select
            :model-value="modelValue.status || 'all'"
            @update:model-value="updateFilter('status', $event === 'all' ? '' : $event)"
        >
            <SelectTrigger class="w-48">
                <SelectValue placeholder="All Statuses" />
            </SelectTrigger>
            <SelectContent>
                <SelectItem value="all">All Statuses</SelectItem>
                <SelectItem
                    v-for="status in statuses"
                    :key="status.value"
                    :value="status.value"
                    >
                    {{ status.label }}
                </SelectItem>
            </SelectContent>
        </Select>
        <Select
            :model-value="modelValue.rating || 'all'"
            @update:model-value="updateFilter('rating', $event === 'all' ? '' : $event)"
        >
            <SelectTrigger class="w-48">
                <SelectValue placeholder="All Ratings" />
            </SelectTrigger>
            <SelectContent>
                <SelectItem value="all">All Ratings</SelectItem>
                <SelectItem
                    v-for="rating in ratings"
                    :key="rating.value"
                    :value="rating.value"
                >
                    {{ rating.label }}
                </SelectItem>
            </SelectContent>
        </Select>

        <Button
            v-if="hasActiveFilters"
            @click="emit('reset')"
            variant="outline"
            type="button"
        >
            <X class="mr-2 h-4 w-4" />
            Reset
        </Button>
    </div>
</template>
