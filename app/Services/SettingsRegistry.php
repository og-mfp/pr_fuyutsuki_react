<?php

namespace App\Services;

use Illuminate\Validation\Rule;
use InvalidArgumentException;

class SettingsRegistry
{
    /** @var array<string, array{category: string, type: string, default: mixed, rules: array<int, mixed>, sensitive: bool}> */
    private array $definitions = [];

    public function __construct()
    {
        foreach ([
            'general' => [
                'display_name' => ['string', config('app.name'), ['required', 'string', 'max:100']],
                'description' => ['string', '', ['nullable', 'string', 'max:500']],
                'logo' => ['string', '', ['nullable', 'string', 'max:2048']],
                'favicon' => ['string', '', ['nullable', 'string', 'max:2048']],
                'support_contact' => ['string', '', ['nullable', 'string', 'max:255']],
                'default_landing_page' => ['string', 'welcome', ['required', Rule::in(['welcome', 'dashboard'])]],
                'admin_theme' => ['string', 'system', ['required', Rule::in(['light', 'dark', 'system'])]],
            ],
            'authentication' => [
                'mfa_enabled' => ['boolean', true, ['required', 'boolean']],
                'mfa_requirement' => ['string', 'optional', ['required', Rule::in(['optional', 'required'])]],
                'password_policy' => ['string', app()->isProduction() ? 'strong' : 'standard', ['required', Rule::in(['standard', 'strong'])]],
                'password_min_length' => ['integer', app()->isProduction() ? 12 : 8, ['required', 'integer', 'between:'.(app()->isProduction() ? 12 : 8).',128']],
                'password_reset_expiration_minutes' => ['integer', 60, ['required', 'integer', 'between:1,1440']],
                'login_max_attempts' => ['integer', 5, ['required', 'integer', 'between:1,100']],
                'account_lockout_minutes' => ['integer', 1, ['required', 'integer', 'between:1,1440']],
                'ip_login_threshold' => ['integer', 30, ['required', 'integer', 'between:20,300']],
                'account_failure_threshold' => ['integer', 10, ['required', 'integer', 'between:5,100']],
                'risk_window_minutes' => ['integer', 10, ['required', 'integer', 'between:5,60']],
                'risk_mode' => ['string', 'monitor', ['required', Rule::in(['monitor', 'enforce'])]],
                'risk_step_up' => ['boolean', false, ['required', 'boolean']],
                'medium_risk_threshold' => ['integer', 30, ['required', 'integer', 'between:20,60']],
                'high_risk_threshold' => ['integer', 70, ['required', 'integer', 'between:61,100']],
                'trusted_devices_enabled' => ['boolean', true, ['required', 'boolean']],
                'trusted_device_days' => ['integer', 30, ['required', 'integer', 'between:1,90']],
                'browser_signals' => ['boolean', false, ['required', 'boolean']],
                'reauthentication_minutes' => ['integer', 0, ['required', 'integer', 'between:0,120']],
                'session_idle_timeout_minutes' => ['integer', 120, ['required', 'integer', 'between:1,10080']],
                'concurrent_sessions' => ['boolean', false, ['required', 'boolean']],
                'authentication_lockdown' => ['boolean', false, ['required', 'boolean']],
                'lockdown_reason' => ['string', '', ['nullable', 'string', 'max:500']],
            ],
            'users' => [
                'registration_mode' => ['string', 'public', ['required', Rule::in(['public', 'invitation_only', 'administrator_created'])]],
                'registration_approval_required' => ['boolean', false, ['required', 'boolean']],
                'email_confirmation_required' => ['boolean', false, ['required', 'boolean']],
                'inactivity_warning_threshold_days' => ['integer', 90, ['required', 'integer', 'between:1,3650']],
                'default_role' => ['string', 'std-usr', ['required', 'string', Rule::exists('groups', 'slug')->where(fn ($query) => $query->whereNotIn('slug', ['owner'])->whereNotIn('id', fn ($groups) => $groups->select('rbac_roles.group_id')->from('rbac_roles')->join('role_permission', 'rbac_roles.id', '=', 'role_permission.role_id')))]],
            ],
            'smtp' => [
                'enabled' => ['boolean', false, ['required', 'boolean']],
                'host' => ['string', '127.0.0.1', ['required', 'string', 'max:255']],
                'port' => ['integer', 587, ['required', 'integer', 'between:1,65535']],
                'scheme' => ['string', 'smtp', ['required', Rule::in(['smtp', 'smtps'])]],
                'username' => ['string', '', ['nullable', 'string', 'max:255']],
                'password' => ['string', '', ['nullable', 'string', 'max:1024']],
                'from_address' => ['string', config('mail.from.address'), ['required', 'email', 'max:255']],
            ],
            'notifications' => [
                'sender_name' => ['string', config('app.name'), ['required', 'string', 'max:100']],
                'reply_to' => ['string', '', ['nullable', 'email', 'max:255']],
                'default_enabled' => ['boolean', true, ['required', 'boolean']],
                'security_enabled' => ['boolean', true, ['required', 'boolean']],
                'template_subject' => ['string', 'Application notification', ['required', 'string', 'max:200']],
                'template_body' => ['string', 'This is a notification from your application.', ['required', 'string', 'max:10000']],
            ],
            'health_maintenance' => [
                'heading' => ['string', 'We will be back soon', ['required', 'string', 'max:160']],
                'description' => ['string', 'The application is temporarily unavailable while maintenance is in progress.', ['required', 'string', 'max:1000']],
                'support_contact' => ['string', '', ['nullable', 'string', 'max:255']],
                'scheduled_window' => ['string', '', ['nullable', 'string', 'max:255']],
                'retry_after' => ['integer', 300, ['required', 'integer', 'between:0,86400']],
                'announcement_lead_hours' => ['integer', 24, ['required', 'integer', 'between:1,720']],
            ],
        ] as $category => $settings) {
            foreach ($settings as $key => [$type, $default, $rules]) {
                $this->register($category.'.'.$key, $category, $type, $default, $rules, in_array($category, ['authentication', 'users', 'security', 'health_maintenance', 'smtp'], true));
            }
        }
    }

    /** @param array<int, mixed> $rules */
    public function register(string $key, string $category, string $type, mixed $default, array $rules, bool $sensitive = false): void
    {
        if (isset($this->definitions[$key]) || ! preg_match('/^[a-z][a-z0-9_.]*$/', $key) || ! in_array($type, ['string', 'integer', 'boolean'], true)) {
            throw new InvalidArgumentException('Invalid or duplicate setting definition.');
        }
        $this->definitions[$key] = compact('category', 'type', 'default', 'rules', 'sensitive');
    }

    /** @return array{category: string, type: string, default: mixed, rules: array<int, mixed>, sensitive: bool} */
    public function definition(string $key): array
    {
        return $this->definitions[$key] ?? throw new InvalidArgumentException("Unknown setting: {$key}");
    }

    /** @return array<string, array{category: string, type: string, default: mixed, rules: array<int, mixed>, sensitive: bool}> */
    public function all(): array
    {
        return $this->definitions;
    }
}
