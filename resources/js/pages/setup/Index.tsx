import { Head } from '@inertiajs/react';
import { useState } from 'react';
import { Field, SubmitForm } from '@/components/migration/common';
import { Button } from '@/components/ui/button';

type Check = { label: string; ready: boolean; detail: string };
export default function Setup({
    checks,
    passwordRules,
}: {
    checks: Check[];
    passwordRules: string;
}) {
    const [step, setStep] = useState(1);
    const ready = checks.every((check) => check.ready);
    return (
        <div className="space-y-6">
            <Head title="First-time setup" />
            <h1 className="text-2xl font-semibold">First-time setup</h1>
            <p>Verify the application and create its Owner account.</p>
            <p>Step {step} of 2</p>
            {step === 1 ? (
                <>
                    <ul className="space-y-2" aria-label="Configuration checks">
                        {checks.map((check) => (
                            <li key={check.label}>
                                <strong>{check.label}</strong>:{' '}
                                {check.ready ? 'Ready' : check.detail}
                            </li>
                        ))}
                    </ul>
                    <Button disabled={!ready} onClick={() => setStep(2)}>
                        Continue
                    </Button>
                </>
            ) : (
                <>
                    <SubmitForm action="/setup" label="Create Owner">
                        <Field name="name" label="Superuser name" required />
                        <Field name="email" type="email" required />
                        <label className="grid gap-1">
                            Password
                            <input
                                className="rounded-md border p-2"
                                name="password"
                                type="password"
                                autoComplete="new-password"
                                passwordrules={passwordRules}
                                required
                            />
                        </label>
                        <Field
                            name="password_confirmation"
                            label="Confirm password"
                            type="password"
                            required
                        />
                    </SubmitForm>
                    <Button variant="outline" onClick={() => setStep(1)}>
                        Back
                    </Button>
                </>
            )}
        </div>
    );
}
Setup.layout = {
    title: 'First-time setup',
    description: 'Verify configuration and create the Owner account',
};
