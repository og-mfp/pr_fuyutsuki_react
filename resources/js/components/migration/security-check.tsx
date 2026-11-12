import { useRef, useState, type FormEvent, type ReactNode } from 'react';

type Challenge = { id: string | number; method: 'email' | 'mfa' };
async function postJson(url: string, data: object): Promise<Response> {
    const csrf = document.querySelector<HTMLMetaElement>(
        'meta[name="csrf-token"]',
    )?.content;
    const cookie = document.cookie
        .split('; ')
        .find((item) => item.startsWith('XSRF-TOKEN='))
        ?.slice('XSRF-TOKEN='.length);
    const headers: Record<string, string> = {
        'Content-Type': 'application/json',
        Accept: 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
    };
    if (csrf) headers['X-CSRF-TOKEN'] = csrf;
    else if (cookie) headers['X-XSRF-TOKEN'] = decodeURIComponent(cookie);
    return fetch(url, {
        method: 'POST',
        credentials: 'same-origin',
        headers,
        body: JSON.stringify(data),
    });
}
export async function authorizeSecurityChange(
    action: string,
    payload: Record<string, string> = {},
    target?: string,
): Promise<boolean> {
    const response = await postJson('/settings/security-challenges', {
        action,
        payload,
        target: target || null,
    });
    if (!response.ok) {
        const error = await response.json().catch(() => ({}));
        throw new Error(error.message || 'Unable to start the security check.');
    }
    const challenge = (await response.json()) as Challenge;
    const value = window.prompt(
        challenge.method === 'email'
            ? 'Enter the confirmation code sent to your email.'
            : 'Enter an authenticator code or recovery code.',
    );
    if (!value) return false;
    const recovery =
        challenge.method === 'mfa' && !/^\d{6}$/.test(value.trim());
    const confirmed = await postJson(
        `/settings/security-challenges/${encodeURIComponent(String(challenge.id))}/confirm`,
        recovery ? { recovery_code: value.trim() } : { code: value.trim() },
    );
    if (!confirmed.ok) {
        const error = await confirmed.json().catch(() => ({}));
        throw new Error(error.message || 'Security confirmation failed.');
    }
    return true;
}
export function SecurityFormGuard({
    action,
    fields,
    skip,
    children,
}: {
    action: string;
    fields: string[];
    skip?: (data: FormData) => boolean;
    children: ReactNode;
}) {
    const approved = useRef(false);
    const pending = useRef(false);
    const [error, setError] = useState('');
    const capture = async (event: FormEvent<HTMLDivElement>) => {
        const form = event.target as HTMLFormElement;
        if (approved.current) {
            approved.current = false;
            return;
        }
        const data = new FormData(form);
        if (skip?.(data)) return;
        event.preventDefault();
        event.stopPropagation();
        if (pending.current) return;
        pending.current = true;
        setError('');
        try {
            const payload = Object.fromEntries(
                fields.map((field) => [field, String(data.get(field) ?? '')]),
            );
            if (await authorizeSecurityChange(action, payload)) {
                approved.current = true;
                form.requestSubmit();
            }
        } catch (err) {
            setError(
                err instanceof Error
                    ? err.message
                    : 'Security confirmation failed.',
            );
        } finally {
            pending.current = false;
        }
    };
    return (
        <div onSubmitCapture={capture}>
            {error && (
                <p role="alert" className="text-destructive">
                    {error}
                </p>
            )}
            {children}
        </div>
    );
}
