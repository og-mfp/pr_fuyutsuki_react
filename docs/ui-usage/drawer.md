> **Historical Svelte source reference:** This is preserved from `pr_fuyutsuki`, not current React documentation. Examples, routes, dependencies, commands and conclusions below describe the source at the time of writing and have not been verified for this React application. Do not use them as React instructions.

# Drawer

A drawer component for Svelte.

Showcase: `/admin/ui/drawer`. Source: ui/components/Drawer.svelte (source-only path: `../../resources/js/pages/ui/components/Drawer.svelte`).

[Official documentation](https://shadcn-svelte.com/docs/components/drawer). Check the installed barrel for all exported parts.

```svelte
<script lang="ts">
    import { Button } from '@/components/ui/button';
    import * as Drawer from '@/components/ui/drawer';
</script>

<Drawer.Root>
    <Drawer.Trigger>
        {#snippet child({ props })}<Button variant="outline" {...props}>
                Open drawer
            </Button>{/snippet}
    </Drawer.Trigger><Drawer.Content>
        <Drawer.Header>
            <Drawer.Title>Example drawer</Drawer.Title><Drawer.Description>
                Drawer content goes here.
            </Drawer.Description>
        </Drawer.Header>
    </Drawer.Content>
</Drawer.Root>
```
