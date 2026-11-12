import { Head, Link } from '@inertiajs/react';
export default function ErrorPage({
    status,
    errorCode,
    referenceId,
}: {
    status: number;
    errorCode?: string | null;
    referenceId?: string | null;
}) {
    return (
        <main className="mx-auto max-w-lg space-y-4 p-8">
            <Head title={`Error ${status}`} />
            <h1 className="text-3xl font-semibold">Error {status}</h1>
            <p>We could not complete your request.</p>
            {errorCode && <p>Error code: {errorCode}</p>}
            {referenceId && <p>Reference: {referenceId}</p>}
            <Link href="/" className="underline">
                Return home
            </Link>
        </main>
    );
}
