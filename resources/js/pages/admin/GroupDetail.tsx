import { Link, usePage } from '@inertiajs/react';
import {
    Field,
    Screen,
    SubmitForm,
    type Named,
} from '@/components/migration/common';
type Role = Named & { permissions: Named[] };
type Group = Named & {
    description: string | null;
    roles: Role[];
    users?: { id: number; name: string }[];
};
export default function GroupDetail({
    group,
    permissions,
}: {
    group: Group;
    permissions: Named[];
}) {
    const { auth } = usePage<{ auth: { user: { permissions: string[] } } }>()
        .props;
    const canAssign = auth.user.permissions.includes('permissions.assign');
    return (
        <Screen title={group.name}>
            <Link className="underline" href="/admin/groups">
                Back to groups
            </Link>
            <p>{group.description}</p>
            <p>
                Members:{' '}
                {group.users?.map((user) => user.name).join(', ') ||
                    'None or restricted'}
            </p>
            {canAssign && (
                <details>
                    <summary>Create role</summary>
                    <SubmitForm
                        action={`/admin/groups/${group.id}/roles`}
                        label="Create role"
                    >
                        <Field name="name" required />
                        <Field name="slug" required />
                        <Field name="description" />
                    </SubmitForm>
                </details>
            )}
            <h2 className="text-lg font-semibold">Roles</h2>
            {group.roles.map((role) => (
                <section
                    className="space-y-2 rounded-lg border p-4"
                    key={role.id}
                >
                    <h3 className="font-semibold">
                        {role.name} ({role.slug})
                    </h3>
                    {canAssign ? (
                        <>
                            <SubmitForm
                                action={`/admin/groups/${group.id}/roles/${role.id}/permissions`}
                                method="put"
                                label="Save permissions"
                            >
                                <div className="grid gap-2">
                                    {permissions.map((permission) => {
                                        const assigned = role.permissions.some(
                                            (item) => item.id === permission.id,
                                        );
                                        const allowed =
                                            auth.user.permissions.includes(
                                                permission.slug,
                                            );
                                        return (
                                            <label key={permission.id}>
                                                <input
                                                    type="checkbox"
                                                    name={
                                                        allowed
                                                            ? 'permissions[]'
                                                            : undefined
                                                    }
                                                    value={permission.id}
                                                    defaultChecked={assigned}
                                                    disabled={!allowed}
                                                />{' '}
                                                {permission.name} (
                                                {permission.slug})
                                                {!allowed && assigned && (
                                                    <input
                                                        type="hidden"
                                                        name="permissions[]"
                                                        value={permission.id}
                                                    />
                                                )}
                                            </label>
                                        );
                                    })}
                                </div>
                            </SubmitForm>
                            {role.slug !== 'default' && (
                                <SubmitForm
                                    action={`/admin/groups/${group.id}/roles/${role.id}`}
                                    method="delete"
                                    label="Delete role"
                                    confirm={`Delete role ${role.name}?`}
                                />
                            )}
                        </>
                    ) : (
                        <p>
                            {role.permissions
                                .map((permission) => permission.name)
                                .join(', ') || 'No permissions'}
                        </p>
                    )}
                </section>
            ))}
        </Screen>
    );
}
