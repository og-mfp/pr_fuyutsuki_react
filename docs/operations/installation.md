> **React port note:** Backend details below are adapted from the Svelte project and checked against the corresponding server implementation. React UI parity and deployment behavior have not been verified end to end; test the relevant flows in your environment.

# Installation and deployment safety

See the [environment variable reference](../environment-variables.md) for the purpose and usage of settings in `.env.example`.

Fuyutsuki inspects installation state before exposing first-time setup:

- `installed`: a user exists. A user always prevents reinstallation, even if a secondary marker is missing.
- `not_installed`: the database and required schema are reachable, with no users or installation markers. Setup may proceed after its normal prerequisite checks.
- `unknown`: database access or the required schema cannot be verified. Setup and registration return a safe HTTP 503 response.
- `inconsistent`: one or both installation markers exist without a user. Setup and registration return HTTP 503 until an authorized operator investigates.

Do not delete users, metadata, or the lock file to resolve an inconsistent state. Restore database access, inspect deployment history and backups, then use an authorized recovery procedure.

## Initial owner setup

The first successful setup request creates the installation owner. Until setup completes, anyone who can reach the prepared application's `/setup` page may attempt to create that owner account. Rate limiting and one-time installation state do not prove the request came from the operator.

For a new VPS or Coolify deployment, keep the app private while configuring it: restrict access with the firewall, VPN, temporary proxy access rule, or another operator-controlled network boundary. Configure persistent production secrets and database access, run migrations, complete `/setup` yourself, verify owner sign-in, then remove the temporary restriction and publish the application. Do not expose an initialized-but-unclaimed setup page publicly. If the deployment platform cannot restrict access before setup, use a separate private deployment window or network rule; do not rely on obscurity of the setup URL.

After installation, the application verifies database ownership metadata and a private lock file and refuses reinstall when inconsistent state is detected. Do not remove either installation marker to reopen setup.

## APP_KEY lifecycle

`composer setup` only prepares dependencies, a local `.env` when absent, and frontend assets. It never generates `APP_KEY` or runs migrations. For a verified fresh local environment, run these deliberate commands:

```sh
php artisan key:generate
php artisan migrate
```

CI uses the disposable `APP_KEY` in `phpunit.xml`. Staging and production must receive separate persistent keys through deployment secrets (for example, Coolify environment configuration). Never generate a key in an image build or routine redeployment, and never replace an unavailable key automatically. Key rotation is a separately authorized migration procedure because existing encrypted data depends on the old key.

## Production deployment checklist

- Set `APP_ENV=production`, `APP_DEBUG=false`, a public HTTPS `APP_URL`, and a unique persistent `APP_KEY` from the platform's secret store. Do not ship `.env` or credentials in an image or repository.
- Use PostgreSQL with a dedicated least-privileged application role. Keep database access private to the application network and configure persistent backups.
- Run `composer install --no-dev --optimize-autoloader`, install frontend dependencies from the lockfile, and build assets during the release/image build. Never copy development dependencies or build secrets into a public image.
- Run `php artisan migrate --force` as an explicit release step before exposing first-time setup. Do not run schema migrations on every web request or independently on every replica.
- Ensure the application user can write only the required Laravel `storage/` and `bootstrap/cache/` paths. Keep private uploads and `storage/app/private` outside the web root; publish only intended public assets.
- Terminate TLS at a trusted proxy, configure `TRUSTED_PROXIES` with only the actual proxy IPs/CIDRs (leave it unset for direct exposure), and ensure the proxy forwards HTTPS metadata correctly. Never use `*` or trust arbitrary client-supplied forwarded headers. Verify `request()->ip()` reports the actual client address.
- Configure a durable session/cache/queue strategy appropriate to the replica count. Run queue workers and the Laravel scheduler as supervised processes; restart workers after each deployment.
- Keep the service behind an operator-only network boundary until setup is complete and sign-in has been verified. Then publish it and verify `/up`, HTTPS cookies, login, and an authorized admin route. When TLS terminates at a proxy, set `SESSION_SECURE_COOKIE=true` and test the cookie flags through the public HTTPS URL.
- Monitor and retain application, deployment, database, and host authentication logs; test a backup restore before relying on a backup for recovery.

## Deployment packaging

This React checkout does not contain the source project's `compose.yaml` or `deploy/Dockerfile`. Provision the web server, PHP runtime, database, persistent storage, queue worker, scheduler, TLS and deployment secrets using your own tested deployment configuration. The source Compose recipe has not been ported or validated here.

For a fresh local checkout, `composer setup` installs dependencies, copies `.env.example` if needed and builds assets; it does not generate a key or migrate. After configuring the database, run the deliberate key generation and migration commands above, then `composer run dev` to start development services. Check `.env.example` and `composer.json` for current defaults and scripts.
