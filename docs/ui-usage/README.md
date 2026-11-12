> **Historical Svelte source reference:** This is preserved from `pr_fuyutsuki`, not current React documentation. Examples, routes, dependencies, commands and conclusions below describe the source at the time of writing and have not been verified for this React application. Do not use them as React instructions.

# Component inventory

The catalog has 64 independent pages at `/admin/ui`, protected by the existing admin gate. There are 60 official component categories and four documented compositions. Form is excluded because its Formsnap/SvelteKit Superforms integration is outside this Laravel + Inertia architecture.

Source components were reviewed against official shadcn-svelte CLI 1.7.0, nova registry, retrieved 2026-10-14. Components are vendored source, not individually versioned packages. See [installation and update guidance](../source-reference/frontend-components-svelte.md).

## Runtime dependencies

- `svelte`: 5.57.1
- `@inertiajs/svelte`: 3.7.1
- `tailwindcss`: 4.3.3
- `bits-ui`: 2.19.5
- `@shadcn-svelte/primitives`: 0.0.1
- `@tanstack/svelte-table`: 9.2.6
- `@internationalized/date`: 3.12.4
- `tailwind-variants`: 3.3.1
- `@lucide/svelte`: 1.52.0
- `embla-carousel-svelte`: 8.6.0
- `layerchart`: 2.6.1
- `mode-watcher`: 1.1.0
- `paneforge`: 1.0.2
- `svelte-sonner`: 1.2.1
- `vaul-svelte`: 1.0.0-next.7

## Routes and pages

| Category                                | Route                      | Inertia page                  |
| --------------------------------------- | -------------------------- | ----------------------------- |
| [Accordion](accordion.md)               | /admin/ui/accordion        | ui/components/Accordion       |
| [Alert](alert.md)                       | /admin/ui/alert            | ui/components/Alert           |
| [Alert Dialog](alert-dialog.md)         | /admin/ui/alert-dialog     | ui/components/AlertDialog     |
| [Aspect Ratio](aspect-ratio.md)         | /admin/ui/aspect-ratio     | ui/components/AspectRatio     |
| [Attachment](attachment.md)             | /admin/ui/attachment       | ui/components/Attachment      |
| [Avatar](avatar.md)                     | /admin/ui/avatar           | ui/components/Avatar          |
| [Badge](badge.md)                       | /admin/ui/badge            | ui/components/Badge           |
| [Breadcrumb](breadcrumb.md)             | /admin/ui/breadcrumb       | ui/components/Breadcrumb      |
| [Bubble](bubble.md)                     | /admin/ui/bubble           | ui/components/Bubble          |
| [Button](button.md)                     | /admin/ui/button           | ui/components/Button          |
| [Button Group](button-group.md)         | /admin/ui/button-group     | ui/components/ButtonGroup     |
| [Calendar](calendar.md)                 | /admin/ui/calendar         | ui/components/Calendar        |
| [Card](card.md)                         | /admin/ui/card             | ui/components/Card            |
| [Carousel](carousel.md)                 | /admin/ui/carousel         | ui/components/Carousel        |
| [Chart](chart.md)                       | /admin/ui/chart            | ui/components/Chart           |
| [Checkbox](checkbox.md)                 | /admin/ui/checkbox         | ui/components/Checkbox        |
| [Collapsible](collapsible.md)           | /admin/ui/collapsible      | ui/components/Collapsible     |
| [Combobox](combobox.md)                 | /admin/ui/combobox         | ui/components/Combobox        |
| [Command](command.md)                   | /admin/ui/command          | ui/components/Command         |
| [Context Menu](context-menu.md)         | /admin/ui/context-menu     | ui/components/ContextMenu     |
| [Data Table](data-table.md)             | /admin/ui/data-table       | ui/components/DataTable       |
| [Date Picker](date-picker.md)           | /admin/ui/date-picker      | ui/components/DatePicker      |
| [Dialog](dialog.md)                     | /admin/ui/dialog           | ui/components/Dialog          |
| [Drawer](drawer.md)                     | /admin/ui/drawer           | ui/components/Drawer          |
| [Dropdown Menu](dropdown-menu.md)       | /admin/ui/dropdown-menu    | ui/components/DropdownMenu    |
| [Empty](empty.md)                       | /admin/ui/empty            | ui/components/Empty           |
| [Field](field.md)                       | /admin/ui/field            | ui/components/Field           |
| [Hover Card](hover-card.md)             | /admin/ui/hover-card       | ui/components/HoverCard       |
| [Input](input.md)                       | /admin/ui/input            | ui/components/Input           |
| [Input Group](input-group.md)           | /admin/ui/input-group      | ui/components/InputGroup      |
| [Input OTP](input-otp.md)               | /admin/ui/input-otp        | ui/components/InputOTP        |
| [Item](item.md)                         | /admin/ui/item             | ui/components/Item            |
| [Kbd](kbd.md)                           | /admin/ui/kbd              | ui/components/Kbd             |
| [Label](label.md)                       | /admin/ui/label            | ui/components/Label           |
| [Marker](marker.md)                     | /admin/ui/marker           | ui/components/Marker          |
| [Menubar](menubar.md)                   | /admin/ui/menubar          | ui/components/Menubar         |
| [Message](message.md)                   | /admin/ui/message          | ui/components/Message         |
| [Message Scroller](message-scroller.md) | /admin/ui/message-scroller | ui/components/MessageScroller |
| [Navigation Menu](navigation-menu.md)   | /admin/ui/navigation-menu  | ui/components/NavigationMenu  |
| [Pagination](pagination.md)             | /admin/ui/pagination       | ui/components/Pagination      |
| [Popover](popover.md)                   | /admin/ui/popover          | ui/components/Popover         |
| [Progress](progress.md)                 | /admin/ui/progress         | ui/components/Progress        |
| [Questionnaire](questionnaire.md)       | /admin/ui/questionnaire    | ui/components/Questionnaire   |
| [Radio Group](radio-group.md)           | /admin/ui/radio-group      | ui/components/RadioGroup      |
| [Range Calendar](range-calendar.md)     | /admin/ui/range-calendar   | ui/components/RangeCalendar   |
| [Resizable](resizable.md)               | /admin/ui/resizable        | ui/components/Resizable       |
| [Scroll Area](scroll-area.md)           | /admin/ui/scroll-area      | ui/components/ScrollArea      |
| [Select](select.md)                     | /admin/ui/select           | ui/components/Select          |
| [Separator](separator.md)               | /admin/ui/separator        | ui/components/Separator       |
| [Sheet](sheet.md)                       | /admin/ui/sheet            | ui/components/Sheet           |
| [Sidebar](sidebar.md)                   | /admin/ui/sidebar          | ui/components/Sidebar         |
| [Skeleton](skeleton.md)                 | /admin/ui/skeleton         | ui/components/Skeleton        |
| [Slider](slider.md)                     | /admin/ui/slider           | ui/components/Slider          |
| [Sonner](sonner.md)                     | /admin/ui/sonner           | ui/components/Sonner          |
| [Spinner](spinner.md)                   | /admin/ui/spinner          | ui/components/Spinner         |
| [Switch](switch.md)                     | /admin/ui/switch           | ui/components/Switch          |
| [Table](table.md)                       | /admin/ui/table            | ui/components/Table           |
| [Tabs](tabs.md)                         | /admin/ui/tabs             | ui/components/Tabs            |
| [Textarea](textarea.md)                 | /admin/ui/textarea         | ui/components/Textarea        |
| [Toggle](toggle.md)                     | /admin/ui/toggle           | ui/components/Toggle          |
| [Toggle Group](toggle-group.md)         | /admin/ui/toggle-group     | ui/components/ToggleGroup     |
| [Tooltip](tooltip.md)                   | /admin/ui/tooltip          | ui/components/Tooltip         |
| [Typography](typography.md)             | /admin/ui/typography       | ui/components/Typography      |
