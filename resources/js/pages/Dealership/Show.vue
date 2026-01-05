<script setup lang="ts">
import { Head, Form, usePage } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Trash2, Save, Minus } from 'lucide-vue-next';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import DealershipController, {
    show,
} from '@/actions/App/Http/Controllers/DealershipController';
import { Field, FieldGroup, FieldLabel } from '@/components/ui/field';
import { Textarea } from '@/components/ui/textarea';
import type { BreadcrumbItem } from '@/types';
import { Separator } from '@/components/ui/separator';
import InputError from '@/components/InputError.vue';
import { watch } from 'vue';
import { toast } from 'vue-sonner';
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

interface Dealership {
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
    users: {
        data: User[];
    };
}

interface User {
    id: number;
    name: string;
}

interface Props {
    dealership: Dealership;
}

const props = defineProps<Props>();

const page = usePage();

const breadcrumbItems: BreadcrumbItem[] = [
    {
        title: props.dealership.name,
        href: show(props.dealership.id).url,
    },
];

watch(
    () => page.props.flash?.success,
    (message) => {
        if (message) {
            toast.success(message);
        }
    },
);
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head :title="dealership.name" />
        <Form
            v-bind="DealershipController.update.form(dealership.id)"
            class="px-8 py-3"
            set-defaults-on-success
            v-slot="{ errors, processing, isDirty }"
        >
            <div class="flex shrink-0 items-center justify-between gap-4">
                <div class="flex flex-col">
                    <h1
                        class="text-2xl font-black text-slate-900 dark:text-slate-100"
                    >
                        {{ dealership.name }}
                    </h1>
                    <div class="mt-1 flex items-center gap-1">
                        <p class="text-xs text-zinc-400 dark:text-zinc-500">
                            ID: {{ dealership.id }}
                        </p>
                        <Badge variant="secondary" class="ml-2">{{
                            dealership.status
                        }}</Badge>
                        <Badge variant="secondary" class="ml-2">{{
                            dealership.rating
                        }}</Badge>
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    <Button variant="destructive" :disabled="processing">
                        <Trash2 />
                        Delete
                    </Button>
                    <Button :disabled="processing || !isDirty">
                        <Save />
                        Update
                    </Button>
                </div>
            </div>

            <div class="mx-auto mt-5 w-full">
                <div class="grid grid-cols-1 gap-5 md:grid-cols-3">
                    <Card class="col-span-2">
                        <div class="space-y-5 px-5">
                            <FieldGroup>
                                <div class="grid grid-cols-6 gap-5">
                                    <Field class="col-span-full">
                                        <FieldLabel for="name"
                                            >Dealership Name</FieldLabel
                                        >
                                        <Input
                                            id="name"
                                            name="name"
                                            :default-value="dealership.name"
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
                                            :default-value="dealership.address"
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
                                            :default-value="dealership.city"
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
                                            :default-value="dealership.state"
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
                                            :default-value="dealership.zipCode"
                                            required
                                            placeholder="Zip Code"
                                        />
                                        <InputError
                                            :message="errors.zip_code"
                                        />
                                    </Field>

                                    <Field class="col-span-full">
                                        <FieldLabel for="phone"
                                            >Phone Number</FieldLabel
                                        >
                                        <Input
                                            id="phone"
                                            name="phone"
                                            :default-value="dealership.phone"
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
                                            :default-value="
                                                dealership.currentSolutionName
                                            "
                                        />
                                        <InputError
                                            :message="
                                                errors.current_solution_name
                                            "
                                        />
                                    </Field>

                                    <Field class="col-span-3">
                                        <FieldLabel for="current_solution_use"
                                            >Current Solution Use</FieldLabel
                                        >
                                        <Input
                                            id="current_solution_use"
                                            name="current_solution_use"
                                            :default-value="
                                                dealership.currentSolutionUse
                                            "
                                        />
                                        <InputError
                                            :message="
                                                errors.current_solution_use
                                            "
                                        />
                                    </Field>

                                    <Field class="col-span-full">
                                        <FieldLabel for="notes"
                                            >Notes</FieldLabel
                                        >
                                        <Textarea
                                            id="notes"
                                            name="notes"
                                            :default-value="dealership.notes"
                                            placeholder="Add note..."
                                        />
                                        <InputError :message="errors.notes" />
                                    </Field>
                                </div>
                            </FieldGroup>
                        </div>
                    </Card>
                    <div class="space-y-5">
                        <Card>
                            <CardHeader>
                                <CardTitle>Status</CardTitle>
                            </CardHeader>
                            <CardContent>
                                <Select
                                    id="status"
                                    name="status"
                                    :default-value="dealership.status"
                                >
                                    <SelectTrigger class="w-full">
                                        <SelectValue
                                            placeholder="Select a status"
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
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader>
                                <CardTitle>Rating</CardTitle>
                            </CardHeader>
                            <CardContent>
                                <Select
                                    id="rating"
                                    name="rating"
                                    :default-value="dealership.rating"
                                >
                                    <SelectTrigger class="w-full">
                                        <SelectValue
                                            placeholder="Select a rating"
                                        />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="hot">Hot</SelectItem>
                                        <SelectItem value="warm"
                                            >Warm</SelectItem
                                        >
                                        <SelectItem value="cold"
                                            >Cold</SelectItem
                                        >
                                    </SelectContent>
                                </Select>
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader>
                                <CardTitle
                                    class="flex items-center justify-between"
                                >
                                    Consultants
                                    <Badge variant="outline" class="ml-2"
                                        >{{
                                            dealership.users.data.length
                                        }}
                                        selected</Badge
                                    >
                                </CardTitle>
                            </CardHeader>
                            <CardContent>
                                <ItemGroup
                                    class="divide-y divide-zinc-200 dark:divide-zinc-700"
                                >
                                    <template
                                        v-for="user in dealership.users.data"
                                        :key="user.id"
                                    >
                                        <span class="py-2 text-sm flex items-center justify-between gap-2">
                                            {{ user.name }}
                                            <Button
                                                size="icon-sm"
                                                variant="outline"
                                                class="rounded-full"
                                            >
                                                <Minus />
                                            </Button>
                                        </span>
                                    </template>
                                </ItemGroup>
                            </CardContent>
                        </Card>
                    </div>
                </div>
            </div>
        </Form>
    </AppLayout>
</template>
