import { Link, usePage } from '@inertiajs/react';
import {
    Field,
    Screen,
    SubmitForm,
    type Named,
} from '@/components/migration/common';
type Group = Named & {
    description: string | null;
    users?: { id: number; name: string }[];
};
export default function Groups({ groups }: { groups: Group[] }) {
    const { auth } = usePage<{ auth: { user: { permissions: string[] } } }>()
        .props;
    const can = (permission: string) =>
        auth.user.permissions.includes(permission);
    return (
        <Screen title="Groups">
            {can('groups.create') && (
                <details>
                    <summary>Create group</summary>
                    <SubmitForm action="/admin/groups" label="Create group">
                        <Field name="name" required />
                        <Field name="slug" required />
                        <Field name="description" />
                    </SubmitForm>
                </details>
            )}
            <div className="grid gap-3">
                {groups.map((group) => (
                    <article
                        className="space-y-3 rounded-lg border p-4"
                        key={group.id}
                    >
                        <h2 className="font-semibold">
                            <Link
                                className="underline"
                                href={`/admin/groups/${group.id}`}
                            >
                                {group.name}
                            </Link>{' '}
                            ({group.slug})
                        </h2>
                        <p>{group.description}</p>
                        <p>
                            Members:{' '}
                            {group.users?.map((user) => user.name).join(', ') ||
                                'None or restricted'}
                        </p>
                        {can('groups.update') && (
                            <details>
                                <summary>Edit</summary>
                                <SubmitForm
                                    action={`/admin/groups/${group.id}`}
                                    method="put"
                                >
                                    <Field
                                        name="name"
                                        value={group.name}
                                        required
                                    />
                                    <Field
                                        name="slug"
                                        value={group.slug}
                                        required
                                    />
                                    <Field
                                        name="description"
                                        value={group.description}
                                    />
                                </SubmitForm>
                            </details>
                        )}
                        {can('groups.delete') && (
                            <SubmitForm
                                action={`/admin/groups/${group.id}`}
                                method="delete"
                                label="Delete group"
                                confirm={`Delete ${group.name}?`}
                            />
                        )}
                    </article>
                ))}
            </div>
        </Screen>
    );
}
