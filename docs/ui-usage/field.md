> **Historical Svelte source reference:** This is preserved from `pr_fuyutsuki`, not current React documentation. Examples, routes, dependencies, commands and conclusions below describe the source at the time of writing and have not been verified for this React application. Do not use them as React instructions.

# Field

Combine labels, controls, and help text to compose accessible form fields and grouped inputs.

Showcase: `/admin/ui/field`. Source: ui/components/Field.svelte (source-only path: `../../resources/js/pages/ui/components/Field.svelte`).

[Official documentation](https://shadcn-svelte.com/docs/components/field). Check the installed barrel for all exported parts.

```svelte
<script lang="ts">
    import * as Field from '@/components/ui/field';
    import { Input } from '@/components/ui/input';
    let value = $state('');
</script>

<Field.Group class="max-w-xl">
    <Field.Field>
        <Field.Label for="ui-field-vertical">Email</Field.Label><Input
            bind:value
            id="ui-field-vertical"
            type="email"
            placeholder="you@example.com"
        /><Field.Description>Vertical field.</Field.Description>
    </Field.Field><Field.Field orientation="horizontal">
        <Field.Label for="ui-field-horizontal">Name</Field.Label><Input
            id="ui-field-horizontal"
            placeholder="Ada Lovelace"
        />
    </Field.Field>
</Field.Group>

<p class="mt-3" role="status">Current value: {value || 'Empty'}</p>
```
