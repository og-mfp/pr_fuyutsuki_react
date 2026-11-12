> **Historical Svelte source reference:** This is preserved from `pr_fuyutsuki`, not current React documentation. Examples, routes, dependencies, commands and conclusions below describe the source at the time of writing and have not been verified for this React application. Do not use them as React instructions.

# Hover Card

For sighted users to preview content available behind a link.

Showcase: `/admin/ui/hover-card`. Source: ui/components/HoverCard.svelte (source-only path: `../../resources/js/pages/ui/components/HoverCard.svelte`).

[Official documentation](https://shadcn-svelte.com/docs/components/hover-card). Check the installed barrel for all exported parts.

```svelte
<script lang="ts">
    import { Button } from '@/components/ui/button';
    import * as HoverCard from '@/components/ui/hover-card';
</script>

<HoverCard.Root>
    <HoverCard.Trigger>
        {#snippet child({ props })}<Button variant="link" {...props}>
                Hover for details
            </Button>{/snippet}
    </HoverCard.Trigger><HoverCard.Content>
        More information appears on hover.
    </HoverCard.Content>
</HoverCard.Root>
```
