import { Link } from '@inertiajs/react';
import { Screen, type Named } from '@/components/migration/common';
type User = {
    name: string;
    email: string;
    email_verified_at: string | null;
    frozen_at: string | null;
    full_name: string | null;
    date_of_birth: string | null;
    primary_phone: string | null;
    secondary_phone: string | null;
};
export default function UserDetail({
    user,
    groups,
    permissionSlugs,
    trustedDeviceCount,
}: {
    user: User;
    groups: Named[];
    permissionSlugs: string[];
    trustedDeviceCount: number;
}) {
    return (
        <Screen title={user.name}>
            <Link href="/admin/users" className="underline">
                Back to users
            </Link>
            <dl className="grid grid-cols-2 gap-2 rounded-lg border p-4">
                {Object.entries(user).map(([key, value]) => (
                    <div key={key}>
                        <dt className="font-medium">
                            {key.replaceAll('_', ' ')}
                        </dt>
                        <dd>{value ?? '—'}</dd>
                    </div>
                ))}
                <div>
                    <dt>Trusted devices</dt>
                    <dd>{trustedDeviceCount}</dd>
                </div>
            </dl>
            <h2 className="font-semibold">Groups</h2>
            <ul>
                {groups.map((group) => (
                    <li key={group.id}>
                        <Link
                            className="underline"
                            href={`/admin/groups/${group.id}`}
                        >
                            {group.name}
                        </Link>
                    </li>
                ))}
            </ul>
            <h2 className="font-semibold">Effective permissions</h2>
            <p>{permissionSlugs.join(', ') || 'None'}</p>
        </Screen>
    );
}
