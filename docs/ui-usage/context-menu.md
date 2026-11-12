> **Historical Svelte source reference:** This is preserved from `pr_fuyutsuki`, not current React documentation. Examples, routes, dependencies, commands and conclusions below describe the source at the time of writing and have not been verified for this React application. Do not use them as React instructions.

# Context Menu

Displays a menu of actions triggered by a right click.

Showcase: `/admin/ui/context-menu`. Source: ui/components/ContextMenu.svelte (source-only path: `../../resources/js/pages/ui/components/ContextMenu.svelte`).

[Official documentation](https://shadcn-svelte.com/docs/components/context-menu). Check the installed barrel for all exported parts.

```svelte
<script lang="ts">
    import { Button } from '@/components/ui/button';
    import * as ContextMenu from '@/components/ui/context-menu';
</script>

<ContextMenu.Root>
    <ContextMenu.Trigger>
        {#snippet child({ props })}<Button variant="outline" {...props}>
                Right-click here
            </Button>{/snippet}
    </ContextMenu.Trigger><ContextMenu.Content>
        <ContextMenu.Group>
            <ContextMenu.Item>Open</ContextMenu.Item><ContextMenu.Item>
                Copy
            </ContextMenu.Item>
        </ContextMenu.Group>
    </ContextMenu.Content>
</ContextMenu.Root>
```
