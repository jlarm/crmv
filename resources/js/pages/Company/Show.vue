<script setup lang="ts">
import { Head, Form, router, usePage } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import {
    MoreVertical,
    Trash2,
    Save,
    Minus,
    Star,
    Phone,
    Mail,
    Linkedin,
} from 'lucide-vue-next';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { show, update } from '@/routes/company';
import { Field, FieldGroup, FieldLabel } from '@/components/ui/field';
import { Textarea } from '@/components/ui/textarea';
import type { BreadcrumbItem } from '@/types';
import { Separator } from '@/components/ui/separator';
import InputError from '@/components/InputError.vue';
import { computed, ref, watch } from 'vue';
import { Badge } from '@/components/ui/badge';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    Item,
    ItemActions,
    ItemContent,
    ItemGroup,
    ItemSeparator,
    ItemTitle,
} from '@/components/ui/item';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';

interface Company {
    id: number;
    name: string;
    address: string;
    city: string;
    state: string;
    zipCode: string;
    phone: string;
    notes: string;
    currentSolutionName: string;
    currentSolutionUse: string;
    status: string;
    rating: string;
    stores: Store[];
    contacts: Contact[];
    users: {
        data: User[];
    };
}

interface Store {
    id: number;
    name: string;
    address: string;
    city: string;
    state: string;
    zipCode: string;
    phone: string;
    currentSolutionName: string;
    currentSolutionUse: string;
    notes: string;
}

interface Contact {
    id: number;
    name: string;
    email: string | null;
    phone: string | null;
    position: string | null;
    linkedinLink: string | null;
    primaryContact: boolean;
}

interface User {
    id: number;
    name: string;
}

interface Props {
    company: Company;
    allUsers: User[];
}

const props = defineProps<Props>();

const page = usePage();

const company = computed(() => {
    return (
        props.company ??
        ((page.props.value?.company as Company | undefined) ?? null)
    );
});

const consultantSearch = ref('');
const filteredConsultants = computed(() => {
    const search = consultantSearch.value.trim().toLowerCase();
    const available = props.allUsers.filter(
        (user) => !selectedConsultantIds.value.includes(user.id),
    );

    if (!search) {
        return available;
    }

    return available.filter((user) =>
        user.name.toLowerCase().includes(search),
    );
});

const selectedConsultantIds = ref<number[]>([]);
const initialConsultantIds = ref<number[]>([]);
const lastCompanyId = ref<number | null>(null);
const selectedConsultants = computed(() => {
    return props.allUsers.filter((user) =>
        selectedConsultantIds.value.includes(user.id),
    );
});

function syncConsultantsFromCompany(): void {
    const ids = (company.value?.users?.data ?? []).map((user) => user.id);
    selectedConsultantIds.value = ids;
    initialConsultantIds.value = ids;
}

watch(
    () => company.value?.id ?? null,
    (id) => {
        if (!id) {
            return;
        }

        if (lastCompanyId.value !== id) {
            lastCompanyId.value = id;
            syncConsultantsFromCompany();
        }
    },
    { immediate: true },
);

watch(
    () => (company.value?.users?.data ?? []).map((user) => user.id).join(','),
    () => {
        if (!consultantsDirty.value) {
            syncConsultantsFromCompany();
        }
    },
);

const consultantsDirty = computed(() => {
    const current = [...selectedConsultantIds.value].sort();
    const initial = [...initialConsultantIds.value].sort();
    if (current.length !== initial.length) {
        return true;
    }

    return current.some((id, index) => id !== initial[index]);
});

const breadcrumbItems = computed<BreadcrumbItem[]>(() => {
    if (!company.value?.id) {
        return [];
    }

    return [
        {
            title: company.value.name,
            href: show(company.value.id).url,
        },
    ];
});

const activeTab = ref<'details' | 'stores' | 'contacts'>('details');
const isStoreCreateOpen = ref(false);
const isStoreEditOpen = ref(false);
const editingStore = ref<Store | null>(null);
const isContactCreateOpen = ref(false);
const isContactEditOpen = ref(false);
const editingContact = ref<Contact | null>(null);

function handleStoreCreateSuccess(): void {
    isStoreCreateOpen.value = false;
}

function handleContactCreateSuccess(): void {
    isContactCreateOpen.value = false;
}

function openStoreEdit(store: Store): void {
    editingStore.value = store;
    isStoreEditOpen.value = true;
}

function openContactEdit(contact: Contact): void {
    editingContact.value = contact;
    isContactEditOpen.value = true;
}

function deleteStore(store: Store): void {
    if (!company.value?.id) {
        return;
    }

    if (!window.confirm('Delete this store?')) {
        return;
    }

    router.delete(`/companies/${company.value.id}/stores/${store.id}`);
}

function deleteContact(contact: Contact): void {
    if (!company.value?.id) {
        return;
    }

    if (!window.confirm('Delete this contact?')) {
        return;
    }

    router.delete(`/companies/${company.value.id}/contacts/${contact.id}`);
}

function toggleConsultant(userId: number, checked: boolean | 'indeterminate'): void {
    if (checked === true) {
        if (!selectedConsultantIds.value.includes(userId)) {
            selectedConsultantIds.value = [...selectedConsultantIds.value, userId];
        }
        return;
    }

    selectedConsultantIds.value = selectedConsultantIds.value.filter(
        (id) => id !== userId,
    );
}

// Success toasts are handled globally in AppLayout.
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <div v-if="!company?.id" class="px-8 py-6 text-sm text-slate-500">
            Loading company...
        </div>
        <Head :title="company.name" />
        <div class="px-8 py-3">
            <div class="flex shrink-0 items-center justify-between gap-4">
                <div class="flex flex-col">
                    <h1
                        class="text-2xl font-black text-slate-900 dark:text-slate-100"
                    >
                        {{ company.name }}
                    </h1>
                    <div class="mt-1 flex items-center gap-1">
                        <p class="text-xs text-zinc-400 dark:text-zinc-500">
                            ID: {{ company.id }}
                        </p>
                        <Badge variant="secondary" class="ml-2">{{
                            company.status
                        }}</Badge>
                        <Badge variant="secondary" class="ml-2">{{
                            company.rating
                        }}</Badge>
                    </div>
                </div>
            </div>

            <div class="mt-6 border-b border-slate-200 dark:border-slate-800">
                <nav class="flex gap-8 text-sm font-medium">
                    <button
                        type="button"
                        class="pb-3 transition-colors"
                        :class="
                            activeTab === 'details'
                                ? 'text-orange-600 border-b-2 border-orange-500'
                                : 'text-slate-500 hover:text-slate-700 dark:hover:text-slate-200'
                        "
                        @click="activeTab = 'details'"
                    >
                        Info
                    </button>
                    <button
                        type="button"
                        class="pb-3 transition-colors"
                        :class="
                            activeTab === 'stores'
                                ? 'text-orange-600 border-b-2 border-orange-500'
                                : 'text-slate-500 hover:text-slate-700 dark:hover:text-slate-200'
                        "
                        @click="activeTab = 'stores'"
                    >
                        Stores
                    </button>
                    <button
                        type="button"
                        class="pb-3 transition-colors"
                        :class="
                            activeTab === 'contacts'
                                ? 'text-orange-600 border-b-2 border-orange-500'
                                : 'text-slate-500 hover:text-slate-700 dark:hover:text-slate-200'
                        "
                        @click="activeTab = 'contacts'"
                    >
                        Contacts
                    </button>
                </nav>
            </div>

            <div class="mx-auto mt-5 w-full" v-if="activeTab === 'details'">
                <Form
                    v-if="company?.id"
                    :action="update(company.id).url"
                    method="put"
                    class="grid grid-cols-1 gap-5 md:grid-cols-3"
                    set-defaults-on-success
                    v-slot="{ errors, processing, isDirty }"
                >
                    <Card class="col-span-2">
                        <div class="space-y-5 px-5">
                            <FieldGroup>
                                <div class="grid grid-cols-6 gap-5">
                                    <Field class="col-span-full">
                                        <FieldLabel for="name"
                                            >Company Name</FieldLabel
                                        >
                                        <Input
                                            id="name"
                                            name="name"
                                            :default-value="company.name"
                                            required
                                            placeholder="Name"
                                        />
                                        <InputError :message="errors.name" />
                                    </Field>

                                    <Field class="col-span-full">
                                        <FieldLabel for="address"
                                            >Address</FieldLabel
                                        >
                                        <Input
                                            id="address"
                                            name="address"
                                            :default-value="company.address"
                                            required
                                            placeholder="Address"
                                        />
                                        <InputError :message="errors.address" />
                                    </Field>

                                    <Field class="col-span-2">
                                        <FieldLabel for="city">City</FieldLabel>
                                        <Input
                                            id="city"
                                            name="city"
                                            :default-value="company.city"
                                            required
                                            placeholder="City"
                                        />
                                        <InputError :message="errors.city" />
                                    </Field>

                                    <Field class="col-span-2">
                                        <FieldLabel for="state"
                                            >State</FieldLabel
                                        >
                                        <Input
                                            id="state"
                                            name="state"
                                            :default-value="company.state"
                                            required
                                            placeholder="State"
                                        />
                                        <InputError :message="errors.state" />
                                    </Field>

                                    <Field class="col-span-2">
                                        <FieldLabel for="zip_code"
                                            >Zip Code</FieldLabel
                                        >
                                        <Input
                                            id="zip_code"
                                            name="zip_code"
                                            :default-value="company.zipCode"
                                            required
                                            placeholder="Zip Code"
                                        />
                                        <InputError :message="errors.zip_code" />
                                    </Field>

                                    <Field class="col-span-full">
                                        <FieldLabel for="phone"
                                            >Phone Number</FieldLabel
                                        >
                                        <Input
                                            id="phone"
                                            name="phone"
                                            :default-value="company.phone"
                                            placeholder="999-999-9999"
                                        />
                                        <InputError :message="errors.phone" />
                                    </Field>

                                    <Separator class="col-span-full my-5" />

                                    <Field class="col-span-3">
                                        <FieldLabel for="current_solution_name"
                                            >Current Solution Name</FieldLabel
                                        >
                                        <Input
                                            id="current_solution_name"
                                            name="current_solution_name"
                                            :default-value="company.currentSolutionName"
                                            placeholder="Solution Name"
                                        />
                                    </Field>

                                    <Field class="col-span-3">
                                        <FieldLabel for="current_solution_use"
                                            >Current Solution Use</FieldLabel
                                        >
                                        <Input
                                            id="current_solution_use"
                                            name="current_solution_use"
                                            :default-value="company.currentSolutionUse"
                                            placeholder="Solution Use"
                                        />
                                    </Field>

                                    <Field class="col-span-full">
                                        <FieldLabel for="notes">Notes</FieldLabel>
                                        <Textarea
                                            id="notes"
                                            name="notes"
                                            :default-value="company.notes"
                                            placeholder="Notes"
                                        />
                                    </Field>

                                </div>
                            </FieldGroup>
                        </div>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>Consultants</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div class="space-y-4">
                                <input
                                    v-for="id in selectedConsultantIds"
                                    :key="id"
                                    type="hidden"
                                    name="user_ids[]"
                                    :value="id"
                                />
                                <div class="space-y-3">
                                    <DropdownMenu>
                                        <DropdownMenuTrigger as-child>
                                            <Button
                                                variant="outline"
                                                class="w-full justify-between"
                                            >
                                                <span>
                                                    Select consultants
                                                </span>
                                                <span class="text-xs text-slate-500">
                                                    {{ selectedConsultantIds.length }}
                                                </span>
                                            </Button>
                                        </DropdownMenuTrigger>
                                        <DropdownMenuContent class="w-72">
                                            <div class="p-2">
                                                <Input
                                                    v-model="consultantSearch"
                                                    placeholder="Search consultants..."
                                                />
                                            </div>
                                            <div class="max-h-60 overflow-auto py-1">
                                                <DropdownMenuItem
                                                    v-for="user in filteredConsultants"
                                                    :key="user.id"
                                                    @select.prevent="toggleConsultant(user.id, true)"
                                                    @click="toggleConsultant(user.id, true)"
                                                >
                                                    {{ user.name }}
                                                </DropdownMenuItem>
                                                <div
                                                    v-if="filteredConsultants.length === 0"
                                                    class="px-2 py-2 text-xs text-slate-500"
                                                >
                                                    No consultants match that search.
                                                </div>
                                            </div>
                                        </DropdownMenuContent>
                                    </DropdownMenu>
                                    <div class="text-xs text-slate-500">
                                        Selected consultants
                                    </div>
                                    <div class="flex flex-wrap gap-2">
                                        <span
                                            v-for="user in selectedConsultants"
                                            :key="user.id"
                                            class="inline-flex items-center gap-2 rounded-full bg-slate-100 px-3 py-1 text-xs text-slate-700"
                                        >
                                            {{ user.name }}
                                            <button
                                                type="button"
                                                class="text-slate-500 hover:text-slate-700"
                                                aria-label="Remove consultant"
                                                @click="toggleConsultant(user.id, false)"
                                            >
                                                ×
                                            </button>
                                        </span>
                                        <span
                                            v-if="selectedConsultants.length === 0"
                                            class="text-xs text-slate-500"
                                        >
                                            None selected.
                                        </span>
                                    </div>
                                </div>
                                <Separator class="my-4" />
                                <div class="grid grid-cols-2 gap-4">
                                    <Field class="col-span-2">
                                        <FieldLabel for="status">Status</FieldLabel>
                                        <Select name="status" :default-value="company.status">
                                            <SelectTrigger>
                                                <SelectValue
                                                    :placeholder="company.status"
                                                />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value="active"
                                                    >Active</SelectItem
                                                >
                                                <SelectItem value="inactive"
                                                    >Inactive</SelectItem
                                                >
                                            </SelectContent>
                                        </Select>
                                    </Field>

                                    <Field class="col-span-2">
                                        <FieldLabel for="rating">Rating</FieldLabel>
                                        <Select name="rating" :default-value="company.rating">
                                            <SelectTrigger>
                                                <SelectValue
                                                    :placeholder="company.rating"
                                                />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value="hot">Hot</SelectItem>
                                                <SelectItem value="warm">Warm</SelectItem>
                                                <SelectItem value="cold">Cold</SelectItem>
                                            </SelectContent>
                                        </Select>
                                    </Field>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <div class="col-span-full flex justify-end gap-3">
                        <Button type="button" variant="destructive" :disabled="processing">
                            <Trash2 />
                            Delete
                        </Button>
                        <Button type="submit" :disabled="processing || (!isDirty && !consultantsDirty)">
                            <Save />
                            Update
                        </Button>
                    </div>
                </Form>
            </div>

            <div class="mx-auto mt-6 w-full" v-if="activeTab === 'stores'">
                <div class="flex items-center justify-between">
                    <h2
                        class="text-lg font-semibold text-slate-900 dark:text-slate-100"
                    >
                        Stores
                    </h2>
                    <Dialog v-model:open="isStoreCreateOpen">
                        <DialogTrigger as-child>
                            <Button>Create Store</Button>
                        </DialogTrigger>
                        <DialogContent class="sm:max-w-2xl">
                            <DialogHeader>
                                <DialogTitle>Create Store</DialogTitle>
                                <DialogDescription>
                                    Add a new store for this company.
                                </DialogDescription>
                            </DialogHeader>
                            <Form
                                :action="`/companies/${company.id}/stores`"
                                method="post"
                                class="grid grid-cols-2 gap-4"
                                reset-on-success
                                :on-success="handleStoreCreateSuccess"
                                v-slot="{ errors, processing }"
                            >
                                <Field class="col-span-2">
                                    <FieldLabel for="store_name">Name</FieldLabel>
                                    <Input id="store_name" name="name" required />
                                    <InputError :message="errors.name" />
                                </Field>
                                <Field class="col-span-2">
                                    <FieldLabel for="store_address"
                                        >Address</FieldLabel
                                    >
                                    <Input id="store_address" name="address" />
                                </Field>
                                <Field>
                                    <FieldLabel for="store_city">City</FieldLabel>
                                    <Input id="store_city" name="city" />
                                </Field>
                                <Field>
                                    <FieldLabel for="store_state">State</FieldLabel>
                                    <Input id="store_state" name="state" />
                                </Field>
                                <Field>
                                    <FieldLabel for="store_zip">Zip</FieldLabel>
                                    <Input id="store_zip" name="zip_code" />
                                </Field>
                                <Field>
                                    <FieldLabel for="store_phone">Phone</FieldLabel>
                                    <Input id="store_phone" name="phone" />
                                </Field>
                                <Field class="col-span-2">
                                    <FieldLabel for="store_solution_name"
                                        >Current Solution Name</FieldLabel
                                    >
                                    <Input
                                        id="store_solution_name"
                                        name="current_solution_name"
                                    />
                                </Field>
                                <Field class="col-span-2">
                                    <FieldLabel for="store_solution_use"
                                        >Current Solution Use</FieldLabel
                                    >
                                    <Input
                                        id="store_solution_use"
                                        name="current_solution_use"
                                    />
                                </Field>
                                <Field class="col-span-2">
                                    <FieldLabel for="store_notes">Notes</FieldLabel>
                                    <Textarea id="store_notes" name="notes" />
                                </Field>
                                <DialogFooter class="col-span-2">
                                    <Button :disabled="processing">Create</Button>
                                </DialogFooter>
                            </Form>
                        </DialogContent>
                    </Dialog>
                </div>
                <div class="mt-6 grid gap-3 md:grid-cols-2 xl:grid-cols-3">
                    <Card
                        v-for="store in company.stores"
                        :key="store.id"
                        class="rounded-2xl border border-slate-200 shadow-sm transition hover:shadow-md dark:border-slate-800"
                    >
                        <CardHeader>
                            <div class="space-y-1">
                                <div class="flex w-full items-center justify-between gap-4">
                                    <CardTitle class="text-base font-semibold text-slate-900 dark:text-slate-100">
                                        {{ store.name }}
                                    </CardTitle>
                                    <DropdownMenu>
                                        <DropdownMenuTrigger as-child>
                                            <Button
                                                variant="ghost"
                                                size="icon"
                                                class="text-slate-500 hover:text-slate-700"
                                                aria-label="Store actions"
                                            >
                                                <MoreVertical class="h-4 w-4" />
                                            </Button>
                                        </DropdownMenuTrigger>
                                        <DropdownMenuContent align="end">
                                            <DropdownMenuItem @click="openStoreEdit(store)">
                                                Edit
                                            </DropdownMenuItem>
                                            <DropdownMenuItem
                                                class="text-red-600 focus:text-red-600"
                                                @click="deleteStore(store)"
                                            >
                                                Delete
                                            </DropdownMenuItem>
                                        </DropdownMenuContent>
                                    </DropdownMenu>
                                </div>
                                <p class="text-xs text-slate-500">
                                    {{ store.address || '—' }}
                                </p>
                                <p class="text-xs text-slate-500">
                                    {{ store.city }}{{ store.state ? `, ${store.state}` : '' }}
                                    {{ store.zipCode || '' }}
                                </p>
                                <p class="text-xs text-slate-500">
                                    {{ store.phone || '—' }}
                                </p>
                            </div>
                        </CardHeader>
                    </Card>
                    <p
                        v-if="company.stores.length === 0"
                        class="text-xs text-muted-foreground"
                    >
                        No stores yet.
                    </p>
                </div>

                <Dialog v-model:open="isStoreEditOpen">
                    <DialogContent class="sm:max-w-2xl">
                        <DialogHeader>
                            <DialogTitle>Edit Store</DialogTitle>
                            <DialogDescription>
                                Update store details.
                            </DialogDescription>
                        </DialogHeader>
                        <Form
                            v-if="editingStore"
                            :action="`/companies/${company.id}/stores/${editingStore.id}`"
                            method="put"
                            class="grid grid-cols-2 gap-4"
                            v-slot="{ errors, processing }"
                        >
                            <Field class="col-span-2">
                                <FieldLabel for="store_edit_name">Name</FieldLabel>
                                <Input
                                    id="store_edit_name"
                                    name="name"
                                    :default-value="editingStore.name"
                                    required
                                />
                                <InputError :message="errors.name" />
                            </Field>
                            <Field class="col-span-2">
                                <FieldLabel for="store_edit_address"
                                    >Address</FieldLabel
                                >
                                <Input
                                    id="store_edit_address"
                                    name="address"
                                    :default-value="editingStore.address"
                                />
                            </Field>
                            <Field>
                                <FieldLabel for="store_edit_city">City</FieldLabel>
                                <Input
                                    id="store_edit_city"
                                    name="city"
                                    :default-value="editingStore.city"
                                />
                            </Field>
                            <Field>
                                <FieldLabel for="store_edit_state">State</FieldLabel>
                                <Input
                                    id="store_edit_state"
                                    name="state"
                                    :default-value="editingStore.state"
                                />
                            </Field>
                            <Field>
                                <FieldLabel for="store_edit_zip">Zip</FieldLabel>
                                <Input
                                    id="store_edit_zip"
                                    name="zip_code"
                                    :default-value="editingStore.zipCode"
                                />
                            </Field>
                            <Field>
                                <FieldLabel for="store_edit_phone">Phone</FieldLabel>
                                <Input
                                    id="store_edit_phone"
                                    name="phone"
                                    :default-value="editingStore.phone"
                                />
                            </Field>
                            <Field class="col-span-2">
                                <FieldLabel for="store_edit_solution_name"
                                    >Current Solution Name</FieldLabel
                                >
                                <Input
                                    id="store_edit_solution_name"
                                    name="current_solution_name"
                                    :default-value="editingStore.currentSolutionName"
                                />
                            </Field>
                            <Field class="col-span-2">
                                <FieldLabel for="store_edit_solution_use"
                                    >Current Solution Use</FieldLabel
                                >
                                <Input
                                    id="store_edit_solution_use"
                                    name="current_solution_use"
                                    :default-value="editingStore.currentSolutionUse"
                                />
                            </Field>
                            <Field class="col-span-2">
                                <FieldLabel for="store_edit_notes">Notes</FieldLabel>
                                <Textarea
                                    id="store_edit_notes"
                                    name="notes"
                                    :default-value="editingStore.notes"
                                />
                            </Field>
                            <DialogFooter class="col-span-2">
                                <Button :disabled="processing">Save</Button>
                            </DialogFooter>
                        </Form>
                    </DialogContent>
                </Dialog>
            </div>

            <div class="mx-auto mt-6 w-full" v-if="activeTab === 'contacts'">
                <div class="flex items-center justify-between">
                    <h2
                        class="text-lg font-semibold text-slate-900 dark:text-slate-100"
                    >
                        Contacts
                    </h2>
                    <Dialog v-model:open="isContactCreateOpen">
                        <DialogTrigger as-child>
                            <Button>Create Contact</Button>
                        </DialogTrigger>
                        <DialogContent class="sm:max-w-xl">
                            <DialogHeader>
                                <DialogTitle>Create Contact</DialogTitle>
                                <DialogDescription>
                                    Add a new contact for this company.
                                </DialogDescription>
                            </DialogHeader>
                            <Form
                                :action="`/companies/${company.id}/contacts`"
                                method="post"
                                class="grid grid-cols-2 gap-4"
                                reset-on-success
                                :on-success="handleContactCreateSuccess"
                                v-slot="{ errors, processing }"
                            >
                                <Field class="col-span-2">
                                    <FieldLabel for="contact_name">Name</FieldLabel>
                                    <Input id="contact_name" name="name" required />
                                    <InputError :message="errors.name" />
                                </Field>
                                <Field>
                                    <FieldLabel for="contact_email">Email</FieldLabel>
                                    <Input id="contact_email" name="email" />
                                </Field>
                                <Field>
                                    <FieldLabel for="contact_phone">Phone</FieldLabel>
                                    <Input id="contact_phone" name="phone" />
                                </Field>
                                <Field class="col-span-2">
                                    <FieldLabel for="contact_position"
                                        >Position</FieldLabel
                                    >
                                    <Input id="contact_position" name="position" />
                                </Field>
                                <Field class="col-span-2">
                                    <FieldLabel for="contact_linkedin"
                                        >LinkedIn</FieldLabel
                                    >
                                    <Input id="contact_linkedin" name="linkedin_link" />
                                </Field>
                                <Field class="col-span-2">
                                    <label class="flex items-center gap-2 text-sm">
                                        <input
                                            type="checkbox"
                                            name="primary_contact"
                                            value="1"
                                        />
                                        Primary contact
                                    </label>
                                </Field>
                                <DialogFooter class="col-span-2">
                                    <Button :disabled="processing">Create</Button>
                                </DialogFooter>
                            </Form>
                        </DialogContent>
                    </Dialog>
                </div>
                <div class="mt-6 grid gap-6 md:grid-cols-2 xl:grid-cols-3">
                    <Card
                        v-for="contact in company.contacts"
                        :key="contact.id"
                        class="rounded-2xl gap-2 text-xs border border-slate-200 shadow-sm transition hover:shadow-md dark:border-slate-800"
                    >
                        <CardHeader class="text-xs">
                            <div class="flex w-full items-center justify-between gap-4">
                                <div class="flex items-center gap-3">
                                    <CardTitle class="text-base font-semibold text-slate-900 dark:text-slate-100">
                                        {{ contact.name }}
                                    </CardTitle>
                                    <Star
                                        v-if="contact.primaryContact"
                                        class="h-3 w-3 text-amber-500"
                                        fill="currentColor"
                                    />
                                </div>
                                <DropdownMenu>
                                    <DropdownMenuTrigger as-child>
                                        <Button
                                            variant="ghost"
                                            size="icon"
                                            class="text-slate-500 hover:text-slate-700"
                                            aria-label="Contact actions"
                                        >
                                            <MoreVertical class="h-4 w-4" />
                                        </Button>
                                    </DropdownMenuTrigger>
                                    <DropdownMenuContent align="end">
                                        <DropdownMenuItem @click="openContactEdit(contact)">
                                            Edit
                                        </DropdownMenuItem>
                                        <DropdownMenuItem
                                            class="text-red-600 focus:text-red-600"
                                            @click="deleteContact(contact)"
                                        >
                                            Delete
                                        </DropdownMenuItem>
                                    </DropdownMenuContent>
                                </DropdownMenu>
                            </div>
                        </CardHeader>
                        <CardContent class="space-y-3 text-xs text-slate-600">
                            <span
                                v-if="contact.position"
                                class="inline-flex w-fit items-center rounded-xl bg-blue-100 px-3 py-1 text-xs font-medium text-blue-700"
                            >
                                {{ contact.position }}
                            </span>
                            <div class="space-y-2 text-xs text-slate-600">
                                <div v-if="contact.phone" class="flex items-center gap-3">
                                    <Phone class="h-3 w-3 text-slate-500" />
                                    <span>{{ contact.phone }}</span>
                                </div>
                                <div v-if="contact.email" class="flex items-center gap-3">
                                    <Mail class="h-3 w-3 text-slate-500" />
                                    <span>{{ contact.email }}</span>
                                </div>
                                <div v-if="contact.linkedinLink" class="flex items-center gap-3">
                                    <Linkedin class="h-5 w-5 text-slate-500" />
                                    <span>LinkedIn</span>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                    <p
                        v-if="company.contacts.length === 0"
                        class="text-xs text-muted-foreground"
                    >
                        No contacts yet.
                    </p>
                </div>

                <Dialog v-model:open="isContactEditOpen">
                    <DialogContent class="sm:max-w-xl">
                        <DialogHeader>
                            <DialogTitle>Edit Contact</DialogTitle>
                            <DialogDescription>
                                Update contact details.
                            </DialogDescription>
                        </DialogHeader>
                        <Form
                            v-if="editingContact"
                            :action="`/companies/${company.id}/contacts/${editingContact.id}`"
                            method="put"
                            class="grid grid-cols-2 gap-4"
                            v-slot="{ errors, processing }"
                        >
                            <Field class="col-span-2">
                                <FieldLabel for="contact_edit_name">Name</FieldLabel>
                                <Input
                                    id="contact_edit_name"
                                    name="name"
                                    :default-value="editingContact.name"
                                    required
                                />
                                <InputError :message="errors.name" />
                            </Field>
                            <Field>
                                <FieldLabel for="contact_edit_email">Email</FieldLabel>
                                <Input
                                    id="contact_edit_email"
                                    name="email"
                                    :default-value="editingContact.email || ''"
                                />
                            </Field>
                            <Field>
                                <FieldLabel for="contact_edit_phone">Phone</FieldLabel>
                                <Input
                                    id="contact_edit_phone"
                                    name="phone"
                                    :default-value="editingContact.phone || ''"
                                />
                            </Field>
                            <Field class="col-span-2">
                                <FieldLabel for="contact_edit_position"
                                    >Position</FieldLabel
                                >
                                <Input
                                    id="contact_edit_position"
                                    name="position"
                                    :default-value="editingContact.position || ''"
                                />
                            </Field>
                            <Field class="col-span-2">
                                <FieldLabel for="contact_edit_linkedin"
                                    >LinkedIn</FieldLabel
                                >
                                <Input
                                    id="contact_edit_linkedin"
                                    name="linkedin_link"
                                    :default-value="editingContact.linkedinLink || ''"
                                />
                            </Field>
                            <Field class="col-span-2">
                                <label class="flex items-center gap-2 text-sm">
                                    <input
                                        type="checkbox"
                                        name="primary_contact"
                                        value="1"
                                        :checked="editingContact.primaryContact"
                                    />
                                    Primary contact
                                </label>
                            </Field>
                            <DialogFooter class="col-span-2">
                                <Button :disabled="processing">Save</Button>
                            </DialogFooter>
                        </Form>
                    </DialogContent>
                </Dialog>
            </div>
        </div>
    </AppLayout>
</template>
