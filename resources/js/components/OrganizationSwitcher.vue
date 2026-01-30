<script setup lang="ts">
import { computed, ref } from 'vue';
import { Check, ChevronsUpDown, PlusSquareIcon } from 'lucide-vue-next';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { SidebarMenuButton } from '@/components/ui/sidebar';
import CreateOrganizationDialog from '@/components/CreateOrganizationDialog.vue';
import { router, usePage } from '@inertiajs/vue3';
import { switchMethod } from '@/actions/App/Http/Controllers/OrganizationController';

const showCreateDialog = ref(false);
const page = usePage();
const organizations = computed(() => page.props.auth.organizations);
const currentOrganizationId = computed(() => page.props.auth.user?.current_organization_id);
const currentOrganization = computed(() =>
    organizations.value.find((org: { id: number }) => org.id === currentOrganizationId.value),
);
const isAdmin = computed(() => page.props.auth.user?.is_admin);

function switchOrganization(organizationId: number) {
    router.visit(switchMethod(organizationId).url, { method: 'put', preserveScroll: true });
}
</script>

<template>
    <DropdownMenu>
        <DropdownMenuTrigger as-child>
            <SidebarMenuButton
                size="lg"
                class="data-[state=open]:bg-sidebar-accent data-[state=open]:text-sidebar-accent-foreground"
            >
                <span class="font-medium truncate">{{ currentOrganization?.name ?? 'Select Organization' }}</span>
                <ChevronsUpDown class="ml-auto size-4" />
            </SidebarMenuButton>
        </DropdownMenuTrigger>
        <DropdownMenuContent
            class="w-(--reka-dropdown-menu-trigger-width)"
            align="start"
        >
            <DropdownMenuItem
                v-for="organization in organizations"
                :key="organization.id"
                @select="switchOrganization(organization.id)"
            >
                {{ organization.name }}
                <Check v-if="organization.id === currentOrganizationId" class="ml-auto" />
            </DropdownMenuItem>
            <DropdownMenuSeparator v-if="isAdmin" />
            <DropdownMenuItem v-if="isAdmin" @select="showCreateDialog = true">
                <PlusSquareIcon /> Add Organization
            </DropdownMenuItem>
        </DropdownMenuContent>
    </DropdownMenu>

    <CreateOrganizationDialog v-if="isAdmin" v-model:open="showCreateDialog" />
</template>
