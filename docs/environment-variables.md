> **React port note:** Backend details below are adapted from the Svelte project and checked against the corresponding server implementation. React UI parity and deployment behavior have not been verified end to end; test the relevant flows in your environment.

# Environment variables

`.env.example` contains the local defaults. Copy it to `.env` for local use; production deployments should set secrets and environment-specific values through a secret manager or platform configuration. Commented variables below are optional overrides unless noted.

## Required and dashboard-overridable values

“Required” depends on how the app is deployed. Values marked **Required** must be supplied for every installation; **Production required** applies to production; **Conditional** applies only when using that service or feature. Other values use application defaults unless you choose to override them.

| Variable | Requirement |
| --- | --- |
| `APP_KEY` | **Required.** Generate once for a new installation and keep it persistent. |
| `APP_ENV`, `APP_DEBUG`, `APP_URL` | **Production required.** Set `production`, `false`, and the public HTTPS URL. |
| `DB_CONNECTION`, `DB_DATABASE` | **Required.** Select a database driver and database/file. |
| `DB_HOST`, `DB_PORT`, `DB_USERNAME`, `DB_PASSWORD` | **Conditional.** Required for network database connections; not needed for SQLite. |
| `AWS_DEFAULT_REGION`, `AWS_BUCKET` | **Conditional.** Required when attachment storage uses S3. Credentials may be supplied by the platform instead. |
| `MAIL_HOST`, `MAIL_FROM_ADDRESS` | **Conditional.** Required in deployment when SMTP is configured through the environment. |

Some dashboard settings take precedence over matching deployment defaults:

| Dashboard setting | Environment values affected |
| --- | --- |
| General → Display name | `APP_NAME` as shown in the application UI. `APP_NAME` remains the default and is still used by other framework integrations. |
| SMTP → Enabled and SMTP details | `MAIL_MAILER`, `MAIL_HOST`, `MAIL_PORT`, `MAIL_SCHEME`, `MAIL_USERNAME`, `MAIL_PASSWORD`, and `MAIL_FROM_ADDRESS`. When dashboard SMTP is enabled, it configures the app's SMTP mailer. |
| Notifications → Sender name and Reply-to | `MAIL_FROM_NAME` and transport-level reply-to behavior for application mail. |

Configure required database, encryption, URL, storage, and deployment secrets in the environment; the dashboard does not replace those service connections. Dashboard configuration is stored in the database and is managed separately from deployment secrets. See [application configuration](application-configuration.md) for its scope.

## Application

| Variable | Purpose |
| --- | --- |
| `APP_NAME` | Application name shown in the UI and mail. |
| `APP_ENV` | Environment name; use `production` in production. |
| `APP_KEY` | Laravel encryption key. Required; generate once for a new installation and keep persistent. |
| `APP_DEBUG` | Controls detailed error output. Must be `false` in production. |
| `APP_URL` | Canonical application URL, including scheme. |
| `ERROR_CODE_PREFIX` | Optional prefix for diagnostic error identifiers. |
| `TRUSTED_PROXIES` | Optional comma-separated trusted proxy IPs/CIDRs. Set only to actual proxies. |
| `APP_LOCALE` | Default application language. |
| `APP_FALLBACK_LOCALE` | Language used when a translation is missing. |
| `APP_FAKER_LOCALE` | Optional locale for generated development/test data. |
| `APP_MAINTENANCE_DRIVER` | Storage driver for Laravel maintenance mode. |
| `APP_MAINTENANCE_STORE` | Optional cache store when the maintenance driver requires one. |
| `PHP_CLI_SERVER_WORKERS` | Optional worker count for PHP's built-in development server. |
| `BCRYPT_ROUNDS` | Work factor used when hashing passwords. |

## Logging and database

| Variable | Purpose |
| --- | --- |
| `LOG_CHANNEL` | Default Laravel log channel. |
| `LOG_STACK` | Optional channels included by the `stack` log channel. |
| `LOG_DEPRECATIONS_CHANNEL` | Optional channel for deprecation notices. |
| `LOG_LEVEL` | Minimum severity written to logs. |
| `DB_CONNECTION` | Database driver (`sqlite`, `pgsql`, `mysql`, etc.). The example uses SQLite. |
| `DB_URL` | Optional full database connection URL; takes precedence when configured. |
| `DB_HOST`, `DB_PORT` | Database server address and port for network databases. |
| `DB_DATABASE` | Database name or SQLite file path, depending on the driver. |
| `DB_USERNAME`, `DB_PASSWORD` | Database credentials for network databases. |
| `DB_SOCKET` | Optional database Unix socket path. |
| `DB_CHARSET`, `DB_COLLATION` | Optional character set and collation for supported drivers. |
| `DB_FOREIGN_KEYS` | Optional SQLite foreign-key enforcement setting. |

## Sessions, cache, queues, and files

| Variable | Purpose |
| --- | --- |
| `SESSION_DRIVER` | Session storage backend. The app uses database-backed sessions by default. |
| `SESSION_LIFETIME` | Optional idle session lifetime in minutes. |
| `SESSION_ENCRYPT` | Optional encryption of stored session data. |
| `SESSION_PATH`, `SESSION_DOMAIN` | Optional cookie path/domain scope. Leave domain unset for the usual host-only cookie. |
| `SESSION_SECURE_COOKIE` | Optional secure-cookie override; production configuration already requires HTTPS cookies. |
| `SESSION_CONNECTION`, `SESSION_TABLE`, `SESSION_STORE` | Optional connection, table, or cache store for session storage. |
| `SESSION_EXPIRE_ON_CLOSE`, `SESSION_COOKIE`, `SESSION_HTTP_ONLY`, `SESSION_SAME_SITE`, `SESSION_PARTITIONED_COOKIE` | Optional session-cookie behavior overrides. Keep HTTP-only and same-site protections enabled. |
| `BROADCAST_CONNECTION` | Event broadcasting backend. The example logs broadcasts. |
| `CACHE_STORE` | Default cache backend. |
| `CACHE_PREFIX` | Optional prefix to separate this application's cache keys. |
| `QUEUE_CONNECTION` | Queue backend. |
| `FILESYSTEM_DISK` | Default Laravel filesystem disk. |
| `ATTACHMENT_STORAGE_MODE` | Attachment storage mode: `LOCAL` or `S3`. `LOCAL` uses the public local disk. |
| `MEMCACHED_HOST`, `MEMCACHED_PORT`, `MEMCACHED_USERNAME`, `MEMCACHED_PASSWORD`, `MEMCACHED_PERSISTENT_ID` | Optional Memcached connection settings; needed only when using Memcached. |
| `REDIS_CLIENT`, `REDIS_HOST`, `REDIS_PORT`, `REDIS_USERNAME`, `REDIS_PASSWORD`, `REDIS_URL` | Optional Redis connection settings; needed only when using Redis. |
| `REDIS_DB`, `REDIS_CACHE_DB`, `REDIS_PREFIX`, `REDIS_CLUSTER`, `REDIS_PERSISTENT` | Optional Redis database and client behavior overrides. |

## Mail and S3

| Variable | Purpose |
| --- | --- |
| `MAIL_MAILER` | Mail transport. The example uses `log`, so messages are written to logs. |
| `MAIL_SCHEME`, `MAIL_HOST`, `MAIL_PORT` | SMTP transport scheme and server address. Configure these when using SMTP. |
| `MAIL_USERNAME`, `MAIL_PASSWORD` | Optional SMTP credentials. |
| `MAIL_FROM_ADDRESS`, `MAIL_FROM_NAME` | Sender identity for application mail. |
| `MAIL_URL`, `MAIL_EHLO_DOMAIN`, `MAIL_SENDMAIL_PATH`, `MAIL_LOG_CHANNEL` | Optional transport-specific mail settings. |
| `AWS_ACCESS_KEY_ID`, `AWS_SECRET_ACCESS_KEY` | Credentials for S3-compatible storage when required by the provider. Prefer platform-managed credentials where possible. |
| `AWS_DEFAULT_REGION`, `AWS_BUCKET` | S3 region and bucket. Required when `ATTACHMENT_STORAGE_MODE=S3`. |
| `AWS_URL` | Optional public URL or CDN base for S3 objects. Configure bucket access appropriately for public branding images. |
| `AWS_ENDPOINT` | Optional custom S3-compatible endpoint. |
| `AWS_USE_PATH_STYLE_ENDPOINT` | Optional path-style addressing for compatible storage services. |

## Audit, frontend, and deployment

| Variable | Purpose |
| --- | --- |
| `AUDIT_RETENTION_DAYS` | Optional retention period for general activity logs. |
| `AUDIT_SECURITY_RETENTION_DAYS` | Optional retention period for security audit logs. |
| `VITE_APP_NAME` | Application name embedded in frontend assets; falls back to `Laravel` in `resources/js/app.tsx`; `.env.example` sets it from `APP_NAME`. |

For production setup and key handling, see [installation and deployment safety](operations/installation.md). The canonical variable list and example defaults are maintained in [`.env.example`](../.env.example).
