import { Form, Head, Link } from '@inertiajs/react';
import type { ReactNode } from 'react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';

export type Page<T> = {
    data: T[];
    total: number;
    prev_page_url: string | null;
    next_page_url: string | null;
};
export type Named = { id: number; name: string; slug: string };
export function Screen({
    title,
    children,
}: {
    title: string;
    children: ReactNode;
}) {
    return (
        <div className="space-y-5 p-4 md:p-6">
            <Head title={title} />
            <h1 className="text-2xl font-semibold">{title}</h1>
            {children}
        </div>
    );
}
export function Field({
    name,
    label,
    type = 'text',
    value,
    required = false,
}: {
    name: string;
    label?: string;
    type?: string;
    value?: string | number | null;
    required?: boolean;
}) {
    return (
        <label className="grid gap-1 text-sm">
            {label ?? name.replaceAll('_', ' ')}
            <Input
                name={name}
                type={type}
                defaultValue={value ?? ''}
                required={required}
            />
        </label>
    );
}
export function Errors({ errors }: { errors: Record<string, string> }) {
    return (
        <div role="alert" className="text-sm text-destructive">
            {Object.entries(errors).map(([key, value]) => (
                <p key={key}>
                    {key}: {value}
                </p>
            ))}
        </div>
    );
}
export function SubmitForm({
    action,
    method = 'post',
    children,
    label = 'Save',
    confirm,
}: {
    action: string;
    method?: 'post' | 'put' | 'patch' | 'delete';
    children?: ReactNode;
    label?: string;
    confirm?: string;
}) {
    return (
        <div
            onSubmitCapture={(event) => {
                if (confirm && !window.confirm(confirm)) {
                    event.preventDefault();
                    event.stopPropagation();
                }
            }}
        >
            <Form
                action={action}
                method={method}
                className="grid gap-3 rounded-lg border p-4"
            >
                {({ errors, processing, wasSuccessful }) => (
                    <>
                        {children}
                        <Errors errors={errors} />
                        <Button type="submit" disabled={processing}>
                            {processing ? 'Please wait…' : label}
                        </Button>
                        {wasSuccessful && <span role="status">Saved.</span>}
                    </>
                )}
            </Form>
        </div>
    );
}
export function Pagination<T>({ page }: { page: Page<T> }) {
    return (
        <div className="flex gap-4 text-sm">
            <span>{page.total} records</span>
            {page.prev_page_url && (
                <Link href={page.prev_page_url}>Previous</Link>
            )}
            {page.next_page_url && <Link href={page.next_page_url}>Next</Link>}
        </div>
    );
}
