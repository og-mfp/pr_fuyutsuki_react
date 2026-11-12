> **Historical Svelte source reference:** This is preserved from `pr_fuyutsuki`, not current React documentation. Examples, routes, dependencies, commands and conclusions below describe the source at the time of writing and have not been verified for this React application. Do not use them as React instructions.

# Reusable frontend components

## Audit and public APIs

The UI catalog currently lists every installed primitive family. The audit groups them by usage, rather than generating a facade for each family:

| Category                    | Families and decision                                                                                                                                                                                                                                                                                                                                                                                                                                                                           |
| --------------------------- | ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Direct use                  | Button, Input, Badge, Spinner, Textarea, Checkbox, Switch, Separator, Skeleton, Progress, Slider, Toggle, Label, Kbd, Aspect Ratio. Their single-component APIs are already short.                                                                                                                                                                                                                                                                                                              |
| Shared facade               | Accordion for item data; Alert Dialog for confirmation; Field for ordinary text inputs; Badge for semantic status; page headings where a title and action row repeat.                                                                                                                                                                                                                                                                                                                           |
| Existing shared abstraction | DataTable already owns TanStack sorting, filtering, pagination, visibility and rendering. Keep its `table` API. A `data`/`columns` overload would duplicate feature configuration and blur client/server pagination.                                                                                                                                                                                                                                                                            |
| Feature composition         | Chart, Carousel, Questionnaire, Message, Bubble, Attachment, Marker and domain forms belong to the feature using them.                                                                                                                                                                                                                                                                                                                                                                          |
| Compound use                | Avatar, Breadcrumb, Button Group, Calendar, Card, Collapsible, Combobox, Command, Context Menu, Date Picker, Dialog, Drawer, Dropdown Menu, Empty, Hover Card, Input Group, Input OTP, Item, Menubar, Message Scroller, Navigation Menu, Pagination, Popover, Radio Group, Range Calendar, Resizable, Scroll Area, Select, Sheet, Sidebar, Sonner, Table, Tabs, Toggle Group, Tooltip and Typography remain direct compositions. No repeated application pattern currently justifies a wrapper. |

The facade APIs are `Accordion` with `type`, `items`, optional selection and content snippet; `ConfirmDialog` with `open`, title, labels, variant and confirmation callback; `FormField` with input attributes, label, description and error; `StatusBadge` with typed status and optional label; and `PageHeader` with title, description and action snippet.

Accordion item `value` strings must be stable and unique within one accordion. Use `value` plus `onValueChange` for controlled selection, or `defaultValue` for initial selection. The single form takes a string; the multiple form takes a string array. ConfirmDialog callers set `open` explicitly and keep server authorization and failure display in the action callback.

## Boundaries

`components/ui` contains shadcn-svelte primitives. `components/shared` contains small, business-agnostic facades and DataTable. Domain rules, routes, permissions and form submissions stay in pages or `components/features`. `components/layout` and `layouts` own layout. Names are PascalCase Svelte files; import a shared facade directly. Use the primitive compound API when a form uses a custom control, a dialog contains a form, or accordion content needs custom structure beyond the facade snippet.

Use the existing `Heading` component for section headings; `PageHeader` supplies a page-level `h1` and an action area.

```svelte
<Accordion type="multiple" items={[{ value: 'security', title: 'Security', content: 'Settings' }]} />
<StatusBadge status="active" />
<FormField name="email" label="Email" type="email" error={errors.email} required />
<ConfirmDialog title="Delete?" bind:open={open} onConfirm={deleteItem} />
```

Extend an existing facade only when a repeated, shared behavior remains simple and its current API remains valid. For a new facade, first inspect installed primitives and real callers, then add only the common composition in `components/shared`. Keep attributes and callback types explicit, labels and errors associated with controls, keyboard and focus behavior inherited from primitives, and authorization at the server boundary. Exercise interactive states in the catalog and browser tests, then run `npm run check`, `npm run types:check`, `npm run build`, `npm run test:ui`, and `php artisan test`.
