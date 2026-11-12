> **Historical Svelte source reference:** This is preserved from `pr_fuyutsuki`, not current React documentation. Examples, routes, dependencies, commands and conclusions below describe the source at the time of writing and have not been verified for this React application. Do not use them as React instructions.

# Radio Group

A set of checkable buttonsknown as radio buttonswhere no more than one of the buttons can be checked at a time.

Showcase: `/admin/ui/radio-group`. Source: ui/components/RadioGroup.svelte (source-only path: `../../resources/js/pages/ui/components/RadioGroup.svelte`).

[Official documentation](https://shadcn-svelte.com/docs/components/radio-group). Check the installed barrel for all exported parts.

```svelte
<script lang="ts">
    import * as Field from '@/components/ui/field';
    import * as RadioGroup from '@/components/ui/radio-group';
    let value = $state('one');
</script>

<Field.Set>
    <Field.Legend variant="label">Choose one</Field.Legend><RadioGroup.Root
        bind:value
    >
        <Field.Group class="gap-3">
            <Field.Field orientation="horizontal">
                <RadioGroup.Item value="one" id="radio-one" /><Field.Label
                    for="radio-one"
                >
                    First
                </Field.Label>
            </Field.Field><Field.Field orientation="horizontal">
                <RadioGroup.Item value="two" id="radio-two" /><Field.Label
                    for="radio-two"
                >
                    Second
                </Field.Label>
            </Field.Field>
        </Field.Group>
    </RadioGroup.Root>
</Field.Set>

<p class="mt-3" role="status">Current value: {value || 'Empty'}</p>
```
