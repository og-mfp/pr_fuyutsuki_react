> **Historical Svelte source reference:** This is preserved from `pr_fuyutsuki`, not current React documentation. Examples, routes, dependencies, commands and conclusions below describe the source at the time of writing and have not been verified for this React application. Do not use them as React instructions.

# Marker

Displays an inline status, system note, bordered row, or labeled separator in a conversation.

Showcase: `/admin/ui/marker`. Source: ui/components/Marker.svelte (source-only path: `../../resources/js/pages/ui/components/Marker.svelte`).

[Official documentation](https://shadcn-svelte.com/docs/components/marker). Check the installed barrel for all exported parts.

```svelte
<script lang="ts">
    import * as Marker from '@/components/ui/marker';
</script>

<div class="flex max-w-lg flex-col gap-4">
    <Marker.Root><Marker.Content>Default marker</Marker.Content></Marker.Root>
    <Marker.Root variant="separator">
        <Marker.Content>Separator marker</Marker.Content>
    </Marker.Root>
    <Marker.Root variant="border">
        <Marker.Content>Border marker</Marker.Content>
    </Marker.Root>
</div>
```
