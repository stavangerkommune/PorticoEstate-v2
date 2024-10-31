'use client'
import React, {FC} from 'react';
import {useTrans} from "@/app/i18n/ClientTranslationProvider";
import {GSTable} from "@/components/gs-table";
import {CellContext, createColumnHelper} from "@tanstack/table-core";
import {ColumnDef} from "@/components/gs-table/table.types";

interface DelegatesProps {
}



// Define the data type
interface UserData {
    id: number;
    name: string;
    email: string;
    status: 'active' | 'inactive' | 'pending';
    lastLogin: Date;
    posts: number;
    role: string;
}
const userData: UserData[] = [
    {
        id: 1,
        name: "John Smith",
        email: "john.smith@example.com",
        status: "active",
        lastLogin: new Date('2024-03-15T10:30:00'),
        posts: 145,
        role: "Admin"
    },
    {
        id: 2,
        name: "Sarah Johnson",
        email: "sarah.j@example.com",
        status: "inactive",
        lastLogin: new Date('2024-03-10T15:45:00'),
        posts: 67,
        role: "Editor"
    },
    {
        id: 3,
        name: "Michael Chen",
        email: "m.chen@example.com",
        status: "active",
        lastLogin: new Date('2024-03-18T09:15:00'),
        posts: 234,
        role: "Author"
    },
    {
        id: 4,
        name: "Emma Wilson",
        email: "emma.w@example.com",
        status: "pending",
        lastLogin: new Date('2024-03-17T14:20:00'),
        posts: 89,
        role: "Contributor"
    },
    {
        id: 5,
        name: "David Brown",
        email: "david.b@example.com",
        status: "active",
        lastLogin: new Date('2024-03-16T11:50:00'),
        posts: 178,
        role: "Editor"
    }
];

const Delegates: FC<DelegatesProps> = (props) => {
    const t = useTrans();


    // Optional: Use TanStack's column helper for better type inference
    const columnHelper = createColumnHelper<UserData>();


    const columns: ColumnDef<UserData>[] = [
        {
            id: 'name',
            accessorFn: row => row.name,
            header: 'User Name',
            meta: {
                size: 2
            },
            sortingFn: 'alphanumeric'
        },
        {
            id: 'email',
            accessorFn: row => row.email,
            meta: {
                size: 2
            },
            sortingFn: 'alphanumeric'
        },
        {
            id: 'status',
            accessorFn: row => row.status,
            cell: (info: CellContext<UserData, 'active' | 'inactive' | 'pending'>) => {
                const status = info.getValue();
                return (
                    <div>
                        {status}
                    </div>
                );
            },
            sortingFn: 'alphanumeric'
        },
        {
            id: 'lastLogin',
            accessorFn: row => row.lastLogin,
            header: 'Last Login',
            cell: (info: CellContext<UserData, Date>) =>
                info.getValue().toLocaleDateString(),
            sortingFn: (rowA, rowB) => {
                return rowA.original.lastLogin.getTime() - rowB.original.lastLogin.getTime();
            }
        },
        {
            id: 'posts',
            accessorFn: row => row.posts,
            header: 'Total Posts',
            meta: {
                align: 'end'
            },
            sortingFn: 'alphanumeric'
        },
        {
            id: 'role',
            accessorFn: row => row.role,
            sortingFn: 'alphanumeric'
        }
    ] as const;
    return (
        <GSTable<UserData>
            data={userData}
            columns={columns}
            enableSorting={true}
            renderExpandedContent={(user) => (
                <div className="p-4 bg-gray-50">
                    <h3 className="font-bold mb-2">User Details</h3>
                    <p>ID: {user.id}</p>
                    <p>Email: {user.email}</p>
                    <p>Role: {user.role}</p>
                    <p>Posts: {user.posts}</p>
                    <p>Status: {user.status}</p>
                    <p>Last Login: {user.lastLogin.toLocaleString()}</p>
                </div>
            )}
        />
    );
}

export default Delegates


