> **React port note:** Backend details below are adapted from the Svelte project and checked against the corresponding server implementation. React UI parity and deployment behavior have not been verified end to end; test the relevant flows in your environment.

# Application maintenance

## Managed maintenance workflow

The Owner plan operations at **Admin → System Health & Maintenance**. Each schedule requires an internal reason and a separate public announcement. Schedules store UTC timestamps; the interface accepts the configured application timezone. Published notices appear on sign-in and authenticated pages within the configured lead time (24 hours by default). Cancelled, completed, or elapsed schedules are not announced. The schedule never runs `down` or `up` automatically.

Create and save a schedule before running `maintenance:begin`. Use its database ID from the maintenance schedule list, and run the command in an environment connected to the same application database. `--override` only confirms execution outside the scheduled time; it does not create a window or bypass a missing ID.

Run commands inside the production application container or release container. In Coolify, open the application's **Terminal/Execute Command** for the running deployment, then run `php artisan maintenance:status` before acting.

```sh
php artisan maintenance:begin --window=42 --operator="Jane Doe"
# If outside scheduled window, explicitly confirm:
php artisan maintenance:begin --window=42 --override --operator="Jane Doe"
php artisan maintenance:status
php artisan maintenance:end --summary="Release deployed and smoke checks passed"
```

For planned and emergency begins, supply a non-secret human or ticket reference with `--operator`. It is self-declared and does not authenticate the CLI user. The OS account and SSH client IP are also logged when available. Completion keeps `--operator` optional so recovery cannot be blocked when identification is unavailable:

```sh
php artisan maintenance:begin --emergency --reason="Critical authentication security patch" --operator="INC-1234"
php artisan maintenance:end --summary="Patch deployed and sign-in verified" --operator="INC-1234"
```

### Emergency options, settings, and operator responsibilities

`--emergency` tells the command to create an emergency operation instead of selecting a planned window. `--reason` is required and kept in internal maintenance history; state the incident or work being done, without secrets or personal data. `--operator` is required for `maintenance:begin` and `maintenance:reconcile`, and accepts a non-secret staff name or incident/ticket ID. It is self-declared, not authenticated. For `maintenance:end`, it remains optional to preserve emergency recovery. `--override` applies only to planned windows and confirms early or late execution. These are Artisan options, not `.env` variables.

Emergency activation blocks normal application HTTP requests with Laravel's HTTP 503 response. It does not stop queue workers or scheduled jobs, and it does not guarantee every proxy or infrastructure endpoint is blocked. Coordinate worker/scheduler controls and proxy behavior separately when incident requires it. `/up` is a liveness endpoint and remains reachable through Laravel; it does not indicate readiness for user traffic.

For operator browser access, optionally add `--with-secret` to `maintenance:begin`. The command prints a bypass URL; treat it as a credential and share it only through a secure channel. Laravel sets a bypass cookie when the URL is visited. Authentication and authorization still apply. Ending maintenance invalidates the bypass and revokes all current login sessions and remember-me logins, including accounts that logged in through the bypass; users must sign in again. Native `up` does the same. A later operation generates a new bypass secret. Do not include the URL in logs or tickets. Proxy and server access logs may capture its path; restrict access to those logs or redact the path.

The public emergency page uses a fixed message, “We are performing emergency maintenance. Please try again shortly.” When a database record is available, its estimated end is one hour after activation. The page uses `health_maintenance.heading`, `health_maintenance.support_contact`, `general.display_name`, and `health_maintenance.retry_after`; fallback values are used if settings cannot load. `retry_after` is seconds for the HTTP `Retry-After` header; zero omits that header. The public message and one-hour estimate are not incident-specific, so communicate a more accurate status through an established status channel when needed. Do not put internal reason or sensitive details on the public page.

`APP_MAINTENANCE_DRIVER` and `APP_MAINTENANCE_STORE` are deployment environment variables controlling where Laravel stores maintenance state. Defaults are `file` and `database`. `file` affects one container only; use a genuinely shared cache store for multi-replica deployments, with the same driver, store, and namespace everywhere. Choose storage reachable during the incident; a PostgreSQL-backed store cannot activate or recover maintenance when PostgreSQL itself is unavailable. These variables do not control the public message or enable partial access.

Use emergency maintenance only when blocking normal requests is needed to contain an outage, protect data, or perform work that cannot safely run online. Follow incident authorization and escalation policy, record a truthful reason and operator/ticket reference, notify affected users through the status channel, and keep the outage no longer than necessary. Before activation, identify the recovery command and verify maintenance storage path. After containment or repair, run `maintenance:end` with a concise completion summary, then verify critical routes and background processing. If managed recovery cannot run, use documented break-glass `php artisan up` and record the action through incident/audit procedures. Emergency activation can proceed without a database history record if PostgreSQL is unavailable; operational logs then become especially important.

CLI transitions write Spatie activity events and update schedule history. If PostgreSQL is unavailable during emergency activation, the command logs a critical application event and activates native maintenance without a history row when the configured maintenance driver remains available; completion restores Laravel first, then reports recording failures. `php artisan down` and `php artisan up` remain available for break-glass recovery and bypass custom reason enforcement. Native file state is not shared across replicas. Cache-driver maintenance requires a genuinely shared cache store and namespace; if PostgreSQL backs that cache, it cannot contain an outage caused by PostgreSQL failure. Choose the file driver for database-independent recovery on a single container, or use a separate shared cache backend already operated by the deployment. For a single-container service whose currently running web process already uses file maintenance, emergency command remains usable without PostgreSQL. If every app replica uses a PostgreSQL-backed maintenance cache, use Coolify/server SSH to enable the proxy's static 503 page; changing only the CLI process to file would not block requests on web replicas still using the cache driver. Do not add PostgreSQL as the maintenance state store when PostgreSQL outage is in scope.

`maintenance:status` compares Laravel's actual state with `in_progress` records. It reports untracked activation, native/external completion, and conflicting records. `maintenance:reconcile --explanation="Native up used after rollback and health checks" --operator="INC-1234"` records a timestamped snapshot and mandatory explanation without changing availability or inventing a start/end time, reason, or actor. CLI reconciliation requires `--operator`; the Owner can record the same evidence from System Health & Maintenance after password confirmation. Reconciliation acknowledges observed discrepancy; it does not rewrite maintenance history or make the underlying states consistent. The original operation and native state remain visible as separate evidence.

`maintenance:status` exits with code 0 when inspection succeeds, including inconsistent states or unresolved incidents, and prints a warning when review is needed. It exits with code 1 when inspection is unavailable; automation must treat that as unknown, not operational. Managed begin and end run Laravel's maintenance command lifecycle, including the early maintenance bootstrap and framework events.

Maintenance Artisan commands, including `down` and `up`, write a completion entry to Laravel's application log with the command, exit code, CLI source, operator identifier when supplied, OS account when available, and SSH client IP when available. Raw command arguments and secrets are not logged. Missing SSH connection metadata leaves the IP empty. This is supplementary evidence only; logging can be unavailable, and commands outside Artisan are not covered. Production operators must enable Coolify audit/activity logging for terminal and deployment access, retain VPS SSH authentication logs (`journald`/`auth.log`) and `sudo` records, use named individual accounts, and forward logs off-host with retention and restricted access. Shell history alone is not a sufficient audit trail.

When the application or database cannot start, use Coolify's container terminal/release task or server SSH to access a healthy application container. Restore service at infrastructure level if no app container can boot. Configure the reverse proxy/load balancer with a static HTTP 503 maintenance page for complete application, database, or shared-cache outages; application-rendered error pages cannot cover a stopped application container. Scheduler cannot operate after its container stops.

`maintenance:begin` embeds escaped public content in Laravel's native maintenance response before activation. The response is self-contained HTML and makes no database query. It returns HTTP 503 with the configured retry interval (300 seconds by default; zero omits `Retry-After`). If settings cannot load, command uses static defaults and 300 seconds. The snapshot is taken at activation; changing announcement text does not change an already active response.

Authentication lockdown only blocks authentication and revokes sessions according to its own policy. It is not application maintenance and does not activate Laravel's request-blocking maintenance mode.

Laravel maintenance mode blocks normal HTTP requests and returns HTTP 503. Fuyutsuki uses Laravel's maintenance repository; the System Health & Maintenance page reports its status. Web activation and deactivation are intentionally disabled, so a lost administrator session cannot strand operators.

## Configure the maintenance driver

Laravel defaults to the file driver. It writes `storage/framework/down` inside one container, so the state is lost when that container is replaced and is not shared by multiple replicas. For Docker or Coolify deployments with more than one app container, configure a shared Laravel cache store:

```dotenv
APP_MAINTENANCE_DRIVER=cache
APP_MAINTENANCE_STORE=redis
```

Redis is optional; installations without it continue to use their existing configured drivers. For shared state and recovery during a PostgreSQL outage, use an available shared non-PostgreSQL cache store such as Redis. All replicas must use the same maintenance store and cache namespace; the managed operation lock uses that store too. A configured but unavailable store prevents managed transitions rather than falling back to a different lock. A cache connectivity or maintenance repository error is shown as unavailable in the admin overview, not operational. `/up` is a liveness endpoint, not a database or cache readiness check; proxy behavior must be verified separately.

## Break-glass recovery

Normal managed activation uses `maintenance:begin` so Laravel captures the public page and records the operation. Privileged shell operators can still use native commands directly; these bypass the managed reason and history safeguards:

```sh
php artisan down --render="errors::maintenance" --retry=300
php artisan up
```

Use native `up` when managed recovery cannot run. With the file driver, recovery is independent of PostgreSQL. With the cache driver, the configured maintenance cache store must be reachable. For file-driver multi-replica deployments, issue native commands on each replica or move to a genuinely shared cache. A replaced file-driver container may start operational while another replica remains down. If no application container can boot, recover the container or maintenance store through Coolify/server SSH and use an infrastructure-level static 503 page during the outage. Native commands are not written to managed history; retain deployment logs for break-glass actions.

## Planned PostgreSQL release example

1. Confirm the release image, database connectivity, shared cache, and a tested recovery command.
2. Announce the maintenance window in Health & Maintenance, then save settings with current-password confirmation.
3. Run `php artisan maintenance:begin --window=<id> --operator="<name-or-ticket>"` in the release container. Use `--override` only when operator confirms early or late execution.
4. Apply the forward PostgreSQL migration with `php artisan migrate --force`. Review migration locks, duration, and compatibility with the currently deployed code before starting. Prefer expand/contract changes when the migration cannot safely run while old code is present.
5. Deploy the compatible application image, verify database/cache/queue configuration, then run `php artisan maintenance:end --summary="Schema update deployed and verified"`.
6. Verify `/up`, public pages, sign-in, an authorized admin route, and worker/scheduler operation. Re-enable maintenance through Artisan if checks fail; diagnose and prepare a forward fix. Do not automatically roll back irreversible database migrations.

Queue workers do not stop merely because HTTP requests are in maintenance mode. Pause or drain workers and scheduler-triggered work using the deployment's existing process controls when the migration requires it, then restart workers after deployment so they load the new code. Maintenance mode is not an application-wide read-only switch. Direct `down` and `up` remain break-glass commands and are not recorded in the managed history.

## Troubleshooting and verification

- If the public page is generic, verify the maintenance activation snapshot was generated and the fallback view is available.
- If replicas disagree, verify `APP_MAINTENANCE_DRIVER=cache`, the same `APP_MAINTENANCE_STORE`, cache namespace, and connectivity on each replica.
- If Laravel cannot boot, run `php artisan up` in a working container with the same environment. If the database is down, this is normally independent of the database when using the file driver; cache-driver recovery needs the cache backend.
- Never expose `storage/framework/down`, maintenance secrets, environment values, or deployment paths publicly.
- Check the admin health overview after boot. It reports Laravel's maintenance repository, database `select 1`, and a read-only cache lookup. Queue driver is configuration only; a database queue backend is checked with `select 1`, while other backends and all queue workers remain `not verified` without dedicated monitoring.
- Confirm the response is HTTP 503, includes configured `Retry-After` when nonzero, and displays escaped public text. Confirm standard application routes are blocked.
