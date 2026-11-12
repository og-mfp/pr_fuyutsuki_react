import { Link } from '@inertiajs/react';
import { useState } from 'react';
import {
    Field,
    Pagination,
    Screen,
    type Page,
} from '@/components/migration/common';
type Activity = {
    id: number;
    timestamp: string;
    actor: { name: string } | null;
    event: string;
    category: string;
    subject: string | null;
    subject_id: number | null;
    ip: string | null;
    risk_level: string | null;
    risk_score: number | null;
    risk_signals: string[];
    old: Record<string, unknown>;
    new: Record<string, unknown>;
};
export default function ActivityLogs({
    activities,
    filters,
    canExport,
}: {
    activities: Page<Activity>;
    filters: Record<string, string>;
    canExport: boolean;
}) {
    const [selected, setSelected] = useState<Activity | null>(null);
    const query = new URLSearchParams(
        Object.entries(filters).filter(
            ([key, value]) => key !== 'page' && !!value,
        ),
    ).toString();
    return (
        <Screen title="Activity logs">
            {canExport && (
                <a
                    className="underline"
                    href={`/admin/activity-logs/export${query ? `?${query}` : ''}`}
                >
                    Export CSV
                </a>
            )}
            <form
                action="/admin/activity-logs"
                method="get"
                role="search"
                className="grid gap-3 rounded border p-4 sm:grid-cols-3"
            >
                {[
                    'from',
                    'to',
                    'actor',
                    'event',
                    'category',
                    'risk_level',
                    'subject',
                    'subject_id',
                ].map((name) => (
                    <Field
                        key={name}
                        name={name}
                        type={
                            name === 'from' || name === 'to' ? 'date' : 'text'
                        }
                        value={filters[name]}
                    />
                ))}
                <button className="rounded border p-2">Apply filters</button>
                <Link href="/admin/activity-logs">Clear</Link>
            </form>
            <div className="overflow-x-auto">
                <table className="w-full text-left">
                    <thead>
                        <tr>
                            <th>Timestamp</th>
                            <th>Actor</th>
                            <th>Event</th>
                            <th>Target</th>
                            <th>Category</th>
                            <th>Details</th>
                        </tr>
                    </thead>
                    <tbody>
                        {activities.data.map((activity) => (
                            <tr className="border-t" key={activity.id}>
                                <td>{activity.timestamp}</td>
                                <td>
                                    {activity.actor?.name ?? 'System / unknown'}
                                </td>
                                <td>{activity.event}</td>
                                <td>
                                    {activity.subject} {activity.subject_id}
                                </td>
                                <td>{activity.category}</td>
                                <td>
                                    <button
                                        onClick={() => setSelected(activity)}
                                        className="underline"
                                    >
                                        View
                                    </button>
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>
            {selected && (
                <section
                    className="space-y-2 rounded border p-4"
                    aria-label="Activity details"
                >
                    <button
                        className="underline"
                        onClick={() => setSelected(null)}
                    >
                        Close
                    </button>
                    <h2>Activity #{selected.id}</h2>
                    <p>
                        IP: {selected.ip ?? '—'} · Risk:{' '}
                        {selected.risk_level ?? '—'} {selected.risk_score ?? ''}
                    </p>
                    <p>Signals: {selected.risk_signals?.join(', ') || '—'}</p>
                    <pre className="overflow-auto">
                        {JSON.stringify(
                            { before: selected.old, after: selected.new },
                            null,
                            2,
                        )}
                    </pre>
                </section>
            )}
            <Pagination page={activities} />
        </Screen>
    );
}
