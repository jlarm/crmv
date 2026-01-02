<script setup lang="ts">
import { Head, Form, useForm } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Trash2, Save } from 'lucide-vue-next';
import { Card } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import {
    show,
    update,
} from '@/actions/App/Http/Controllers/DealershipController';
import { computed, onMounted, onUnmounted } from 'vue';
import {
    Field,
    FieldError,
    FieldGroup,
    FieldLabel,
} from '@/components/ui/field';
import { Textarea } from '@/components/ui/textarea';
import type { BreadcrumbItem } from '@/types';
import { Separator } from '@/components/ui/separator';
import { toast } from 'vue-sonner';

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
}

interface Props {
    dealership: Dealership;
}

const props = defineProps<Props>();

const form = useForm({
    name: props.dealership.name,
    address: props.dealership.address,
    city: props.dealership.city,
    state: props.dealership.state,
    zipCode: props.dealership.zipCode,
    phone: props.dealership.phone,
    notes: props.dealership.notes,
    currentSolutionName: props.dealership.currentSolutionName,
    currentSolutionUse: props.dealership.currentSolutionUse,
});

const isDirty = computed(() => form.isDirty);

function submit(): void {
    form.transform((data) => ({
        name: data.name,
        address: data.address,
        city: data.city,
        state: data.state,
        zip_code: data.zipCode,
        phone: data.phone,
        notes: data.notes,
        current_solution_name: data.currentSolutionName,
        current_solution_use: data.currentSolutionUse,
    })).put(update.url(props.dealership.id), {
        preserveScroll: true,
        onSuccess: () => {
            toast.success('Dealership updated successfully');
        },
    });
}

function cancel(): void {
    form.reset();
}

function handleKeyDown(event: KeyboardEvent): void {
    if ((event.metaKey || event.ctrlKey) && event.key === 's') {
        event.preventDefault();
        if (!form.processing && isDirty.value) {
            submit();
        }
    }
}

const breadcrumbItems: BreadcrumbItem[] = [
    {
        title: props.dealership.name,
        href: show(props.dealership.id).url,
    },
];

onMounted(() => {
    window.addEventListener('keydown', handleKeyDown);
});

onUnmounted(() => {
    window.removeEventListener('keydown', handleKeyDown);
});
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head :title="dealership.name" />
        <Form @submit.prevent="submit" method="post" class="px-8 py-3">
            <div class="flex shrink-0 items-center justify-between gap-4">
                <div class="flex flex-col">
                    <h1 class="text-2xl font-black text-slate-900">
                        {{ dealership.name }}
                    </h1>
                    <p class="text-xs text-zinc-400">ID: {{ dealership.id }}</p>
                </div>
                <div class="flex items-center gap-3">
                    <Button variant="destructive" :disabled="form.processing">
                        <Trash2 />
                        Delete
                    </Button>
                    <div class="mx-1 h-6 w-px bg-slate-200"></div>
                    <Button
                        variant="outline"
                        :disabled="!isDirty || form.processing"
                        @click="cancel"
                        >Cancel</Button
                    >
                    <Button
                        @click="submit"
                        :disabled="form.processing || !isDirty"
                    >
                        <Save />
                        Update
                    </Button>
                </div>
            </div>

            <div class="mx-auto mt-10 w-full">
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
                                            v-model="form.name"
                                            name="name"
                                            placeholder="Name"
                                        />
                                        <FieldError
                                            :errors="[form.errors.name]"
                                        />
                                    </Field>

                                    <Field class="col-span-full">
                                        <FieldLabel for="address"
                                            >Address</FieldLabel
                                        >
                                        <Input
                                            v-model="form.address"
                                            name="address"
                                            placeholder="Address"
                                        />
                                        <FieldError
                                            :errors="[form.errors.address]"
                                        />
                                    </Field>

                                    <Field class="col-span-2">
                                        <FieldLabel for="city">City</FieldLabel>
                                        <Input
                                            v-model="form.city"
                                            name="city"
                                            placeholder="City"
                                        />
                                        <FieldError
                                            :errors="[form.errors.city]"
                                        />
                                    </Field>

                                    <Field class="col-span-2">
                                        <FieldLabel for="state"
                                            >State</FieldLabel
                                        >
                                        <Input
                                            v-model="form.state"
                                            name="state"
                                            placeholder="State"
                                        />
                                        <FieldError
                                            :errors="[form.errors.state]"
                                        />
                                    </Field>

                                    <Field class="col-span-2">
                                        <FieldLabel for="zip_code"
                                            >Zip Code</FieldLabel
                                        >
                                        <Input
                                            v-model="form.zipCode"
                                            name="zip_code"
                                            placeholder="Zip Code"
                                        />
                                        <FieldError
                                            :errors="[form.errors.zipCode]"
                                        />
                                    </Field>

                                    <Field class="col-span-full">
                                        <FieldLabel for="phone"
                                            >Phone Number</FieldLabel
                                        >
                                        <Input
                                            v-model="form.phone"
                                            name="phone"
                                            placeholder="999-999-9999"
                                        />
                                        <FieldError
                                            :errors="[form.errors.phone]"
                                        />
                                    </Field>

                                    <Separator class="col-span-full my-5" />

                                    <Field class="col-span-3">
                                        <FieldLabel for="current_solution_name"
                                            >Current Solution Name</FieldLabel
                                        >
                                        <Input
                                            v-model="form.currentSolutionName"
                                            name="current_solution_name"
                                        />
                                        <FieldError
                                            :errors="[
                                                form.errors.currentSolutionName,
                                            ]"
                                        />
                                    </Field>

                                    <Field class="col-span-3">
                                        <FieldLabel for="current_solution_use"
                                            >Current Solution Use</FieldLabel
                                        >
                                        <Input
                                            v-model="form.currentSolutionUse"
                                            name="current_solution_use"
                                        />
                                        <FieldError
                                            :errors="[
                                                form.errors.currentSolutionUse,
                                            ]"
                                        />
                                    </Field>

                                    <Field class="col-span-full">
                                        <FieldLabel for="notes"
                                            >Notes</FieldLabel
                                        >
                                        <Textarea
                                            v-model="form.notes"
                                            name="notes"
                                            placeholder="Add note..."
                                        />
                                        <FieldError
                                            :errors="[form.errors.notes]"
                                        />
                                    </Field>
                                </div>
                            </FieldGroup>
                        </div>
                    </Card>
                    <Card></Card>
                </div>
            </div>
        </Form>
    </AppLayout>
</template>
