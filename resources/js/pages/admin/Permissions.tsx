import { Link } from '@inertiajs/react';
import { Screen, type Named } from '@/components/migration/common';
type Permission = Named & { groups?: Named[] };
export default function Permissions({
    permissions,
    stale,
}: {
    permissions: Permission[];
    stale: string[];
}) {
    return (
        <Screen title="Permissions">
            <Link href="/admin/groups" className="underline">
                Manage group roles
            </Link>
            {stale.length > 0 && (
                <p role="alert">Unknown permissions: {stale.join(', ')}</p>
            )}
            <div className="overflow-x-auto">
                <table className="w-full text-left">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Slug</th>
                            <th>Groups</th>
                        </tr>
                    </thead>
                    <tbody>
                        {permissions.map((permission) => (
                            <tr className="border-t" key={permission.id}>
                                <td>{permission.name}</td>
                                <td>{permission.slug}</td>
                                <td>
                                    {permission.groups
                                        ?.map((group) => group.name)
                                        .join(', ') || '—'}
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>
        </Screen>
    );
}
