> **Historical Svelte source reference:** This is preserved from `pr_fuyutsuki`, not current React documentation. Examples, routes, dependencies, commands and conclusions below describe the source at the time of writing and have not been verified for this React application. Do not use them as React instructions.

# Combobox

Searchable select for choosing one option. The menu input filters options as the user types.

Showcase: `/admin/ui/combobox`. Source: ui/components/Combobox.svelte (source-only path: `../../resources/js/pages/ui/components/Combobox.svelte`).

```svelte
<script lang="ts">
    import * as Select from '@/components/ui/select';
    const options = [
        { value: 'laravel', label: 'Laravel' },
        { value: 'svelte', label: 'Svelte' },
    ];
    let value = $state('');
</script>

<Select.Searchable
    {options}
    bind:value
    name="technology"
    placeholder="Choose technology"
    searchPlaceholder="Search technologies"
/>
```
