> **Historical Svelte source reference:** This is preserved from `pr_fuyutsuki`, not current React documentation. Examples, routes, dependencies, commands and conclusions below describe the source at the time of writing and have not been verified for this React application. Do not use them as React instructions.

# Empty

Use the Empty component to display an empty state.

Showcase: `/admin/ui/empty`. Source: ui/components/Empty.svelte (source-only path: `../../resources/js/pages/ui/components/Empty.svelte`).

[Official documentation](https://shadcn-svelte.com/docs/components/empty). Check the installed barrel for all exported parts.

```svelte
<script lang="ts">
    import * as Empty from '@/components/ui/empty';
</script>

<Empty.Root>
    <Empty.Header>
        <Empty.Title>Nothing here yet</Empty.Title><Empty.Description>
            Content will appear here when available.
        </Empty.Description>
    </Empty.Header>
</Empty.Root>
```
