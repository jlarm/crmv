<script setup lang="ts">
import { Form, router } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import { MoreVertical, Calendar as CalendarIcon } from 'lucide-vue-next';
import { getLocalTimeZone } from '@internationalized/date';
import { toDate } from 'reka-ui/date';
import type { DateValue } from 'reka-ui';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Field, FieldLabel } from '@/components/ui/field';
import { Textarea } from '@/components/ui/textarea';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';
import { Calendar } from '@/components/ui/calendar';
import InputError from '@/components/InputError.vue';
import type { Company, ProgressItem } from '@/pages/Company/types';

const props = defineProps<{
    company: Company;
}>();

const isProgressCreateOpen = ref(false);
const isProgressEditOpen = ref(false);
const editingProgress = ref<ProgressItem | null>(null);
const createProgressCompleted = ref(false);
const editProgressCompleted = ref(false);
const createContactId = ref<string>('none');
const editContactId = ref<string>('none');
const createProgressDate = ref<DateValue | null>(null);

const createProgressDateLabel = computed(() => {
    if (!createProgressDate.value) {
        return 'Pick a date';
    }

    return new Intl.DateTimeFormat('en-US', {
        month: 'short',
        day: '2-digit',
        year: 'numeric',
    }).format(toDate(createProgressDate.value, getLocalTimeZone()));
});

watch(isProgressCreateOpen, (open) => {
    if (!open) {
        createProgressCompleted.value = false;
        createContactId.value = 'none';
        createProgressDate.value = null;
    }
});

watch(isProgressEditOpen, (open) => {
    if (!open) {
        editingProgress.value = null;
        editProgressCompleted.value = false;
        editContactId.value = 'none';
    }
});

function openProgressEdit(progress: ProgressItem): void {
    editingProgress.value = progress;
    editProgressCompleted.value = !!progress.completedAt;
    editContactId.value = progress.contact?.id
        ? String(progress.contact.id)
        : 'none';
    isProgressEditOpen.value = true;
}

function deleteProgress(progress: ProgressItem): void {
    if (!window.confirm('Delete this progress item?')) {
        return;
    }

    router.delete(`/companies/${props.company.id}/progresses/${progress.id}`);
}

function toggleProgressComplete(progress: ProgressItem, checked: boolean | 'indeterminate'): void {
    if (checked === 'indeterminate') {
        return;
    }

    router.put(`/companies/${props.company.id}/progresses/${progress.id}`, {
        completed: checked,
    });
}
</script>

<template>
    <div class="mx-auto mt-6 w-full">
        <Card>
            <CardHeader class="flex items-center justify-between gap-4 sm:flex-row">
                <div class="space-y-1">
                    <CardTitle>History</CardTitle>
                    <p class="text-xs text-muted-foreground">
                        Track key moments with a contact attached.
                    </p>
                </div>
                <Button
                    size="sm"
                    type="button"
                    @click="isProgressCreateOpen = true"
                >
                    Add item
                </Button>
            </CardHeader>
            <CardContent>
                <div class="space-y-4">
                    <div
                        v-for="(progress, index) in company.progresses"
                        :key="progress.id"
                        class="relative flex gap-4"
                    >
                        <div class="flex flex-col items-center">
                            <div class="h-3 w-3 shrink-0 rounded-full bg-slate-900"></div>
                            <div
                                v-if="index < company.progresses.length - 1"
                                class="mt-2 w-px flex-1 bg-slate-200 dark:bg-slate-800"
                            ></div>
                        </div>
                        <div class="flex-1 rounded-xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-950">
                            <div class="flex items-start justify-between gap-3">
                                <div class="space-y-2">
                                    <label class="flex items-center gap-2 text-sm font-medium text-slate-900 dark:text-slate-100">
                                        <Checkbox
                                            :model-value="!!progress.completedAt"
                                            @update:model-value="toggleProgressComplete(progress, $event)"
                                        />
                                        <span :class="progress.completedAt ? 'line-through text-slate-400' : ''">
                                            {{ progress.details }}
                                        </span>
                                    </label>
                                    <p class="text-xs text-slate-500">
                                        Contact:
                                        <span class="font-medium text-slate-700 dark:text-slate-300">
                                            {{ progress.contact?.name || 'Unassigned' }}
                                        </span>
                                    </p>
                                </div>
                                <div class="flex items-center gap-2 text-xs text-slate-500">
                                    <span>
                                        {{ progress.date || progress.createdAt || '—' }}
                                    </span>
                                    <DropdownMenu>
                                        <DropdownMenuTrigger as-child>
                                            <Button variant="ghost" size="icon" class="h-7 w-7">
                                                <MoreVertical class="h-4 w-4" />
                                            </Button>
                                        </DropdownMenuTrigger>
                                        <DropdownMenuContent align="end">
                                            <DropdownMenuItem @click="openProgressEdit(progress)">
                                                Edit
                                            </DropdownMenuItem>
                                            <DropdownMenuItem
                                                class="text-red-600 focus:text-red-600"
                                                @click="deleteProgress(progress)"
                                            >
                                                Delete
                                            </DropdownMenuItem>
                                        </DropdownMenuContent>
                                    </DropdownMenu>
                                </div>
                            </div>
                        </div>
                    </div>

                    <p
                        v-if="company.progresses.length === 0"
                        class="text-xs text-muted-foreground"
                    >
                        No history entries yet.
                    </p>
                </div>
            </CardContent>
        </Card>

        <Dialog v-model:open="isProgressCreateOpen">
            <DialogContent class="sm:max-w-lg">
                <DialogHeader>
                    <DialogTitle>Add history item</DialogTitle>
                    <DialogDescription>
                        Log a step with an optional contact and due date.
                    </DialogDescription>
                </DialogHeader>
                <Form
                    :action="`/companies/${company.id}/progresses`"
                    method="post"
                    class="grid gap-4"
                    reset-on-success
                    :on-success="() => (isProgressCreateOpen = false)"
                    v-slot="{ errors, processing }"
                >
                    <Field>
                        <FieldLabel for="progress_details">Details</FieldLabel>
                        <Textarea
                            id="progress_details"
                            name="details"
                            placeholder="What happened or what's next?"
                            required
                        />
                        <InputError :message="errors.details" />
                    </Field>
                    <Field>
                        <FieldLabel for="progress_contact">Contact</FieldLabel>
                        <Select v-model="createContactId">
                            <SelectTrigger id="progress_contact">
                                <SelectValue placeholder="Choose a contact" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="none">Unassigned</SelectItem>
                                <SelectItem
                                    v-for="contact in company.contacts"
                                    :key="contact.id"
                                    :value="String(contact.id)"
                                >
                                    {{ contact.name }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <input
                            type="hidden"
                            name="contact_id"
                            :value="createContactId === 'none' ? '' : createContactId"
                        />
                        <InputError :message="errors.contact_id" />
                    </Field>
                    <Field>
                        <FieldLabel for="progress_date">Due date</FieldLabel>
                        <Popover>
                            <PopoverTrigger as-child>
                                <Button
                                    variant="outline"
                                    type="button"
                                    class="w-full justify-between text-left font-normal"
                                    id="progress_date"
                                >
                                    <span>{{ createProgressDateLabel }}</span>
                                    <CalendarIcon class="h-4 w-4 text-muted-foreground" />
                                </Button>
                            </PopoverTrigger>
                            <PopoverContent class="w-auto p-0" align="start">
                                <Calendar v-model="createProgressDate" />
                            </PopoverContent>
                        </Popover>
                        <input
                            type="hidden"
                            name="date"
                            :value="createProgressDate ? createProgressDate.toString() : ''"
                        />
                        <InputError :message="errors.date" />
                    </Field>
                    <Field>
                        <label class="flex items-center gap-2 text-sm">
                            <Checkbox v-model="createProgressCompleted" />
                            Mark completed
                        </label>
                        <input
                            type="hidden"
                            name="completed"
                            :value="createProgressCompleted ? '1' : '0'"
                        />
                    </Field>
                    <DialogFooter>
                        <DialogClose as-child>
                            <Button variant="ghost" type="button">Cancel</Button>
                        </DialogClose>
                        <Button :disabled="processing">Create</Button>
                    </DialogFooter>
                </Form>
            </DialogContent>
        </Dialog>

        <Dialog v-model:open="isProgressEditOpen">
            <DialogContent class="sm:max-w-lg">
                <DialogHeader>
                    <DialogTitle>Edit history item</DialogTitle>
                    <DialogDescription>
                        Update the details, contact, or due date.
                    </DialogDescription>
                </DialogHeader>
                <Form
                    v-if="editingProgress"
                    :action="`/companies/${company.id}/progresses/${editingProgress.id}`"
                    method="put"
                    class="grid gap-4"
                    :on-success="() => (isProgressEditOpen = false)"
                    v-slot="{ errors, processing }"
                >
                    <Field>
                        <FieldLabel for="progress_edit_details">Details</FieldLabel>
                        <Textarea
                            id="progress_edit_details"
                            name="details"
                            :default-value="editingProgress.details"
                            required
                        />
                        <InputError :message="errors.details" />
                    </Field>
                    <Field>
                        <FieldLabel for="progress_edit_contact">Contact</FieldLabel>
                        <Select v-model="editContactId">
                            <SelectTrigger id="progress_edit_contact">
                                <SelectValue placeholder="Choose a contact" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="none">Unassigned</SelectItem>
                                <SelectItem
                                    v-for="contact in company.contacts"
                                    :key="contact.id"
                                    :value="String(contact.id)"
                                >
                                    {{ contact.name }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <input
                            type="hidden"
                            name="contact_id"
                            :value="editContactId === 'none' ? '' : editContactId"
                        />
                        <InputError :message="errors.contact_id" />
                    </Field>
                    <Field>
                        <FieldLabel for="progress_edit_date">Due date</FieldLabel>
                        <Input
                            id="progress_edit_date"
                            name="date"
                            type="date"
                            :default-value="editingProgress.date || ''"
                        />
                        <InputError :message="errors.date" />
                    </Field>
                    <Field>
                        <label class="flex items-center gap-2 text-sm">
                            <Checkbox v-model="editProgressCompleted" />
                            Mark completed
                        </label>
                        <input
                            type="hidden"
                            name="completed"
                            :value="editProgressCompleted ? '1' : '0'"
                        />
                    </Field>
                    <DialogFooter>
                        <DialogClose as-child>
                            <Button variant="ghost" type="button">Cancel</Button>
                        </DialogClose>
                        <Button :disabled="processing">Save</Button>
                    </DialogFooter>
                </Form>
            </DialogContent>
        </Dialog>
    </div>
</template>
