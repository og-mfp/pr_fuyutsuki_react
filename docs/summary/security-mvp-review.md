> **Historical Svelte source reference:** This is preserved from `pr_fuyutsuki`, not current React documentation. Examples, routes, dependencies, commands and conclusions below describe the source at the time of writing and have not been verified for this React application. Do not use them as React instructions.

# Security and MVP Review Summary

## Scope

Read-only security review of Laravel application security boundaries, Fortify authentication and rate limits, configurable RBAC/system settings, first-time setup, available deployment materials, CI dependency audit results, and the MVP criteria in `AGENTS.md`. Independent reviewers assessed SMTP SSRF, user-directory authorization, setup exposure, and Fortify throttling.

## Key findings

### High operational risk: publicly reachable first-time setup

The intended initial setup flow lets the first successful visitor create the installation owner. Until setup completes, anyone who can reach `/setup` may attempt to claim the owner account. One-time installation checks, locking, and rate limiting prevent repeat installs but do not establish that the requester is the operator.

Keep a fresh VPS/Coolify deployment behind an operator-only network boundary until migrations are complete, setup is performed by the operator, and owner sign-in is verified. The setup page is intended to be public before installation; this is an exposure risk if the uninitialized service is published too early, not evidence of a post-install setup bypass.

Evidence: `routes/web.php:27-30`; `app/Http/Controllers/FirstTimeSetupController.php:23-69`; `app/Services/InstallationService.php:31-52,89-112`; `tests/Feature/FirstTimeSetupTest.php:19-72,89-99,149-169,191-206`.

### Medium: Fortify public routes have no endpoint-specific abuse limits

Fortify routes use a broad `throttle:120,1` middleware (`config/fortify.php:104`). Login, two-factor, and passkey flows have their own stricter limiters, but registration, reset-link issuance, and password-reset submissions rely on the broad limit. Laravel's password-reset broker throttles repeated token issuance for one user (`config/auth.php:95-101`), but this does not bound requests across many addresses or invalid reset submissions. The impact depends on whether public registration is enabled and on email/account creation costs.

Recommendation: add tighter endpoint-specific request limits to public registration, reset-link requests, and reset submissions; retain existing login/MFA/passkey limits and the password broker throttle. No implementation was made because route-level integration was not validated in the installed Laravel/Fortify version.

Evidence: `config/fortify.php:104,117-121`; `app/Providers/FortifyServiceProvider.php:113-140`; `config/auth.php:95-101`; `app/Actions/Fortify/CreateNewUser.php:28-60`.

### MVP gap: no supported production VPS/Coolify deployment definition

No Dockerfile, Compose definition, or deployment guide tying together the production runtime, web server, worker/scheduler processes, persistent storage, and release steps was found. Operators must provide these pieces themselves. Manual guidance was added to `docs/operations/installation.md`, including production environment/secrets, PostgreSQL, release migrations, filesystem permissions, TLS and trusted proxies, durable session/cache/queue configuration, scheduler/worker supervision, initial setup isolation, logs, and backups.

This documentation does not constitute a tested deployment image or a verified end-to-end installation. A supported and exercised deployment recipe remains outstanding for the VPS/Coolify MVP criterion.

### Low: transitive `deepmerge` advisory

`pnpm audit --prod` reports one low-severity advisory for `deepmerge@4.3.1`, pulled in through `@sveltejs/vite-plugin-svelte`. The package manager reports no patched version. Composer reports no security advisories. Monitor upstream and avoid an unverified override.

### Data minimization: user-directory fields

`users.view` intentionally grants access to the paginated user directory; controller and tests establish this behavior. It includes names, emails, group membership, account status, and latest security-event metadata. No authorization bypass was demonstrated. Whether security-event fields should require a more restrictive permission is a product/RBAC policy decision.

### SMTP destination configuration

SMTP host configuration can target internal destinations, but only the installation owner may change system settings or invoke the rate-limited test notification. Internal SMTP relays may be required in supported deployments. The independent review did not establish this as an unauthenticated or lower-privilege SSRF. No host restriction was retained.

## Positive controls observed

- Admin routes are behind authentication, email verification, and `access-admin` authorization.
- System configuration is restricted to the recorded installation owner and sensitive changes require password confirmation.
- First-time setup validates prerequisites, serializes installation, records database ownership, and refuses reinstall after installation or inconsistent state.
- Production session cookies default to Secure; cookies are HttpOnly and SameSite=Lax.
- Profile avatars are validated, decoded, and re-encoded before storage; sensitive credentials are hidden from model serialization.
- Security-focused authentication, RBAC, setup, configuration, and upload tests exist.

## Verification and limitations

- `composer audit --no-interaction`: passed; no Composer advisories found.
- `pnpm audit --prod`: reports one low-severity transitive `deepmerge` advisory; no patched version reported.
- `pnpm run check`: blocked by formatting issues in the pre-existing modified `AGENTS.md`; that file was left untouched.
- PHP feature tests could not run: this environment lacks the SQLite PDO driver (`could not find driver`).
- `pnpm run build` was not run after the frontend check stopped.
- No live deployment, penetration test, or production proxy/cookie smoke test was performed.

## Files changed during this review

- `docs/operations/installation.md`: added first-time setup exposure warning and a manual production checklist, with explicit notes that this repository has no supported Docker/Compose deployment definition.
- An unverified SMTP host validation experiment was fully reverted. No application code change remains from that experiment.
- `AGENTS.md` already had a user modification and was not changed by this review.
