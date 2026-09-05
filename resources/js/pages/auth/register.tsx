import { Head, useForm } from '@inertiajs/react';
import { LoaderCircle } from 'lucide-react';
import { FormEventHandler } from 'react';

import InputError from '@/components/input-error';
import TextLink from '@/components/text-link';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AuthLayout from '@/layouts/auth-layout';

interface RegisterForm {
    [key: string]: string;
    nombre_1: string;
    nombre_2: string;
    apellido_1: string;
    apellido_2: string;
    cargo: string;
    rut: string;
    email: string;
    password: string;
    password_confirmation: string;
}

export default function Register() {
    const { data, setData, post, processing, errors, reset } = useForm<RegisterForm>({
        nombre_1: '',
        nombre_2: '',
        apellido_1: '',
        apellido_2: '',
        cargo: '',
        rut: '',
        email: '',
        password: '',
        password_confirmation: '',
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(route('register'), {
            onFinish: () => reset('password', 'password_confirmation'),
        });
    };

    return (
        <AuthLayout title="Create an account" description="Enter your details below to create your account">
            <Head title="Register" />
            <form className="flex flex-col gap-6" onSubmit={submit}>
                <div className="grid gap-6">
                    <div className="grid gap-4 md:grid-cols-2">
                        <div className="grid gap-2">
                            <Label htmlFor="nombre_1">Primer nombre</Label>
                            <Input
                                id="nombre_1"
                                type="text"
                                required
                                autoFocus
                                tabIndex={1}
                                value={data.nombre_1}
                                onChange={(e) => setData("nombre_1", e.target.value)}
                                disabled={processing}
                                placeholder="Juan"
                            />
                            <InputError message={errors.nombre_1} className="mt-2" />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="nombre_2">Segundo nombre</Label>
                            <Input
                                id="nombre_2"
                                type="text"
                                required
                                tabIndex={2}
                                value={data.nombre_2}
                                onChange={(e) => setData("nombre_2", e.target.value)}
                                disabled={processing}
                                placeholder="Carlos"
                            />
                            <InputError message={errors.nombre_2} />
                        </div>
                    </div>

                    <div className="grid gap-4 md:grid-cols-2">
                        <div className="grid gap-2">
                            <Label htmlFor="apellido_1">Primer apellido</Label>
                            <Input
                                id="apellido_1"
                                type="text"
                                required
                                tabIndex={3}
                                value={data.apellido_1}
                                onChange={(e) => setData("apellido_1", e.target.value)}
                                disabled={processing}
                                placeholder="Perez"
                            />
                            <InputError message={errors.apellido_1} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="apellido_2">Segundo apellido</Label>
                            <Input
                                id="apellido_2"
                                type="text"
                                required
                                tabIndex={4}
                                value={data.apellido_2}
                                onChange={(e) => setData("apellido_2", e.target.value)}
                                disabled={processing}
                                placeholder="Gonzalez"
                            />
                            <InputError message={errors.apellido_2} />
                        </div>
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="cargo">Cargo</Label>
                        <Input
                            id="cargo"
                            type="text"
                            required
                            tabIndex={5}
                            value={data.cargo}
                            onChange={(e) => setData("cargo", e.target.value)}
                            disabled={processing}
                            placeholder="Analista"
                        />
                        <InputError message={errors.cargo} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="rut">RUT</Label>
                        <Input
                            id="rut"
                            type="text"
                            required
                            tabIndex={6}
                            value={data.rut}
                            onChange={(e) => setData("rut", e.target.value)}
                            disabled={processing}
                            placeholder="12345678-9"
                        />
                        <InputError message={errors.rut} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="email">Email address</Label>
                        <Input
                            id="email"
                            type="email"
                            required
                            tabIndex={7}
                            autoComplete="email"
                            value={data.email}
                            onChange={(e) => setData('email', e.target.value)}
                            disabled={processing}
                            placeholder="email@example.com"
                        />
                        <InputError message={errors.email} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="password">Password</Label>
                        <Input
                            id="password"
                            type="password"
                            required
                            tabIndex={8}
                            autoComplete="new-password"
                            value={data.password}
                            onChange={(e) => setData('password', e.target.value)}
                            disabled={processing}
                            placeholder="Password"
                        />
                        <InputError message={errors.password} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="password_confirmation">Confirm password</Label>
                        <Input
                            id="password_confirmation"
                            type="password"
                            required
                            tabIndex={9}
                            autoComplete="new-password"
                            value={data.password_confirmation}
                            onChange={(e) => setData('password_confirmation', e.target.value)}
                            disabled={processing}
                            placeholder="Confirm password"
                        />
                        <InputError message={errors.password_confirmation} />
                    </div>

                    <Button type="submit" className="mt-2 w-full" tabIndex={10} disabled={processing}>
                        {processing && <LoaderCircle className="h-4 w-4 animate-spin" />}
                        Create account
                    </Button>
                </div>

                <div className="text-muted-foreground text-center text-sm">
                    Already have an account?{' '}
                    <TextLink href={route('login')} tabIndex={11}>
                        Log in
                    </TextLink>
                </div>
            </form>
        </AuthLayout>
    );
}
