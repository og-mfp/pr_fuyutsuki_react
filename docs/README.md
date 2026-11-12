# Documentation

This directory adapts backend and operations documentation from the sibling `pr_fuyutsuki` Svelte application to this Laravel/Inertia React checkout. The server implementations are substantially shared, but the React interface and deployment have not been verified end to end. Consult the implementation and test the relevant workflows before relying on a procedure in production.

## Current backend and operations

- [Installation and deployment safety](operations/installation.md)
- [Environment variables](environment-variables.md)
- [Application configuration](application-configuration.md)
- [Authorization and RBAC](rbac.md)
- [Authentication risk engine](authentication-risk-engine.md)
- [Activity logging](activity-logging.md)
- [Maintenance operations](operations/maintenance.md)
- [Upload validation](uploads.md)
- [Versioning](versioning.md)

The React checkout does **not** include the source project's Docker Compose or Dockerfile deployment; provision and test deployment services separately. Backend controls are not proof that every React workflow has equivalent UI coverage.

## Historical source references

The [Svelte component inventory](ui-usage/README.md), individual `ui-usage` pages, [reusable components](frontend/reusable-components.md), [frontend component guide](source-reference/frontend-components-svelte.md), [UI restoration audit](ui-restoration-audit.md), and [dated/security summaries](summary/) are retained for source-project context only. They are marked on each page; their Svelte examples, old routes, dependencies, commands, paths and findings should not be treated as React instructions or current guarantees. Source-only paths are identified as such rather than linked to nonexistent React files. React frontend documentation is maintained separately.
