> **Historical Svelte source reference:** This is preserved from `pr_fuyutsuki`, not current React documentation. Examples, routes, dependencies, commands and conclusions below describe the source at the time of writing and have not been verified for this React application. Do not use them as React instructions.

# Collapsible

An interactive component which expands/collapses a panel.

Showcase: `/admin/ui/collapsible`. Source: ui/components/Collapsible.svelte (source-only path: `../../resources/js/pages/ui/components/Collapsible.svelte`).

[Official documentation](https://shadcn-svelte.com/docs/components/collapsible). Check the installed barrel for all exported parts.

```svelte
<script lang="ts">
    import { Button } from '@/components/ui/button';
    import * as Collapsible from '@/components/ui/collapsible';
</script>

<Collapsible.Root class="flex max-w-sm flex-col gap-3">
    <Collapsible.Trigger>
        {#snippet child({ props })}<Button variant="outline" {...props}>
                Show details
            </Button>{/snippet}
    </Collapsible.Trigger><Collapsible.Content>
        These details can be expanded and collapsed.
    </Collapsible.Content>
</Collapsible.Root>
```
