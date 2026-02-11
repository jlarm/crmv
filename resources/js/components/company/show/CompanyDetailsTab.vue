<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import { Trash2, Save } from 'lucide-vue-next';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Field, FieldGroup, FieldLabel } from '@/components/ui/field';
import { Textarea } from '@/components/ui/textarea';
import { Separator } from '@/components/ui/separator';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import InputError from '@/components/InputError.vue';
import { update } from '@/routes/company';
import type { Company, User } from '@/pages/Company/types';

const props = defineProps<{
    company: Company;
    allUsers: User[];
}>();

const consultantSearch = ref('');
const selectedConsultantIds = ref<number[]>([]);
const initialConsultantIds = ref<number[]>([]);
const lastCompanyId = ref<number | null>(null);

const selectedConsultants = computed(() => {
    return props.allUsers.filter((user) =>
        selectedConsultantIds.value.includes(user.id),
    );
});

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

const completedProgressTimeline = computed(() => {
    return props.company.progresses
        .filter((progress) => !!progress.completedAt)
        .map((progress) => ({
            progress,
            completedAtTs: new Date(progress.completedAt as string).getTime(),
        }))
        .filter((item) => !Number.isNaN(item.completedAtTs))
        .sort((a, b) => b.completedAtTs - a.completedAtTs)
        .slice(0, 5);
});

function formatCompletedAt(value: string): string {
    const completedAt = new Date(value);
    if (Number.isNaN(completedAt.getTime())) {
        return 'Unknown date';
    }

    return new Intl.DateTimeFormat('en-US', {
        month: 'short',
        day: '2-digit',
        year: 'numeric',
    }).format(completedAt);
}

function syncConsultantsFromCompany(): void {
    const ids = (props.company.users?.data ?? []).map((user) => user.id);
    selectedConsultantIds.value = ids;
    initialConsultantIds.value = ids;
}

watch(
    () => props.company.id,
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

const consultantsDirty = computed(() => {
    const current = [...selectedConsultantIds.value].sort();
    const initial = [...initialConsultantIds.value].sort();

    if (current.length !== initial.length) {
        return true;
    }

    return current.some((id, index) => id !== initial[index]);
});

watch(
    () => (props.company.users?.data ?? []).map((user) => user.id).join(','),
    () => {
        if (!consultantsDirty.value) {
            syncConsultantsFromCompany();
        }
    },
);

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
</script>

<template>
    <div class="mx-auto mt-5 w-full">
        <Form
            :action="update(company.id).url"
            method="put"
            class="grid grid-cols-1 gap-5 md:grid-cols-3"
            set-defaults-on-success
            v-slot="{ errors, processing, isDirty }"
        >
            <div class="col-span-2 space-y-5">
                <Card>
                    <div class="space-y-5 px-5">
                        <FieldGroup>
                            <div class="grid grid-cols-6 gap-5">
                                <Field class="col-span-full">
                                    <FieldLabel for="name">Company Name</FieldLabel>
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
                                    <FieldLabel for="address">Address</FieldLabel>
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
                                    <FieldLabel for="state">State</FieldLabel>
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
                                    <FieldLabel for="zip_code">Zip Code</FieldLabel>
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
                                    <FieldLabel for="phone">Phone Number</FieldLabel>
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
                                    <FieldLabel for="current_solution_name">Current Solution Name</FieldLabel>
                                    <Input
                                        id="current_solution_name"
                                        name="current_solution_name"
                                        :default-value="company.currentSolutionName"
                                        placeholder="Solution Name"
                                    />
                                </Field>

                                <Field class="col-span-3">
                                    <FieldLabel for="current_solution_use">Current Solution Use</FieldLabel>
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
            </div>

            <div class="self-start space-y-5">
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
                                            <span>Select consultants</span>
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
                                <div class="text-xs text-slate-500">Selected consultants</div>
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
                                            <SelectValue :placeholder="company.status" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="active">Active</SelectItem>
                                            <SelectItem value="inactive">Inactive</SelectItem>
                                        </SelectContent>
                                    </Select>
                                </Field>

                                <Field class="col-span-2">
                                    <FieldLabel for="rating">Rating</FieldLabel>
                                    <Select name="rating" :default-value="company.rating">
                                        <SelectTrigger>
                                            <SelectValue :placeholder="company.rating" />
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

                <Card>
                    <CardHeader>
                        <CardTitle>Completed Progress</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="space-y-4">
                            <div
                                v-for="(item, index) in completedProgressTimeline"
                                :key="item.progress.id"
                                class="flex gap-3"
                            >
                                <div class="flex flex-col items-center">
                                    <div class="mt-1 h-2.5 w-2.5 shrink-0 rounded-full bg-emerald-600"></div>
                                    <div
                                        v-if="index < completedProgressTimeline.length - 1"
                                        class="mt-2 w-px flex-1 bg-slate-200 dark:bg-slate-800"
                                    ></div>
                                </div>
                                <div class="min-w-0 pb-4">
                                    <p class="text-xs text-slate-500">
                                        {{ formatCompletedAt(item.progress.completedAt as string) }}
                                    </p>
                                    <p class="text-sm text-slate-900 dark:text-slate-100">
                                        {{ item.progress.details }}
                                    </p>
                                    <p class="text-xs text-slate-500">
                                        {{ item.progress.contact?.name || 'Unassigned' }}
                                    </p>
                                </div>
                            </div>

                            <p
                                v-if="completedProgressTimeline.length === 0"
                                class="text-xs text-muted-foreground"
                            >
                                No completed progress yet.
                            </p>
                        </div>
                    </CardContent>
                </Card>
            </div>

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
</template>
