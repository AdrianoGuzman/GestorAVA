import { type BreadcrumbItem, type SharedData } from '@/types';
import { Transition } from '@headlessui/react';
import { Head, Link, useForm, usePage } from '@inertiajs/react';
import { FormEventHandler } from 'react';

import DeleteUser from '@/components/delete-user';
import HeadingSmall from '@/components/heading-small';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import SettingsLayout from '@/layouts/settings/layout';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Profile settings',
        href: '/settings/profile',
    },
];

export default function Profile({ mustVerifyEmail, status }: { mustVerifyEmail: boolean; status?: string }) {
    const { auth } = usePage<SharedData>().props;

    const { data, setData, patch, errors, processing, recentlySuccessful } = useForm({
        nombre_1: auth.user.nombre_1,
        nombre_2: auth.user.nombre_2,
        apellido_1: auth.user.apellido_1,
        apellido_2: auth.user.apellido_2,
        email: auth.user.email,
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();

        patch(route('profile.update'));
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Profile settings" />

            <SettingsLayout>
                <div className="space-y-6">
                    <HeadingSmall title="Profile information" description="Update your name and email address" />

                    <form onSubmit={submit} className="space-y-6">
                        <div className="grid gap-4 md:grid-cols-2">
                            <div className="grid gap-2">
                                <Label htmlFor="nombre_1">Primer nombre</Label>
                                <Input
                                    id="nombre_1"
                                    className="mt-1 block w-full"
                                    value={data.nombre_1}
                                    onChange={(e) => setData('nombre_1', e.target.value)}
                                    required
                                    autoComplete="given-name"
                                />
                                <InputError className="mt-2" message={errors.nombre_1} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="nombre_2">Segundo nombre</Label>
                                <Input
                                    id="nombre_2"
                                    className="mt-1 block w-full"
                                    value={data.nombre_2}
                                    onChange={(e) => setData('nombre_2', e.target.value)}
                                    required
                                />
                                <InputError className="mt-2" message={errors.nombre_2} />
                            </div>
                        </div>

                        <div className="grid gap-4 md:grid-cols-2">
                            <div className="grid gap-2">
                                <Label htmlFor="apellido_1">Primer apellido</Label>
                                <Input
                                    id="apellido_1"
                                    className="mt-1 block w-full"
                                    value={data.apellido_1}
                                    onChange={(e) => setData('apellido_1', e.target.value)}
                                    required
                                    autoComplete="family-name"
                                />
                                <InputError className="mt-2" message={errors.apellido_1} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="apellido_2">Segundo apellido</Label>
                                <Input
                                    id="apellido_2"
                                    className="mt-1 block w-full"
                                    value={data.apellido_2}
                                    onChange={(e) => setData('apellido_2', e.target.value)}
                                    required
                                />
                                <InputError className="mt-2" message={errors.apellido_2} />
                            </div>
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="email">Email address</Label>

                            <Input
                                id="email"
                                type="email"
                                className="mt-1 block w-full"
                                value={data.email}
                                onChange={(e) => setData('email', e.target.value)}
                                required
                                autoComplete="username"
                                placeholder="Email address"
                            />

                            <InputError className="mt-2" message={errors.email} />
                        </div>

                        {mustVerifyEmail && auth.user.email_verified_at === null && (
                            <div>
                                <p className="mt-2 text-sm text-neutral-800">
                                    Your email address is unverified.
                                    <Link
                                        href={route('verification.send')}
                                        method="post"
                                        as="button"
                                        className="rounded-md text-sm text-neutral-600 underline hover:text-neutral-900 focus:ring-2 focus:ring-offset-2 focus:outline-hidden"
                                    >
                                        Click here to re-send the verification email.
                                    </Link>
                                </p>

                                {status === 'verification-link-sent' && (
                                    <div className="mt-2 text-sm font-medium text-green-600">
                                        A new verification link has been sent to your email address.
                                    </div>
                                )}
                            </div>
                        )}

                        <div className="flex items-center gap-4">
                            <Button disabled={processing}>Save</Button>

                            <Transition
                                show={recentlySuccessful}
                                enter="transition ease-in-out"
                                enterFrom="opacity-0"
                                leave="transition ease-in-out"
                                leaveTo="opacity-0"
                            >
                                <p className="text-sm text-neutral-600">Saved</p>
                            </Transition>
                        </div>
                    </form>
                </div>

                <DeleteUser />
            </SettingsLayout>
        </AppLayout>
    );
}
