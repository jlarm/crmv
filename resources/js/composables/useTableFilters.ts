import { router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

interface UseTableFiltersOptions {
    routeUrl: string;
    initialFilters?: Record<string, any>;
    debounceMs?: number;
    onlyProps?: string[];
}

export function useTableFilters(options: UseTableFiltersOptions) {
    const {
        routeUrl,
        initialFilters = {},
        debounceMs = 300,
        onlyProps = [],
    } = options;

    const defaultFilters = { ...initialFilters };
    const filters = ref({ ...defaultFilters });
    let debounceTimeout: number | null = null;

    watch(
        filters,
        () => {
            if (debounceTimeout) {
                clearTimeout(debounceTimeout);
            }

            debounceTimeout = setTimeout(() => {
                applyFilters();
            }, debounceMs);
        },
        { deep: true },
    );

    function applyFilters(): void {
        const params = Object.fromEntries(
            Object.entries(filters.value).filter(
                ([, value]) => value !== '' && value !== null,
            ),
        );

        router.get(routeUrl, params, {
            preserveState: true,
            preserveScroll: true,
            only: onlyProps.length > 0 ? onlyProps : undefined,
        });
    }

    function resetFilters(): void {
        filters.value = { ...defaultFilters };
    }

    function hasActiveFilters(excludeKeys: string[] = []): boolean {
        return Object.entries(filters.value)
            .filter(([key]) => !excludeKeys.includes(key))
            .some(([, value]) => value !== '' && value !== null);
    }

    return {
        filters,
        applyFilters,
        resetFilters,
        hasActiveFilters,
    };
}
