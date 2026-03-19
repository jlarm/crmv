export interface SelectOption {
    id: number;
    name: string;
}

export interface CompanySearchResult {
    id: number;
    name: string;
    city: string | null;
    state: string | null;
}

export interface TaskItem {
    id: number;
    name: string;
    description: string | null;
    task_type: string;
    priority: string;
    status: string;
    due_date: string | null;
    company_id: number | null;
    assigned_to: number | null;
    store_id: number | null;
    contact_id: number | null;
    company: { id: number; name: string } | null;
    assignedTo: { id: number; name: string } | null;
    store: { id: number; name: string } | null;
    contact: { id: number; name: string } | null;
}

export interface TaskFormOptions {
    taskTypes: string[];
    priorities: string[];
    statuses: string[];
    users: SelectOption[];
}
