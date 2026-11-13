import { Link, usePage } from '@inertiajs/react';
import { useState } from 'react';
import {
    Field,
    Pagination,
    Screen,
    SubmitForm,
    type Named,
    type Page,
} from '@/components/migration/common';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
type Group = Named & { assignable: boolean };
type User = {
    id: number;
    name: string;
    email: string;
    frozen_at: string | null;
    email_verified_at: string | null;
    groups: Named[];
};
export default function Users({
    users,
    groups,
    filters,
}: {
    users: Page<User>;
    groups: Group[];
    filters: Record<string, string>;
}) {
    const { auth } = usePage<{
        auth: { user: { permissions: string[]; groups: string[] } };
    }>().props;
    const can = (permission: string) =>
        auth.user.permissions.includes(permission);
    const choices = groups.filter((group) => group.assignable);
    const [groupId, setGroupId] = useState(filters.group_id || '');
    return (
        <Screen title="Users">
            <form
                action="/admin/users"
                method="get"
                className="flex flex-wrap items-end gap-2"
            >
                <Field name="search" value={filters.search} />
                <label>
                    Group
                    <input type="hidden" name="group_id" value={groupId} />
                    <Select
                        value={groupId || '__all__'}
                        onValueChange={(value) =>
                            setGroupId(value === '__all__' ? '' : value)
                        }
                    >
                        <SelectTrigger aria-label="Group">
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="__all__">All</SelectItem>
                            {groups.map((group) => (
                                <SelectItem
                                    key={group.id}
                                    value={String(group.id)}
                                >
                                    {group.name}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                </label>
                <label>
                    Status
                    <Select
                        name="frozen"
                        defaultValue={filters.frozen || 'all'}
                    >
                        <SelectTrigger aria-label="Status">
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">All</SelectItem>
                            <SelectItem value="active">Active</SelectItem>
                            <SelectItem value="frozen">Frozen</SelectItem>
                        </SelectContent>
                    </Select>
                </label>
                <button className="rounded border p-2">Filter</button>
            </form>
            {can('users.create') && (
                <details>
                    <summary>Create user</summary>
                    <SubmitForm action="/admin/users" label="Create user">
                        <Field name="name" required />
                        <Field name="email" type="email" required />
                        <Field name="password" type="password" required />
                        <Field
                            name="password_confirmation"
                            type="password"
                            required
                        />
                        {can('permissions.assign') &&
                            choices.map((group) => (
                                <label key={group.id}>
                                    <input
                                        type="checkbox"
                                        name="groups[]"
                                        value={group.id}
                                    />{' '}
                                    {group.name}
                                </label>
                            ))}
                    </SubmitForm>
                </details>
            )}
            <div className="grid gap-4">
                {users.data.map((user) => (
                    <article
                        key={user.id}
                        className="space-y-3 rounded-lg border p-4"
                    >
                        <h2 className="font-semibold">
                            <Link
                                href={`/admin/users/${user.id}`}
                                className="underline"
                            >
                                {user.name}
                            </Link>{' '}
                            — {user.email}
                        </h2>
                        <p>
                            {user.frozen_at ? 'Frozen' : 'Active'} ·{' '}
                            {user.email_verified_at ? 'Verified' : 'Unverified'}{' '}
                            ·{' '}
                            {user.groups.map((group) => group.name).join(', ')}
                        </p>
                        {can('users.update') && (
                            <details>
                                <summary>Edit user</summary>
                                <SubmitForm
                                    action={`/admin/users/${user.id}`}
                                    method="put"
                                >
                                    <Field
                                        name="name"
                                        value={user.name}
                                        required
                                    />
                                    <Field
                                        name="email"
                                        type="email"
                                        value={user.email}
                                        required
                                    />
                                    {auth.user.groups.includes('owner') && (
                                        <Field
                                            name="current_password"
                                            label="Current password (required for email change)"
                                            type="password"
                                        />
                                    )}
                                    {can('permissions.assign') && (
                                        <>
                                            <input
                                                type="hidden"
                                                name="groups_present"
                                                value="1"
                                            />
                                            {groups.map((group) => (
                                                <label key={group.id}>
                                                    <input
                                                        type="checkbox"
                                                        name={
                                                            group.assignable
                                                                ? 'groups[]'
                                                                : undefined
                                                        }
                                                        value={group.id}
                                                        defaultChecked={user.groups.some(
                                                            (item) =>
                                                                item.id ===
                                                                group.id,
                                                        )}
                                                        disabled={
                                                            !group.assignable
                                                        }
                                                    />{' '}
                                                    {group.name}
                                                    {!group.assignable &&
                                                        user.groups.some(
                                                            (item) =>
                                                                item.id ===
                                                                group.id,
                                                        ) && (
                                                            <input
                                                                type="hidden"
                                                                name="groups[]"
                                                                value={group.id}
                                                            />
                                                        )}
                                                </label>
                                            ))}
                                        </>
                                    )}
                                </SubmitForm>
                            </details>
                        )}
                        <div className="flex flex-wrap gap-2">
                            {can('users.update') && !user.email_verified_at && (
                                <SubmitForm
                                    action={`/admin/users/${user.id}/verify-email`}
                                    label="Send verification"
                                />
                            )}
                            {can('users.update') && (
                                <SubmitForm
                                    action={`/admin/users/${user.id}/password-reset`}
                                    label="Send password reset"
                                />
                            )}
                            {can('users.update') && (
                                <SubmitForm
                                    action={`/admin/users/${user.id}/freeze`}
                                    method={user.frozen_at ? 'delete' : 'put'}
                                    label={
                                        user.frozen_at ? 'Unfreeze' : 'Freeze'
                                    }
                                    confirm="Change this account’s freeze status?"
                                />
                            )}
                            {can('users.delete') && (
                                <SubmitForm
                                    action={`/admin/users/${user.id}`}
                                    method="delete"
                                    label="Delete"
                                    confirm={`Delete ${user.name}?`}
                                />
                            )}
                        </div>
                    </article>
                ))}
            </div>
            <Pagination page={users} />
        </Screen>
    );
}
