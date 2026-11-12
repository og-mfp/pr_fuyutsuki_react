> **Historical Svelte source reference:** This is preserved from `pr_fuyutsuki`, not current React documentation. Examples, routes, dependencies, commands and conclusions below describe the source at the time of writing and have not been verified for this React application. Do not use them as React instructions.

# Checkbox

A control that allows the user to toggle between checked and not checked.

Showcase: `/admin/ui/checkbox`. Source: ui/components/Checkbox.svelte (source-only path: `../../resources/js/pages/ui/components/Checkbox.svelte`).

[Official documentation](https://shadcn-svelte.com/docs/components/checkbox). Check the installed barrel for all exported parts.

```svelte
<script lang="ts">
    import { Button } from '@/components/ui/button';
    import { Checkbox } from '@/components/ui/checkbox';
    import * as Field from '@/components/ui/field';

    let submitted = $state('');
    let checked = $state(false);
    let checkedExample = $state(true);
    let indeterminate = $state(true);
</script>

<form
    onsubmit={(event) => {
        event.preventDefault();
        submitted =
            new FormData(event.currentTarget).get('agreement') === 'yes'
                ? 'Accepted'
                : 'Not accepted';
    }}
>
    <Field.Set>
        <Field.Legend variant="label">Checkbox states</Field.Legend><Field.Group
            class="gap-3"
        >
            <Field.Field orientation="horizontal">
                <Checkbox
                    id="checkbox-unchecked"
                    name="agreement"
                    value="yes"
                    bind:checked
                /><Field.Label for="checkbox-unchecked">Unchecked</Field.Label>
            </Field.Field>
            <Field.Field orientation="horizontal">
                <Checkbox
                    id="checkbox-checked"
                    bind:checked={checkedExample}
                /><Field.Label for="checkbox-checked">Checked</Field.Label>
            </Field.Field>
            <Field.Field orientation="horizontal">
                <Checkbox
                    id="checkbox-indeterminate"
                    bind:indeterminate
                /><Field.Label for="checkbox-indeterminate">
                    Indeterminate
                </Field.Label>
            </Field.Field>
            <Field.Field orientation="horizontal" data-disabled>
                <Checkbox id="checkbox-disabled" disabled /><Field.Label
                    for="checkbox-disabled"
                >
                    Disabled
                </Field.Label>
            </Field.Field>
        </Field.Group>
    </Field.Set><Button type="submit" class="mt-4">Submit checkbox</Button>
    <p role="status" class="mt-3">Submitted: {submitted || 'Nothing yet'}</p>
</form>
```
