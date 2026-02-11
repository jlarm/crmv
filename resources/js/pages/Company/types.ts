export type CompanyShowTab = 'details' | 'progress' | 'stores' | 'contacts';

export interface User {
    id: number;
    name: string;
}

export interface Contact {
    id: number;
    name: string;
    email: string | null;
    phone: string | null;
    position: string | null;
    linkedinLink: string | null;
    primaryContact: boolean;
}

export interface Store {
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

export interface ProgressItem {
    id: number;
    details: string;
    date: string | null;
    completedAt: string | null;
    createdAt: string | null;
    contact: {
        id: number;
        name: string;
    } | null;
}

export interface Company {
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
    progresses: ProgressItem[];
    users: {
        data: User[];
    };
}
