> **Historical Svelte source reference:** This is preserved from `pr_fuyutsuki`, not current React documentation. Examples, routes, dependencies, commands and conclusions below describe the source at the time of writing and have not been verified for this React application. Do not use them as React instructions.

# Scroll Area

Native scroll container with a thin scrollbar that appears on hover or keyboard focus.

Showcase: `/admin/ui/scroll-area`. Source: ui/components/ScrollArea.svelte (source-only path: `../../resources/js/pages/ui/components/ScrollArea.svelte`).

The component supports vertical, horizontal, and two-axis scrolling with the `orientation` prop.

```svelte
<script lang="ts">
    import { ScrollArea } from '@/components/ui/scroll-area';
    import { Separator } from '@/components/ui/separator';

    const tags = Array.from({ length: 50 }, (_, i) => `v1.2.0-beta.${50 - i}`);
</script>

<ScrollArea class="h-72 w-48 rounded-md border">
    <div class="p-4">
        <h4 class="mb-4 text-sm leading-none font-medium">Tags</h4>
        {#each tags as tag (tag)}
            <div class="text-sm">{tag}</div>
            <Separator class="my-2" />
        {/each}
    </div>
</ScrollArea>
```
