> **Historical Svelte source reference:** This is preserved from `pr_fuyutsuki`, not current React documentation. Examples, routes, dependencies, commands and conclusions below describe the source at the time of writing and have not been verified for this React application. Do not use them as React instructions.

# Popover

Displays rich content in a portal, triggered by a button.

Showcase: `/admin/ui/popover`. Source: ui/components/Popover.svelte (source-only path: `../../resources/js/pages/ui/components/Popover.svelte`).

[Official documentation](https://shadcn-svelte.com/docs/components/popover). Check the installed barrel for all exported parts.

```svelte
<script lang="ts">
    import { Button } from '@/components/ui/button';
    import * as Popover from '@/components/ui/popover';
</script>

<Popover.Root>
    <Popover.Trigger>
        {#snippet child({ props })}<Button variant="outline" {...props}>
                Open popover
            </Button>{/snippet}
    </Popover.Trigger><Popover.Content>
        <Popover.Header>
            <Popover.Title>Popover</Popover.Title><Popover.Description>
                More information here.
            </Popover.Description>
        </Popover.Header>
    </Popover.Content>
</Popover.Root>
```
