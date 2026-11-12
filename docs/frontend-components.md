# Frontend components

This document adapts the component guidance from the Svelte application's `docs/frontend-components.md` and `docs/frontend/reusable-components.md` to the React code currently present here. The Svelte catalog, APIs, facades and test coverage are **not** evidence that their React counterparts have been migrated.

## Stack and ownership

The frontend uses React 19, TypeScript, Inertia 3 and Tailwind CSS 4 with Laravel and Fortify. Laravel remains responsible for authentication, authorization, validation and data ownership; this is not a SvelteKit application. Declared dependency versions and scripts are in `package.json`.

- `resources/js/components/ui/` contains React UI components, many composed from Radix primitives. `components.json` configures shadcn with `radix-mira`, Remix Icon, RTL, CSS variables and `resources/css/app.css`.
- `resources/js/components/` contains application components such as `heading`, `input-error`, navigation and security components. `resources/js/components/migration/common.tsx` contains transitional `Screen`, `Field`, `Errors`, `SubmitForm` and `Pagination` helpers.
- `resources/js/pages/` contains Inertia pages; `resources/js/layouts/` contains application, authentication and settings layouts. `resources/js/app.tsx` resolves pages and chooses layouts.
- `@/` resolves to `resources/js/`. Styles are in `resources/css/app.css`; the configured font package is Inter Variable. Generated Wayfinder actions and routes live under `resources/js/actions/` and `resources/js/routes/`.

## Installed components and usage

Check the actual files and exports before using a component. The current `components/ui` families are alert, avatar, badge, breadcrumb, button, card, checkbox, collapsible, dialog, dropdown menu, icon, input OTP, input, label, navigation menu, placeholder pattern, select, separator, sheet, sidebar, skeleton, sonner, spinner, toggle group, toggle and tooltip. A catalog entry does not establish that its primitive is installed.

React components use JSX, named exports and React props, not Svelte namespace imports, snippets, `bind:` or `child({ props })`. For example:

```tsx
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogTitle, DialogTrigger } from '@/components/ui/dialog';

export function ExampleDialog() {
    return (
        <Dialog>
            <DialogTrigger asChild>
                <Button type="button">Open details</Button>
            </DialogTrigger>
            <DialogContent>
                <DialogTitle>Details</DialogTitle>
                <p>Example content.</p>
            </DialogContent>
        </Dialog>
    );
}
```

Use the relevant Radix component's supported props for controlled state, and preserve forwarded event handlers, refs and accessibility attributes when composing triggers. Give dialogs an accessible title, icon-only controls an accessible name and form fields associated labels. Preserve keyboard, dismissal and focus behavior unless a specific interaction requires otherwise; provide an explicit way to close a protected dialog.

Button and Alert expose their own variants; inspect the local component definitions rather than assuming the old Svelte variants or layout behavior. Button currently uses fixed height and `whitespace-nowrap`, not the Svelte document's wrapping guarantee.

The migration `Field` is a small uncontrolled input helper with `name`, optional `label`, `type`, `value` and `required`; it does not provide a per-field error prop. `SubmitForm` renders aggregate errors and uses `window.confirm` when a confirmation string is supplied. For richer controls and error association, compose the existing `Label`, `Input`, `InputError` and Inertia forms as in `pages/settings/profile.tsx`. Keep authorization on the server.

The Svelte shared `DataTable`, Accordion, ConfirmDialog, FormField, StatusBadge and PageHeader facades are not present under their documented Svelte paths. Do not import them or assume equivalent APIs, sorting, filtering, pagination or confirmation behavior exist here. The migration `Pagination` only renders record count and available previous/next links.

## Icons and appearance

Both `@remixicon/react` and `lucide-react` are declared: the shadcn configuration selects Remix Icon, while existing application components also use Lucide. Follow the local component being extended, import only the icons needed, hide decorative icons from assistive technology and name icon-only controls.

`app.tsx` calls `initializeTheme()` from `hooks/use-appearance` and mounts a global `Toaster` and `TooltipProvider`. The Sonner wrapper currently reads `useTheme()` from `next-themes`, while the application appearance hook manages its own state; `app.tsx` does not mount a `ThemeProvider`. Do not assume toast theme follows the app's selected appearance without testing or integrating these mechanisms.

## Catalog and migration status

Laravel reads `resources/ui-catalog.json` in `routes/web.php`; `resources/js/lib/ui-catalog.json` is a separate copy. They currently contain the same 69 entries, including names inherited from the Svelte catalog. The server keys entries by slug, supplies them to `ui/Index`, and renders the selected entry's `page` with a `component` prop. `/admin/ui` and `/admin/ui/{component}` require authenticated, verified users with `access-admin`; unknown catalog slugs return 404. The `/ui` routes validate the slug and redirect to the admin catalog under the same authorization gate.

The React catalog index filters names and categories client-side. However, `app.tsx` maps every `ui/components/*` page name to the single `ui/ComponentPreview.tsx`, which displays metadata and explicitly says the interactive demonstration has not yet been migrated. The catalog is therefore an allowlist and migration inventory, **not** an inventory of implemented React components or working examples. In particular, do not follow the Svelte instructions to create a showcase and expect it to render without changing this resolver.

When migrating a showcase, first implement and verify the needed React components and interactions, then update the page resolver and catalog as appropriate. Keep catalog route authorization and slug validation intact; avoid deriving arbitrary import paths from URL input. Reconcile the two catalog files if their entries change and check actual route behavior and page coverage. Do not claim migration based solely on an entry or placeholder.

## Adding or updating primitives

The project declares the React `shadcn` CLI and uses the configuration in `components.json`; do not run the Svelte CLI against this project. Before generating or overwriting a component, inspect the installed dependencies, configuration, existing local implementation and callers; review generated changes rather than blindly replacing the entire UI directory. Keep application-specific behavior outside generic primitives where practical.

## Verification

For frontend changes, the scripts actually defined in this project include:

```bash
npm run types:check
npm run check
npm run build
php artisan test
```

Run the tests relevant to the changed behavior, including `tests/Feature/UiGalleryTest.php` for catalog routing when applicable. These commands are guidance, not a claim that this documentation change ran them. Unlike the Svelte source project, this `package.json` does not define `test:ui`; do not assume its Playwright gallery and accessibility coverage exists in React. Exercise new interactions manually or with suitable added tests, including keyboard use, focus, labels, error states, mobile layout and appearance; automated checks alone are not a complete accessibility audit.
