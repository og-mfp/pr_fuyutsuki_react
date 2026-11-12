> **Historical Svelte source reference:** This is preserved from `pr_fuyutsuki`, not current React documentation. Examples, routes, dependencies, commands and conclusions below describe the source at the time of writing and have not been verified for this React application. Do not use them as React instructions.

# Sheet

Extends the Dialog component to display content that complements the main content of the screen.

Showcase: `/admin/ui/sheet`. Source: ui/components/Sheet.svelte (source-only path: `../../resources/js/pages/ui/components/Sheet.svelte`).

[Official documentation](https://shadcn-svelte.com/docs/components/sheet). Check the installed barrel for all exported parts.

```svelte
<script lang="ts">
    import { Button } from '@/components/ui/button';
    import * as Sheet from '@/components/ui/sheet';

    const sides = ['top', 'right', 'bottom', 'left'] as const;
</script>

<div class="flex flex-wrap gap-2">
    {#each sides as side (side)}
        <Sheet.Root>
            <Sheet.Trigger>
                {#snippet child({ props })}
                    <Button variant="outline" {...props}>
                        Open {side} sheet
                    </Button>
                {/snippet}
            </Sheet.Trigger>
            <Sheet.Content {side}>
                <Sheet.Header>
                    <Sheet.Title>Example sheet</Sheet.Title>
                    <Sheet.Description>
                        This panel opens from the {side} edge.
                    </Sheet.Description>
                </Sheet.Header>
                <Sheet.Footer>
                    <Sheet.Close>
                        {#snippet child({ props })}
                            <Button variant="outline" {...props}>Close</Button>
                        {/snippet}
                    </Sheet.Close>
                </Sheet.Footer>
            </Sheet.Content>
        </Sheet.Root>
    {/each}
</div>
```
