import { Head, Link } from '@inertiajs/react';
import { SubmitForm } from '@/components/migration/common';
export default function ReportSecurityChange({
    id,
    token,
    action,
}: {
    id: number | string;
    token: string;
    action: string;
}) {
    return (
        <div className="space-y-4">
            <Head title="Report a security change" />
            <h1 className="text-xl font-semibold">Was this you?</h1>
            <p>
                If you did not request this change, report it. Pending changes
                will be canceled, sessions and trusted devices revoked, and
                account recovery required.
            </p>
            <p>Activity: {action}</p>
            <SubmitForm
                action={`/security-challenges/report/${encodeURIComponent(String(id))}/${encodeURIComponent(token)}`}
                label="It’s not me"
                confirm="Report this security activity and revoke your sessions?"
            />
            <Link href="/login" className="underline">
                Return to sign in
            </Link>
        </div>
    );
}
ReportSecurityChange.layout = {
    title: 'Report a security change',
    description: 'Tell us if you did not request this account change.',
};
