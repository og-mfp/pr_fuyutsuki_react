> **Historical Svelte source reference:** This is preserved from `pr_fuyutsuki`, not current React documentation. Examples, routes, dependencies, commands and conclusions below describe the source at the time of writing and have not been verified for this React application. Do not use them as React instructions.

# Item

A versatile component for displaying content with media, title, description, and actions.

Showcase: `/admin/ui/item`. Source: ui/components/Item.svelte (source-only path: `../../resources/js/pages/ui/components/Item.svelte`).

[Official documentation](https://shadcn-svelte.com/docs/components/item). Check the installed barrel for all exported parts.

```svelte
<script lang="ts">
    import * as Item from '@/components/ui/item';
</script>

<div class="flex max-w-lg flex-col gap-2">
    <Item.Root>
        <Item.Content>
            <Item.Title>Default item</Item.Title><Item.Description>
                Transparent container.
            </Item.Description>
        </Item.Content>
    </Item.Root>
    <Item.Root variant="outline">
        <Item.Content>
            <Item.Title>Outline item</Item.Title><Item.Description>
                Visible border.
            </Item.Description>
        </Item.Content>
    </Item.Root>
    <Item.Root variant="muted">
        <Item.Content>
            <Item.Title>Muted item</Item.Title><Item.Description>
                Subtle background.
            </Item.Description>
        </Item.Content>
    </Item.Root>
</div>
```
