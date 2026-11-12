import { createInertiaApp } from '@inertiajs/react';
import { Toaster } from '@/components/ui/sonner';
import { TooltipProvider } from '@/components/ui/tooltip';
import { initializeTheme } from '@/hooks/use-appearance';
import AppLayout from '@/layouts/app-layout';
import AuthLayout from '@/layouts/auth-layout';
import SettingsLayout from '@/layouts/settings/layout';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

void createInertiaApp({
    resolve: (name) => {
        const aliases: Record<string, string> = {
            Dashboard: 'dashboard',
            'auth/Login': 'auth/login',
            'auth/Register': 'auth/register',
            'auth/ForgotPassword': 'auth/forgot-password',
            'auth/ResetPassword': 'auth/reset-password',
            'auth/ConfirmPassword': 'auth/confirm-password',
            'auth/TwoFactorChallenge': 'auth/two-factor-challenge',
            'auth/VerifyEmail': 'auth/verify-email',
            'settings/Profile': 'settings/profile',
            'settings/Security': 'settings/security',
            'settings/Appearance': 'settings/appearance',
        };
        const pages = import.meta.glob('./pages/**/*.tsx');
        const target = name.startsWith('ui/components/')
            ? 'ui/ComponentPreview'
            : (aliases[name] ?? name);
        const load = pages[`./pages/${target}.tsx`];
        if (!load) throw new Error(`Unknown Inertia page: ${name}`);
        return load().then(
            (module) => (module as { default: React.ComponentType }).default,
        );
    },
    title: (title) => (title ? `${title} - ${appName}` : appName),
    layout: (name) => {
        switch (true) {
            case name === 'welcome' || name.startsWith('errors/'):
                return null;
            case name.startsWith('setup/'):
            case name.startsWith('auth/'):
                return AuthLayout;
            case name.startsWith('settings/'):
                return [AppLayout, SettingsLayout];
            default:
                return AppLayout;
        }
    },
    strictMode: true,
    withApp(app) {
        return (
            <TooltipProvider delayDuration={0}>
                {app}
                <Toaster />
            </TooltipProvider>
        );
    },
    progress: {
        color: '#4B5563',
    },
});

// This will set light / dark mode on load...
initializeTheme();
