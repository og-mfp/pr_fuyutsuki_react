import { Link, usePage } from '@inertiajs/react';
import { Screen } from '@/components/migration/common';
const links = [
    ['Users', '/admin/users', 'users.view'],
    ['Groups', '/admin/groups', 'groups.view'],
    ['Permissions', '/admin/permissions', 'permissions.view'],
    ['Activity logs', '/admin/activity-logs', 'audit.view'],
    ['Configuration', '/admin/configuration', 'system.settings.manage'],
    ['Maintenance', '/admin/maintenance', 'system.settings.manage'],
] as const;
export default function Admin() {
    const { auth } = usePage<{
        auth: { user: { permissions?: string[]; groups?: string[] } };
    }>().props;
    return (
        <Screen title="Administration">
            <nav className="grid gap-3">
                <Link
                    className="rounded-lg border p-4 underline"
                    href="/admin/ui"
                >
                    UI components
                </Link>
                {links
                    .filter(([, , permission]) =>
                        permission === 'system.settings.manage'
                            ? auth.user.groups?.includes('owner')
                            : auth.user.permissions?.includes(permission),
                    )
                    .map(([label, href]) => (
                        <Link
                            className="rounded-lg border p-4 underline"
                            key={href}
                            href={href}
                        >
                            {label}
                        </Link>
                    ))}
            </nav>
        </Screen>
    );
}
