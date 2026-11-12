> **Historical Svelte source reference:** This is preserved from `pr_fuyutsuki`, not current React documentation. Examples, routes, dependencies, commands and conclusions below describe the source at the time of writing and have not been verified for this React application. Do not use them as React instructions.

# Input

A text input component for forms and user data entry with built-in styling and accessibility features.

Showcase: `/admin/ui/input`. Source: ui/components/Input.svelte (source-only path: `../../resources/js/pages/ui/components/Input.svelte`).

[Official documentation](https://shadcn-svelte.com/docs/components/input). Check the installed barrel for all exported parts.

```svelte
<script lang="ts">
    import { Input } from '@/components/ui/input';
    import * as Field from '@/components/ui/field';
    import { Button } from '@/components/ui/button';
    let name = $state('Ada');
    let submitted = $state('');
</script>

<form
    onsubmit={(event) => {
        event.preventDefault();
        submitted = name;
    }}
    class="max-w-md"
>
    <Field.Group>
        <Field.Field>
            <Field.Label for="example-name">Name</Field.Label><Input
                id="example-name"
                name="name"
                bind:value={name}
                required
            /><Field.Description>
                Type a name and submit with Enter.
            </Field.Description>
        </Field.Field>
        <Field.Field data-invalid>
            <Field.Label for="invalid-email">Invalid email</Field.Label><Input
                id="invalid-email"
                aria-invalid="true"
                aria-describedby="email-error"
                value="invalid"
            /><Field.Error id="email-error">
                Enter a valid email address.
            </Field.Error>
        </Field.Field>
        <Field.Field data-disabled>
            <Field.Label for="disabled-input">Disabled</Field.Label><Input
                id="disabled-input"
                disabled
                value="Read only example"
            />
        </Field.Field>
        <Button type="submit">Submit name</Button>
        <p role="status">Submitted: {submitted || 'Nothing yet'}</p>
    </Field.Group>
</form>
```
