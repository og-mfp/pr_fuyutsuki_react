> **React port note:** Backend details below are adapted from the Svelte project and checked against the corresponding server implementation. React UI parity and deployment behavior have not been verified end to end; test the relevant flows in your environment.

# Application configuration

`/admin/configuration` is restricted by the `manage-system-settings` Gate to the active installation owner. Both GET and PUT routes check the Gate. Authentication and registration role updates require the current password by default. A configured reauthentication window allows later sensitive configuration saves in the same session; emergency actions always require the current password.

`app_settings` stores JSONB values. `SettingsRegistry` lists accepted keys, types, defaults, categories, validation rules and sensitive status. `SettingsService` reads sensitive settings and the security-alert switch fresh once per request. Other settings are cached for up to 60 seconds and invalidated after updates commit. It reads defaults before the table exists, validates and writes updates transactionally, and records each change in the existing activity log. Installation calls `initialize()` once inside the setup transaction; it uses `firstOrCreate`, so repeated calls preserve edits. Deployments must run migrations before first-time setup.

Derived applications can register keys in `SettingsRegistry` using `register(key, category, type, default, rules, sensitive)`. Only register values with an actual backend consumer. Add a category form and enforce the key in the owning service or action. Server-side authorization remains independent of feature settings.

SMTP settings are owner-controlled and require password confirmation. The SMTP password is encrypted using Laravel's application key and excluded from page data and audit values. Enabling SMTP overrides the deployment mail transport; disabling it restores deployment configuration. Restart long-running queue workers after changing mail settings so they reload the transport. Third-party mailing services are reserved for future integration.

Authentication & Security controls MFA availability and enrollment requirements, password length and strength, reset link expiry, login attempts and cooldown, email verification, and the sensitive configuration reauthentication window. The emergency actions revoke database sessions and remember tokens for non-super administrators. Lockdown also blocks their authentication and requires a reason shown on the login page until a super administrator ends lockdown. Session revocation requires the database session driver. Registration has `public`, `invitation_only`, and `administrator_created` values; the Fortify registration view only opens in public mode. Invitation and administrator-created registration workflows require further verification before use. The default registration role must be a nonprivileged group without permissions.

Authentication & Security also configures the [authentication risk engine](authentication-risk-engine.md): source and account thresholds, monitor/enforce mode, step-up challenges, trusted devices, and browser consistency. Monitor mode and disabled step-up are the defaults. Existing password-confirmation and Owner controls apply to every risk setting.

Remaining work: invitations, account approval, security alerts, audit retention, notifications, storage limits, maintenance, diagnostics, feature and integration registries, and privacy retention. These require backend enforcement before controls can be offered.

## Health status

Health & Maintenance distinguishes queue configuration from evidence. Queue driver reports configuration; database queue backend is `reachable` only after a harmless database query. Other asynchronous backends are `not verified` because the application does not send jobs or probe provider credentials from a dashboard request. Queue workers are `not verified` unless reliable worker monitoring exists. `not verified` is not a healthy state. Cache status reports a read check only, and Laravel's `/up` endpoint remains liveness rather than dependency readiness.
