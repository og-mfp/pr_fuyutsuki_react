> **Historical Svelte source reference:** This is preserved from `pr_fuyutsuki`, not current React documentation. Examples, routes, dependencies, commands and conclusions below describe the source at the time of writing and have not been verified for this React application. Do not use them as React instructions.

# Textarea

Displays a form textarea or a component that looks like a textarea.

Showcase: `/admin/ui/textarea`. Source: ui/components/Textarea.svelte (source-only path: `../../resources/js/pages/ui/components/Textarea.svelte`).

[Official documentation](https://shadcn-svelte.com/docs/components/textarea). Check the installed barrel for all exported parts.

```svelte
<script lang="ts">
    import * as Field from '@/components/ui/field';
    import { Textarea } from '@/components/ui/textarea';
    let value = $state('');
</script>

<Field.Group class="max-w-2xl sm:grid sm:grid-cols-2">
    <Field.Field>
        <Field.Label for="textarea-empty">Empty</Field.Label><Textarea
            id="textarea-empty"
            bind:value
            placeholder="Write a message"
        />
    </Field.Field>
    <Field.Field>
        <Field.Label for="textarea-filled">Filled</Field.Label><Textarea
            id="textarea-filled"
            value="Existing message"
        />
    </Field.Field>
    <Field.Field data-invalid>
        <Field.Label for="textarea-invalid">Invalid</Field.Label><Textarea
            id="textarea-invalid"
            aria-invalid="true"
            value="Invalid message"
        />
    </Field.Field>
    <Field.Field data-disabled>
        <Field.Label for="textarea-disabled">Disabled</Field.Label><Textarea
            id="textarea-disabled"
            placeholder="Disabled"
            disabled
        />
    </Field.Field>
</Field.Group>

<p class="mt-3" role="status">Current value: {value || 'Empty'}</p>
```
