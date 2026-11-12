> **Historical Svelte source reference:** This is preserved from `pr_fuyutsuki`, not current React documentation. Examples, routes, dependencies, commands and conclusions below describe the source at the time of writing and have not been verified for this React application. Do not use them as React instructions.

# Input Group

Add addons, buttons, and helper content to inputs.

Showcase: `/admin/ui/input-group`. Source: ui/components/InputGroup.svelte (source-only path: `../../resources/js/pages/ui/components/InputGroup.svelte`).

[Official documentation](https://shadcn-svelte.com/docs/components/input-group). Check the installed barrel for all exported parts.

```svelte
<script lang="ts">
    import * as InputGroup from '@/components/ui/input-group';
    let value = $state('');
</script>

<div class="flex max-w-md flex-col gap-3">
    <InputGroup.Root>
        <InputGroup.Addon>https://</InputGroup.Addon><InputGroup.Input
            bind:value
            aria-label="Website"
            placeholder="example.com"
        />
    </InputGroup.Root>
    <InputGroup.Root>
        <InputGroup.Input
            aria-label="Search"
            placeholder="Search"
        /><InputGroup.Addon align="inline-end">⌕</InputGroup.Addon>
    </InputGroup.Root>
</div>

<p class="mt-3" role="status">Current value: {value || 'Empty'}</p>
```
