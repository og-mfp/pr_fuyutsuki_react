> **Historical Svelte source reference:** This is preserved from `pr_fuyutsuki`, not current React documentation. Examples, routes, dependencies, commands and conclusions below describe the source at the time of writing and have not been verified for this React application. Do not use them as React instructions.

# Accordion

A vertically stacked set of interactive headings that each reveal a section of content.

Showcase: `/admin/ui/accordion`. Source: ui/components/Accordion.svelte (source-only path: `../../resources/js/pages/ui/components/Accordion.svelte`).

[Official documentation](https://shadcn-svelte.com/docs/components/accordion). Check the installed barrel for all exported parts.

```svelte
<script lang="ts">
    import * as Accordion from '@/components/ui/accordion';
</script>

<Accordion.Root type="single" class="max-w-md">
    <Accordion.Item value="item-1">
        <Accordion.Trigger>
            Single selection
        </Accordion.Trigger><Accordion.Content>
            Opening this item closes another open item.
        </Accordion.Content>
    </Accordion.Item>
    <Accordion.Item value="item-2">
        <Accordion.Trigger>
            Collapsible item
        </Accordion.Trigger><Accordion.Content>
            Select an open item again to close it.
        </Accordion.Content>
    </Accordion.Item>
</Accordion.Root>
```
