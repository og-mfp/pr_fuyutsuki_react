> **Historical Svelte source reference:** This is preserved from `pr_fuyutsuki`, not current React documentation. Examples, routes, dependencies, commands and conclusions below describe the source at the time of writing and have not been verified for this React application. Do not use them as React instructions.

# Tooltip

A popup that displays information related to an element when the element receives keyboard focus or the mouse hovers over it.

Showcase: `/admin/ui/tooltip`. Source: ui/components/Tooltip.svelte (source-only path: `../../resources/js/pages/ui/components/Tooltip.svelte`).

[Official documentation](https://shadcn-svelte.com/docs/components/tooltip). Check the installed barrel for all exported parts.

```svelte
<script lang="ts">
    import { Button } from '@/components/ui/button';
    import * as Tooltip from '@/components/ui/tooltip';
</script>

<Tooltip.Provider>
    <Tooltip.Root>
        <Tooltip.Trigger>
            {#snippet child({ props })}<Button variant="outline" {...props}>
                    Hover for tooltip
                </Button>{/snippet}
        </Tooltip.Trigger><Tooltip.Content>Helpful detail</Tooltip.Content>
    </Tooltip.Root>
</Tooltip.Provider>
```
