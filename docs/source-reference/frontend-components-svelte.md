> **Historical Svelte source reference:** This is preserved from `pr_fuyutsuki`, not current React documentation. Examples, routes, dependencies, commands and conclusions below describe the source at the time of writing and have not been verified for this React application. Do not use them as React instructions.

# Frontend components

Fuyutsuki uses Laravel 13, Fortify, Inertia 3, Svelte 5, TypeScript and Tailwind CSS 4. Authentication, authorization, forms, validation and data ownership remain in Laravel. This is not a SvelteKit application.

## Reference and ownership

The application shell and default theme were compared with [Laravel's Svelte starter kit at 76d6cf4](https://github.com/laravel/svelte-starter-kit/tree/76d6cf46b6cc7236121a75b9e4f1bea8809cb490). The current official shadcn-svelte `nova` registry supplies one component foundation, generated with CLI 1.7.0. The starter reference's older `asChild` wrappers are not copied over modern Bits UI interactions.

- `resources/js/components/ui/`: official multi-file shadcn-svelte primitives and the reviewed compatibility adjustments below.
- `resources/js/components/shared/`: reusable application behavior, including the typed TanStack DataTable.
- `resources/js/components/features/`: feature-specific composition, including the catalog's shared framing.
- `resources/js/pages/ui/components/`: complete, independent showcase pages with local state and usage examples.
- `resources/js/lib/ui-catalog.json`: the explicit route/navigation allowlist. PHP sends this manifest to the catalog index and passes the selected entry to each page.

The `@/` alias resolves to `resources/js/`. Tailwind 4 uses `resources/css/app.css`; `components.json` intentionally has no Tailwind JavaScript config path. The Laravel theme, Instrument Sans font setup, Inertia layout selection and Wayfinder integration remain in place.

## Install and update

Use npm and the [official CLI](https://shadcn-svelte.com/docs/cli):

```bash
npx shadcn-svelte@latest add accordion
```

Check the CLI version and help before updates. The verified CLI 1.7.0 uses `add`, not an `update` command. For existing components, first generate the requested categories in a temporary project with matching aliases and style, compare the source, then apply the reviewed changes. Do not blindly overwrite `--all`: it includes Form, which requires Formsnap and SvelteKit Superforms. Existing Laravel forms use Inertia `Form` with `Field` instead.

The snapshot contains 60 compatible installed categories plus dedicated Data Table, Combobox, Date Picker and Typography composition pages. See [the inventory](../ui-usage/README.md) and [the original audit](../ui-restoration-audit.md). Bubble, Message and Message Scroller are now installed, not merely documented. No SvelteKit package is installed.

## APIs that must remain intact

Import single controls by name (`Button`, `Input`, `Checkbox`); import compound components as namespaces (`Dialog.Root`, `Dialog.Content`). Preserve the registry barrels and internal multi-file composition.

Use `child({ props })` for trigger composition and forward all `props`, including refs, keyboard handlers and ARIA attributes. Use `bind:open`, `bind:value` and `bind:checked` as documented. Dialogs, drawers and sheets require titles. Select and menu items belong inside their appropriate groups.

The custom `static` dialog prop was removed. Protected administration forms use the supported `escapeKeydownBehavior="ignore"` and `interactOutsideBehavior="ignore"`; provide an explicit close control. Ordinary dialogs keep Escape, outside-dismissal and focus restoration.

Button and Alert use official variants. Custom `warning`, `danger`, `success` and `info` primitive variants were removed; use the official `destructive` variant and toast status functions. Input uses native input attributes, not custom visual size/color props.

DataTable imports from `@/components/shared/DataTable.svelte`. It retains its generic TanStack v9 table contract, header/cell snippets, sorting, filtering, visibility and pagination. The underlying markup uses official Table primitives. Do not move application table behavior back into `ui/`.

## Icons

Use the `@lucide/svelte` icon package used by shadcn-svelte. Import an icon directly by its kebab-case name from `@lucide/svelte/icons/`, for example:

```svelte
<script lang="ts">
    import SearchIcon from '@lucide/svelte/icons/search';
</script>

<SearchIcon class="size-4" aria-hidden="true" />
```

Import only icons used by the page so unrelated icons stay out of its bundle. Give decorative icons `aria-hidden="true"`; give icon-only controls an accessible name on the control. Browse a selection of app icons at `/admin/ui/icon-library`.

## Reviewed registry differences

The installed Bits UI 2 release emits `data-state` and `data-orientation` attributes. Keep the existing selector corrections for overlay transitions, switch state, separators, sliders, tabs (orientation and selected state) and toggle groups until upstream registry output matches those attributes. Keep the sidebar's presence-only `data-active` correction so inactive entries are not highlighted. Type-only corrections in Chart and Toggle preserve type checking; they do not change interactions.

Calendar month/year controls, Button, Alert, Input, Input Group and Sonner have been restored from the official snapshot. Dialog content is official with the same Bits UI state-selector correction. Application layouts pass the existing theme's resolved appearance into Sonner; do not initialize a second global theme manager.

Button caps its width to its parent, wraps long labels, and lets text-sized buttons grow beyond their minimum height.

## Add a showcase

1. Install the compatible primitive and read its official API documentation.
2. Create `resources/js/pages/ui/components/Example.svelte`. Keep the actual examples and their state in this file. The shared `Showcase` component may provide the heading, documentation link and source block only.
3. Add a unique `slug`, `name`, `description`, `category` and exact `page` (for example `ui/components/Example`) to `resources/js/lib/ui-catalog.json`.
4. Laravel's `/admin/ui/{component}` route looks up that exact entry and renders its page. Unknown slugs return 404. `/ui` links redirect only after the same admin authorization checks.
5. Add representative interaction assertions to the browser tests when the category introduces a new interaction pattern.

Use the existing admin application layout. Never add dynamic preview imports or build a filesystem path from a URL parameter. The route/index/page coverage tests enforce the manifest contract.

## Verify

```bash
npm ci
php artisan wayfinder:generate --with-form
npm run types:check
npm run check
npm run build
php artisan route:list
php artisan test
npx playwright install chromium
npm run test:ui
```

The production build uses the existing Bunny font integration and may need network access. Browser tests use the built assets and start their own server on port 8765, with a new temporary SQLite database and storage directory. They never migrate or seed the configured application database. Temporary test directories remain under the OS temporary directory for diagnosis.

Browser coverage includes every catalog page, search, dark appearance persistence, mobile navigation, dialog focus trapping/restoration, keyboard menus/selects, checkbox submission, table controls and existing application pages. Also review labels, reduced motion, contrast and screen-reader output when changing interactions; automated smoke tests are not a full accessibility audit.
