import {
    Field,
    Pagination,
    Screen,
    SubmitForm,
    type Page,
} from '@/components/migration/common';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
type Window = {
    id: number;
    title: string;
    type: string;
    reason: string;
    public_message: string;
    start_local: string;
    end_local: string;
    scheduled_display: string;
    status: string;
    deployment_ref: string | null;
    operator_notes: string | null;
    published: boolean;
};
function WindowFields({ item }: { item?: Window }) {
    return (
        <>
            <Field name="title" value={item?.title} required />
            <label>
                Type
                <Select
                    name="type"
                    defaultValue={item?.type ?? 'infrastructure'}
                >
                    <SelectTrigger aria-label="Type">
                        <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                        {[
                            'infrastructure',
                            'deployment',
                            'security',
                            'database',
                            'other',
                        ].map((type) => (
                            <SelectItem key={type} value={type}>
                                {type}
                            </SelectItem>
                        ))}
                    </SelectContent>
                </Select>
            </label>
            <Field name="reason" value={item?.reason} required />
            <Field
                name="public_message"
                value={item?.public_message}
                required
            />
            <Field
                name="scheduled_start_at"
                type="datetime-local"
                value={item?.start_local}
                required
            />
            <Field
                name="scheduled_end_at"
                type="datetime-local"
                value={item?.end_local}
                required
            />
            <Field name="deployment_ref" value={item?.deployment_ref} />
            <Field name="operator_notes" value={item?.operator_notes} />
            <label>
                <input
                    name="published"
                    type="checkbox"
                    value="1"
                    defaultChecked={item?.published ?? false}
                />{' '}
                Published
            </label>
            <Field name="current_password" type="password" required />
        </>
    );
}
export default function Maintenance({
    windows,
    health,
    maintenanceState,
    maintenanceConsistent,
    unresolvedIncidents,
    diagnosticEvents,
    filters,
    settings,
}: {
    windows: Page<Window>;
    health: Record<string, unknown>;
    settings: Record<string, string | number>;
    maintenanceState: string;
    maintenanceConsistent: boolean;
    unresolvedIncidents: number;
    diagnosticEvents: unknown[];
    filters: { search: string };
}) {
    return (
        <Screen title="System health & maintenance">
            <p>Maintenance activation remains CLI-controlled.</p>
            <p>
                State: {maintenanceState};{' '}
                {maintenanceConsistent ? 'consistent' : 'inconsistent'};
                unresolved incidents: {unresolvedIncidents}
            </p>
            <details>
                <summary>Health and diagnostics</summary>
                <pre className="overflow-auto">
                    {JSON.stringify({ health, diagnosticEvents }, null, 2)}
                </pre>
            </details>
            <details>
                <summary>Maintenance announcement settings</summary>
                <SubmitForm
                    action="/admin/configuration/health_maintenance"
                    method="put"
                    label="Save maintenance settings"
                >
                    {Object.entries(settings).map(([name, value]) => (
                        <Field
                            key={name}
                            name={name}
                            value={value}
                            type={typeof value === 'number' ? 'number' : 'text'}
                        />
                    ))}
                    <Field
                        name="current_password"
                        type="password"
                        label="Current password"
                    />
                </SubmitForm>
            </details>
            <details>
                <summary>Reconcile maintenance state</summary>
                <SubmitForm
                    action="/admin/maintenance/reconcile"
                    label="Reconcile"
                    confirm="Reconcile the observed maintenance state?"
                >
                    <Field name="explanation" required />
                    <Field name="current_password" type="password" required />
                </SubmitForm>
            </details>
            <details>
                <summary>Schedule maintenance</summary>
                <SubmitForm
                    action="/admin/maintenance"
                    label="Schedule"
                    confirm="Schedule this maintenance window?"
                >
                    <WindowFields />
                </SubmitForm>
            </details>
            <form action="/admin/maintenance" method="get">
                <Field name="search" value={filters.search} />
                <button className="rounded border p-2">Search</button>
            </form>
            {windows.data.map((item) => (
                <article key={item.id} className="space-y-2 rounded border p-4">
                    <h2 className="font-semibold">
                        {item.title} — {item.status}
                    </h2>
                    <p>{item.scheduled_display}</p>
                    <p>{item.reason}</p>
                    <details>
                        <summary>Edit window</summary>
                        <SubmitForm
                            action={`/admin/maintenance/${item.id}`}
                            method="put"
                            confirm="Update this maintenance window?"
                        >
                            <WindowFields item={item} />
                        </SubmitForm>
                    </details>
                    <SubmitForm
                        action={`/admin/maintenance/${item.id}`}
                        method="delete"
                        label="Cancel window"
                        confirm="Cancel this maintenance window?"
                    >
                        <Field
                            name="current_password"
                            type="password"
                            required
                        />
                    </SubmitForm>
                </article>
            ))}
            <Pagination page={windows} />
        </Screen>
    );
}
