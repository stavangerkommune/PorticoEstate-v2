'use client'
import React, {FC} from 'react';
import {useTrans} from "@/app/i18n/ClientTranslationProvider";
import {GSTable, TableOptions} from "@/components/gs-table";
import {DateCompare, NumberCompare, StringCompare } from "@/components/gs-table/table.helper";

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


    const tableOptions: TableOptions<UserData> = {
        keyField: 'id',
        columns: [
            {
                key: 'name',
                title: 'User Name',
                size: 2,
                sortCompare: StringCompare
            },
            {
                key: 'email',
                size: 2,
                sortCompare: StringCompare
            },
            {
                key: 'status',
                render: (value) => (
                    <div className={`px-2 py-1 rounded-full text-sm inline-flex ${
                        value === 'active' ? 'bg-green-100 text-green-800' :
                            value === 'inactive' ? 'bg-red-100 text-red-800' :
                                'bg-yellow-100 text-yellow-800'
                    }`}>
                        {value}
                    </div>
                ),
                sortCompare: StringCompare
            },
            {
                key: 'lastLogin',
                title: 'Last Login',
                render: (value) => value.toLocaleDateString(),
                sortCompare: DateCompare
            },
            {
                key: 'posts',
                title: 'Total Posts',
                contentAlign: 'flex-end',
                sortCompare: NumberCompare
            },
            {
                key: 'role',
                sortCompare: StringCompare
            }
        ],
        expandedContent: (user) => (
            <div className="p-4 bg-gray-50">
                <h3 className="font-bold mb-2">User Details</h3>
                <p>ID: {user.id}</p>
                <p>Email: {user.email}</p>
                <p>Role: {user.role}</p>
                <p>Posts: {user.posts}</p>
                <p>Status: {user.status}</p>
                <p>Last Login: {user.lastLogin.toLocaleString()}</p>
            </div>
        )
    };
    return (
            <GSTable<UserData>
                data={userData}
                options={tableOptions}
            />
    );
}

export default Delegates


