<script setup lang="ts">
import { computed, reactive, ref } from 'vue';
import { Head, Form, router } from '@inertiajs/vue3';
import { Trash2, Users as UsersIcon } from 'lucide-vue-next';
import AppLayout from '@/layouts/AppLayout.vue';
import HeadingSmall from '@/components/HeadingSmall.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Badge } from '@/components/ui/badge';
import { Checkbox } from '@/components/ui/checkbox';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { type BreadcrumbItem } from '@/types';

interface Organization {
    id: number;
    name: string;
}

interface ManagedUser {
    id: number;
    name: string;
    email: string;
    is_admin: boolean;
    current_organization_id: number | null;
    organizations: Organization[];
}

interface Props {
    users: ManagedUser[];
    organizations: Organization[];
}

const props = defineProps<Props>();

const isCreateOpen = ref(false);
const organizationSelections = reactive<Record<number, number[]>>(
    Object.fromEntries(
        props.users.map((user) => [
            user.id,
            user.organizations.map((organization) => organization.id),
        ]),
    ),
);
const isEditOpen = reactive<Record<number, boolean>>({});
const processingUserId = ref<number | null>(null);

const breadcrumbItems: BreadcrumbItem[] = [
    {
        title: 'Users',
        href: '/users',
    },
];

const sortedOrganizations = computed(() =>
    [...props.organizations].sort((a, b) => a.name.localeCompare(b.name)),
);

function toggleOrganization(userId: number, organizationId: number, checked: boolean | 'indeterminate'): void {
    if (!organizationSelections[userId]) {
        organizationSelections[userId] = [];
    }

    if (checked === true) {
        if (!organizationSelections[userId].includes(organizationId)) {
            organizationSelections[userId] = [...organizationSelections[userId], organizationId];
        }

        return;
    }

    organizationSelections[userId] = organizationSelections[userId].filter(
        (id) => id !== organizationId,
    );
}

function saveOrganizations(user: ManagedUser): void {
    const organizationIds = organizationSelections[user.id] ?? [];

    router.put(
        `/users/${user.id}/organizations`,
        { organization_ids: organizationIds },
        {
            preserveScroll: true,
            onStart: () => {
                processingUserId.value = user.id;
            },
            onFinish: () => {
                processingUserId.value = null;
            },
            onSuccess: () => {
                isEditOpen[user.id] = false;
            },
        },
    );
}

function deleteUser(user: ManagedUser): void {
    if (!window.confirm(`Delete ${user.name}?`)) {
        return;
    }

    router.delete(`/users/${user.id}`, {
        preserveScroll: true,
    });
}
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="User management" />
        <div class="space-y-6 p-6">
            <HeadingSmall
                title="User management"
                description="Create users, assign organizations, and remove accounts."
            />

            <Card>
                <CardHeader class="flex flex-row items-center justify-between">
                    <CardTitle class="flex items-center gap-2">
                        <UsersIcon class="h-4 w-4" />
                        Create user
                    </CardTitle>
                    <Dialog v-model:open="isCreateOpen">
                        <DialogTrigger as-child>
                            <Button>Create User</Button>
                        </DialogTrigger>
                        <DialogContent class="sm:max-w-lg">
                            <DialogHeader>
                                <DialogTitle>Create user</DialogTitle>
                                <DialogDescription>
                                    Add a user and assign at least one organization.
                                </DialogDescription>
                            </DialogHeader>
                            <Form
                                action="/users"
                                method="post"
                                class="space-y-4"
                                reset-on-success
                                :on-success="() => (isCreateOpen = false)"
                                v-slot="{ errors, processing }"
                            >
                                    <div class="grid gap-2">
                                        <Label for="create_name">Name</Label>
                                        <Input id="create_name" name="name" required />
                                        <InputError :message="errors.name" />
                                    </div>

                                    <div class="grid gap-2">
                                        <Label for="create_email">Email</Label>
                                        <Input id="create_email" name="email" type="email" required />
                                        <InputError :message="errors.email" />
                                    </div>

                                    <div class="grid gap-2">
                                        <Label for="create_password">Password</Label>
                                        <Input id="create_password" name="password" type="password" required />
                                        <InputError :message="errors.password" />
                                    </div>

                                    <div class="grid gap-2">
                                        <Label for="create_password_confirmation">Confirm password</Label>
                                        <Input
                                            id="create_password_confirmation"
                                            name="password_confirmation"
                                            type="password"
                                            required
                                        />
                                    </div>

                                    <div class="space-y-2">
                                        <Label>Organizations</Label>
                                        <div class="max-h-40 space-y-2 overflow-auto rounded-md border p-3">
                                            <label
                                                v-for="organization in sortedOrganizations"
                                                :key="organization.id"
                                                class="flex items-center gap-2 text-sm"
                                            >
                                                <input
                                                    type="checkbox"
                                                    name="organization_ids[]"
                                                    :value="organization.id"
                                                />
                                                {{ organization.name }}
                                            </label>
                                        </div>
                                        <InputError :message="errors.organization_ids" />
                                    </div>

                                    <label class="flex items-center gap-2 text-sm">
                                        <input type="checkbox" name="is_admin" value="1" />
                                        Admin user
                                    </label>

                                <DialogFooter>
                                    <Button :disabled="processing">Create</Button>
                                </DialogFooter>
                            </Form>
                        </DialogContent>
                    </Dialog>
                </CardHeader>
            </Card>

            <div class="space-y-3">
                <Card v-for="user in users" :key="user.id">
                        <CardHeader class="flex flex-row items-start justify-between gap-4">
                            <div class="space-y-2">
                                <div class="flex items-center gap-2">
                                    <CardTitle class="text-base">{{ user.name }}</CardTitle>
                                    <Badge v-if="user.is_admin" variant="secondary">Admin</Badge>
                                </div>
                                <p class="text-sm text-muted-foreground">{{ user.email }}</p>
                                <div class="flex flex-wrap gap-2">
                                    <Badge
                                        v-for="organization in user.organizations"
                                        :key="organization.id"
                                        variant="outline"
                                    >
                                        {{ organization.name }}
                                    </Badge>
                                    <span v-if="user.organizations.length === 0" class="text-xs text-muted-foreground">
                                        No organizations assigned
                                    </span>
                                </div>
                            </div>

                            <div class="flex items-center gap-2">
                                <Dialog v-model:open="isEditOpen[user.id]">
                                    <DialogTrigger as-child>
                                        <Button variant="outline" size="sm">Assign Organizations</Button>
                                    </DialogTrigger>
                                    <DialogContent class="sm:max-w-lg">
                                        <DialogHeader>
                                            <DialogTitle>Assign organizations</DialogTitle>
                                            <DialogDescription>
                                                Choose organizations for {{ user.name }}.
                                            </DialogDescription>
                                        </DialogHeader>
                                        <div class="space-y-2">
                                            <div class="max-h-56 space-y-2 overflow-auto rounded-md border p-3">
                                                <label
                                                    v-for="organization in sortedOrganizations"
                                                    :key="organization.id"
                                                    class="flex items-center gap-2 text-sm"
                                                >
                                                    <Checkbox
                                                        :model-value="(organizationSelections[user.id] ?? []).includes(organization.id)"
                                                        @update:model-value="toggleOrganization(user.id, organization.id, $event)"
                                                    />
                                                    {{ organization.name }}
                                                </label>
                                            </div>
                                            <p
                                                v-if="(organizationSelections[user.id] ?? []).length === 0"
                                                class="text-xs text-red-600"
                                            >
                                                Select at least one organization.
                                            </p>
                                        </div>
                                        <DialogFooter>
                                            <Button
                                                :disabled="processingUserId === user.id || (organizationSelections[user.id] ?? []).length === 0"
                                                @click="saveOrganizations(user)"
                                            >
                                                Save
                                            </Button>
                                        </DialogFooter>
                                    </DialogContent>
                                </Dialog>

                                <Button
                                    variant="destructive"
                                    size="sm"
                                    @click="deleteUser(user)"
                                >
                                    <Trash2 class="h-4 w-4" />
                                    Delete
                                </Button>
                            </div>
                        </CardHeader>
                        <CardContent />
                    </Card>

                <p v-if="users.length === 0" class="text-sm text-muted-foreground">
                    No users found.
                </p>
            </div>
        </div>
    </AppLayout>
</template>
