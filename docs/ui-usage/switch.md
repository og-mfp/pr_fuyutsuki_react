> **Historical Svelte source reference:** This is preserved from `pr_fuyutsuki`, not current React documentation. Examples, routes, dependencies, commands and conclusions below describe the source at the time of writing and have not been verified for this React application. Do not use them as React instructions.

# Switch

A control that allows the user to toggle between checked and not checked.

Showcase: `/admin/ui/switch`. Source: ui/components/Switch.svelte (source-only path: `../../resources/js/pages/ui/components/Switch.svelte`).

[Official documentation](https://shadcn-svelte.com/docs/components/switch). Check the installed barrel for all exported parts.

```svelte
<script lang="ts">
    import * as Field from '@/components/ui/field';
    import { Switch } from '@/components/ui/switch';

    let checked = $state(false);
    let checkedExample = $state(true);
</script>

<Field.Set>
    <Field.Legend variant="label">Switch states</Field.Legend><Field.Group
        class="gap-3"
    >
        <Field.Field orientation="horizontal">
            <Switch id="switch-off" bind:checked /><Field.Label
                for="switch-off"
            >
                Off
            </Field.Label>
        </Field.Field>
        <Field.Field orientation="horizontal">
            <Switch id="switch-on" bind:checked={checkedExample} /><Field.Label
                for="switch-on"
            >
                On
            </Field.Label>
        </Field.Field>
        <Field.Field orientation="horizontal">
            <Switch id="switch-small" size="sm" /><Field.Label
                for="switch-small"
            >
                Small
            </Field.Label>
        </Field.Field>
        <Field.Field orientation="horizontal">
            <Switch id="switch-small-on" size="sm" checked /><Field.Label
                for="switch-small-on"
            >
                Small on
            </Field.Label>
        </Field.Field>
        <Field.Field orientation="horizontal" dir="rtl">
            <Switch id="switch-rtl" checked /><Field.Label for="switch-rtl">
                Right to left
            </Field.Label>
        </Field.Field>
        <Field.Field orientation="horizontal" data-invalid>
            <Switch id="switch-invalid" aria-invalid="true" /><Field.Label
                for="switch-invalid"
            >
                Invalid
            </Field.Label>
        </Field.Field>
        <Field.Field orientation="horizontal" data-disabled>
            <Switch id="switch-disabled" disabled /><Field.Label
                for="switch-disabled"
            >
                Disabled
            </Field.Label>
        </Field.Field>
    </Field.Group>
</Field.Set>
```
