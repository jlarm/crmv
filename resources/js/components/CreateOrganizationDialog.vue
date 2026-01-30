<script setup lang="ts">
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { store } from '@/actions/App/Http/Controllers/OrganizationController';
import { Label } from '@/components/ui/label';
import { Input } from '@/components/ui/input';
import { Button } from '@/components/ui/button';
import { Form } from '@inertiajs/vue3';

const open = defineModel<boolean>('open', { default: false });
</script>

<template>
    <Dialog v-model:open="open">
        <DialogContent>
            <Form
                :action="store()"
                #default="{ errors, invalid, validate }"
                @success="open = false"
            >
                <DialogHeader>
                    <DialogTitle>New Organization</DialogTitle>
                    <DialogDescription
                        >Create a new organization to group and manage
                        applications, teams, and resources.</DialogDescription
                    >
                </DialogHeader>
                <div class="grid gap-4 my-5">
                    <div class="grid gap-3">
                        <Label for="name">Organization Name</Label>
                        <Input
                            id="name"
                            name="name"
                            type="text"
                            placeholder="My Organization"
                            @change="validate('name')"
                        />
                        <p class="text-sm text-red-500" v-if="invalid('name')">{{ errors.name }}</p>
                    </div>
                </div>
                <DialogFooter>
                    <DialogClose as-child>
                        <Button variant="outline">Cancel</Button>
                    </DialogClose>
                    <Button type="submit">Create Organization</Button>
                </DialogFooter>
            </Form>
        </DialogContent>
    </Dialog>
</template>
