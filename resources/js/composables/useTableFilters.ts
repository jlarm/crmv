import { ref, watch } from 'vue'
import { router } from '@inertiajs/vue3'

interface UseTableFiltersOptions {
    routeUrl: string
    initialFilters?: Record<string, any>
    debounceMs?: number
    onlyProps?: string[]
}

export function useTableFilters(options: UseTableFiltersOptions) {
    const {
        routeUrl,
        initialFilters = {},
        debounceMs = 300,
        onlyProps = [],
    } = options

    const filters = ref({ ...initialFilters })
    let debounceTimeout: number | null = null

    watch(
        filters,
        () => {
            if (debounceTimeout) {
                clearTimeout(debounceTimeout)
            }

            debounceTimeout = setTimeout(() => {
                applyFilters()
            }, debounceMs)
        },
        { deep: true }
    )

    function applyFilters(): void {
        const params = Object.fromEntries(
            Object.entries(filters.value).filter(([_, value]) => value !== '' && value !== null)
        )

        router.get(routeUrl, params, {
            preserveState: true,
            preserveScroll: true,
            only: onlyProps.length > 0 ? onlyProps : undefined,
        })
    }

    function resetFilters(): void {
        Object.keys(filters.value).forEach(key => {
            filters.value[key] = ''
        })
    }

    function hasActiveFilters(): boolean {
        return Object.values(filters.value).some(value => value !== '' && value !== null)
    }

    return {
        filters,
        applyFilters,
        resetFilters,
        hasActiveFilters,
    }
}
