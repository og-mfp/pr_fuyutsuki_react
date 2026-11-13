import { Form, Head, usePage } from '@inertiajs/react';
import { Link } from '@inertiajs/react';
import ProfileController from '@/actions/App/Http/Controllers/Settings/ProfileController';
import { SecurityFormGuard } from '@/components/migration/security-check';
import { Field, SubmitForm } from '@/components/migration/common';
import DeleteUser from '@/components/delete-user';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { edit } from '@/routes/profile';
import type { Auth } from '@/types';
import { send } from '@/routes/verification';

type PageProps = {
    auth: Auth;
};

export default function Profile({
    status,
    personalInformation,
}: {
    status?: string;
    personalInformation?: Record<string, string>;
}) {
    const { auth } = usePage<PageProps>().props;

    return (
        <>
            <Head title="Profile settings" />

            <h1 className="sr-only">Profile settings</h1>

            <div className="space-y-6">
                <Heading
                    variant="small"
                    title="Profile"
                    description="Update your name and email address"
                />

                <SecurityFormGuard
                    action="profile.update"
                    fields={['name', 'email']}
                    skip={(data) => data.get('email') === auth.user.email}
                >
                    <Form
                        {...ProfileController.update.form()}
                        options={{
                            preserveScroll: true,
                        }}
                        className="space-y-6"
                    >
                        {({ processing, errors }) => (
                            <>
                                <div className="grid gap-2">
                                    <Label htmlFor="name">Name</Label>

                                    <Input
                                        id="name"
                                        className="mt-1 block w-full"
                                        defaultValue={auth.user.name}
                                        name="name"
                                        required
                                        autoComplete="name"
                                        placeholder="Full name"
                                    />

                                    <InputError
                                        className="mt-2"
                                        message={errors.name}
                                    />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="email">Email address</Label>

                                    <Input
                                        id="email"
                                        type="email"
                                        className="mt-1 block w-full"
                                        defaultValue={auth.user.email}
                                        name="email"
                                        required
                                        autoComplete="username"
                                        placeholder="Email address"
                                    />

                                    <InputError
                                        className="mt-2"
                                        message={errors.email}
                                    />
                                </div>

                                {auth.user.email_verified_at === null && (
                                    <div>
                                        <p className="-mt-4 text-sm text-muted-foreground">
                                            Your email address is unverified.{' '}
                                            <Link
                                                href={send()}
                                                method="post"
                                                as="button"
                                                className="text-foreground underline decoration-neutral-300 underline-offset-4 transition-colors duration-300 ease-out hover:decoration-current! dark:decoration-neutral-500"
                                            >
                                                Click here to re-send the
                                                verification email.
                                            </Link>
                                        </p>

                                        {status ===
                                            'verification-link-sent' && (
                                            <div className="mt-2 text-sm font-medium text-green-600">
                                                A new verification link has been
                                                sent to your email address.
                                            </div>
                                        )}
                                    </div>
                                )}

                                <div className="flex items-center gap-4">
                                    <Button
                                        disabled={processing}
                                        data-test="update-profile-button"
                                    >
                                        Save
                                    </Button>
                                </div>
                            </>
                        )}
                    </Form>
                </SecurityFormGuard>
            </div>

            <section className="space-y-4">
                <Heading
                    variant="small"
                    title="Personal information"
                    description="Update your personal details."
                />
                <SubmitForm
                    action="/settings/profile/personal-information"
                    method="patch"
                >
                    {[
                        'full_name',
                        'date_of_birth',
                        'address',
                        'primary_phone',
                        'secondary_phone',
                    ].map((field) => (
                        <Field
                            key={field}
                            name={field}
                            type={field === 'date_of_birth' ? 'date' : 'text'}
                            value={personalInformation?.[field]}
                        />
                    ))}
                </SubmitForm>
            </section>
            <section className="space-y-4">
                <Heading
                    variant="small"
                    title="Avatar"
                    description="Upload a JPEG, PNG, or WebP image up to 2 MB and 1024 pixels."
                />
                <SubmitForm
                    action="/settings/profile/avatar"
                    label="Upload avatar"
                >
                    <input
                        type="file"
                        name="avatar"
                        accept="image/jpeg,image/png,image/webp"
                        required
                    />
                </SubmitForm>
                {auth.user.avatar && (
                    <SubmitForm
                        action="/settings/profile/avatar"
                        method="delete"
                        label="Remove avatar"
                        confirm="Remove your avatar?"
                    />
                )}
            </section>
            <DeleteUser />
        </>
    );
}

Profile.layout = {
    breadcrumbs: [
        {
            title: 'Profile settings',
            href: edit(),
        },
    ],
};
