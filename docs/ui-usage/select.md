> **Historical Svelte source reference:** This is preserved from `pr_fuyutsuki`, not current React documentation. Examples, routes, dependencies, commands and conclusions below describe the source at the time of writing and have not been verified for this React application. Do not use them as React instructions.

# Select

Displays a list of options for the user to pick fromtriggered by a button.

Showcase: `/admin/ui/select`. Source: ui/components/Select.svelte (source-only path: `../../resources/js/pages/ui/components/Select.svelte`).

[Official documentation](https://shadcn-svelte.com/docs/components/select). Check the installed barrel for all exported parts.

```svelte
<script lang="ts">
    import * as Select from '@/components/ui/select';
    import * as Field from '@/components/ui/field';
    import { Button } from '@/components/ui/button';
    const options = [
        { value: 'daily', label: 'Daily digest' },
        { value: 'weekly', label: 'Weekly digest' },
    ];
    let value = $state('daily');
    let submitted = $state('');
</script>

<form
    class="flex max-w-sm flex-col gap-4"
    onsubmit={(event) => {
        event.preventDefault();
        submitted = String(new FormData(event.currentTarget).get('frequency'));
    }}
>
    <Field.Field>
        <Field.Label for="frequency">Frequency</Field.Label><Select.Root
            type="single"
            name="frequency"
            items={options}
            bind:value
        >
            <Select.Trigger id="frequency">
                <Select.Value placeholder="Choose frequency" />
            </Select.Trigger><Select.Content>
                <Select.Group>
                    {#each options as option}<Select.Item value={option.value}>
                            {option.label}
                        </Select.Item>{/each}<Select.Item
                        value="unavailable"
                        disabled
                    >
                        Unavailable
                    </Select.Item>
                </Select.Group>
            </Select.Content>
        </Select.Root>
    </Field.Field><Button type="submit">Submit selection</Button>
    <p role="status">Submitted: {submitted || 'Nothing yet'}</p>
</form>
```

For a single-choice menu with text search, use `Select.Searchable`. It takes `options` as `{ value, label }` objects and supports `bind:value`, `name`, `placeholder`, `searchPlaceholder`, and `emptyMessage`. See [Combobox](combobox.md) for an example.
