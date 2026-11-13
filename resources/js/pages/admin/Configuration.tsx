import { useState } from 'react';
import { Field, Screen, SubmitForm } from '@/components/migration/common';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
type Value = string | number | boolean | null;
const options: Record<string, string[]> = {
    default_landing_page: ['welcome', 'dashboard'],
    admin_theme: ['light', 'dark', 'system'],
    mfa_requirement: ['optional', 'required'],
    password_policy: ['standard', 'strong'],
    risk_mode: ['monitor', 'enforce'],
    registration_mode: ['public', 'invitation_only', 'administrator_created'],
    scheme: ['smtp', 'smtps'],
};
export default function Configuration({
    settings,
    defaultRoleOptions,
}: {
    settings: Record<string, Record<string, Value>>;
    defaultRoleOptions: { name: string; slug: string }[];
}) {
    const [category, setCategory] = useState(Object.keys(settings)[0]);
    const [emergency, setEmergency] = useState('logout');
    return (
        <Screen title="Application configuration">
            <nav className="flex flex-wrap gap-2">
                {Object.keys(settings).map((key) => (
                    <button
                        className="rounded border p-2"
                        aria-pressed={category === key}
                        key={key}
                        onClick={() => setCategory(key)}
                    >
                        {key}
                    </button>
                ))}
            </nav>
            <SubmitForm
                key={category}
                action={`/admin/configuration/${category}`}
                method="post"
                label="Save settings"
            >
                <input type="hidden" name="_method" value="PUT" />
                {Object.entries(settings[category] ?? {})
                    .filter(
                        ([key]) =>
                            ![
                                'authentication_lockdown',
                                'lockdown_reason',
                            ].includes(key),
                    )
                    .map(([key, value]) =>
                        key === 'logo' || key === 'favicon' ? (
                            <div key={key}>
                                <label>
                                    {key}
                                    <input
                                        className="block"
                                        type="file"
                                        name={key}
                                        accept="image/*"
                                    />
                                </label>
                                <label>
                                    <input
                                        type="checkbox"
                                        name={`remove_${key}`}
                                        value="1"
                                    />{' '}
                                    Remove existing {key}
                                </label>
                            </div>
                        ) : typeof value === 'boolean' ? (
                            <label key={key} className="grid gap-1">
                                {key.replaceAll('_', ' ')}
                                <Select
                                    name={key}
                                    defaultValue={value ? '1' : '0'}
                                >
                                    <SelectTrigger className="w-full">
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="1">
                                            Enabled
                                        </SelectItem>
                                        <SelectItem value="0">
                                            Disabled
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                            </label>
                        ) : options[key] || key === 'default_role' ? (
                            <label key={key} className="grid gap-1">
                                {key.replaceAll('_', ' ')}
                                <Select
                                    name={key}
                                    defaultValue={
                                        (key === 'default_role'
                                            ? defaultRoleOptions.map(
                                                  (option) => option.slug,
                                              )
                                            : options[key]
                                        ).includes(String(value ?? ''))
                                            ? String(value)
                                            : key === 'default_role'
                                              ? defaultRoleOptions[0]?.slug
                                              : options[key][0]
                                    }
                                >
                                    <SelectTrigger className="w-full">
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {(key === 'default_role'
                                            ? defaultRoleOptions.map(
                                                  (option) => option.slug,
                                              )
                                            : options[key]
                                        ).map((option) => (
                                            <SelectItem
                                                key={option}
                                                value={option}
                                            >
                                                {option}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </label>
                        ) : (
                            <Field
                                key={key}
                                name={key}
                                value={value === null ? '' : value}
                                type={
                                    typeof value === 'number'
                                        ? 'number'
                                        : 'text'
                                }
                            />
                        ),
                    )}
                {category === 'smtp' && (
                    <>
                        <Field
                            name="password"
                            type="password"
                            label="New SMTP password (leave blank to keep)"
                        />
                        <label>
                            <input
                                type="checkbox"
                                name="clear_password"
                                value="1"
                            />{' '}
                            Clear SMTP password
                        </label>
                    </>
                )}
                {['authentication', 'users', 'smtp'].includes(category) && (
                    <Field
                        name="current_password"
                        type="password"
                        label="Current password"
                    />
                )}
            </SubmitForm>
            {category === 'smtp' && (
                <SubmitForm
                    action="/admin/configuration/test-notification"
                    label="Send test notification"
                >
                    <Field name="email" type="email" required />
                </SubmitForm>
            )}
            <details>
                <summary>Emergency actions</summary>
                <p>
                    These actions can sign users out or restrict authentication.
                </p>
                <SubmitForm
                    action="/admin/configuration/emergency"
                    label="Run emergency action"
                    confirm="Run this emergency action?"
                >
                    <label>
                        Action
                        <Select
                            name="action"
                            value={emergency}
                            onValueChange={setEmergency}
                        >
                            <SelectTrigger>
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="logout">
                                    Log out accounts
                                </SelectItem>
                                <SelectItem value="lockdown">
                                    Lock down authentication
                                </SelectItem>
                                <SelectItem value="unlock">
                                    Unlock authentication
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </label>
                    {emergency === 'lockdown' && (
                        <Field name="reason" label="Lockdown reason" required />
                    )}
                    <Field name="current_password" type="password" required />
                </SubmitForm>
            </details>
        </Screen>
    );
}
