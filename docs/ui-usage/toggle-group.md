> **Historical Svelte source reference:** This is preserved from `pr_fuyutsuki`, not current React documentation. Examples, routes, dependencies, commands and conclusions below describe the source at the time of writing and have not been verified for this React application. Do not use them as React instructions.

# Toggle Group

A set of two-state buttons that can be toggled on or off.

Showcase: `/admin/ui/toggle-group`. Source: ui/components/ToggleGroup.svelte (source-only path: `../../resources/js/pages/ui/components/ToggleGroup.svelte`).

[Official documentation](https://shadcn-svelte.com/docs/components/toggle-group). Check the installed barrel for all exported parts.

```svelte
<script lang="ts">
    import * as ToggleGroup from '@/components/ui/toggle-group';

    let alignment = $state('left');
    let formatting = $state(['bold']);
</script>

<div class="flex flex-wrap items-start gap-5">
    <ToggleGroup.Root
        type="single"
        variant="outline"
        bind:value={alignment}
        aria-label="Alignment"
    >
        <ToggleGroup.Item value="left">Left</ToggleGroup.Item><ToggleGroup.Item
            value="center"
        >
            Center
        </ToggleGroup.Item><ToggleGroup.Item value="right">
            Right
        </ToggleGroup.Item>
    </ToggleGroup.Root>
    <ToggleGroup.Root
        type="multiple"
        variant="outline"
        spacing={1}
        bind:value={formatting}
        aria-label="Formatting"
    >
        <ToggleGroup.Item value="bold">Bold</ToggleGroup.Item><ToggleGroup.Item
            value="italic"
        >
            Italic
        </ToggleGroup.Item>
    </ToggleGroup.Root>
    <ToggleGroup.Root type="single" orientation="vertical" variant="outline">
        <ToggleGroup.Item value="top">Top</ToggleGroup.Item><ToggleGroup.Item
            value="bottom"
        >
            Bottom
        </ToggleGroup.Item>
    </ToggleGroup.Root>
    <ToggleGroup.Root
        type="single"
        size="sm"
        disabled
        aria-label="Disabled options"
    >
        <ToggleGroup.Item value="one">One</ToggleGroup.Item><ToggleGroup.Item
            value="two"
        >
            Two
        </ToggleGroup.Item>
    </ToggleGroup.Root>
</div>
```
