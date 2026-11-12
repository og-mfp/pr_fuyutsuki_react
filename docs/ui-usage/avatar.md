> **Historical Svelte source reference:** This is preserved from `pr_fuyutsuki`, not current React documentation. Examples, routes, dependencies, commands and conclusions below describe the source at the time of writing and have not been verified for this React application. Do not use them as React instructions.

# Avatar

An image element with a fallback for representing the user.

Showcase: `/admin/ui/avatar`. Source: ui/components/Avatar.svelte (source-only path: `../../resources/js/pages/ui/components/Avatar.svelte`).

[Official documentation](https://shadcn-svelte.com/docs/components/avatar). Check the installed barrel for all exported parts.

```svelte
<script lang="ts">
    import * as Avatar from '@/components/ui/avatar';
</script>

<div class="flex items-center gap-3">
    <Avatar.Root size="sm"><Avatar.Fallback>SM</Avatar.Fallback></Avatar.Root>
    <Avatar.Root><Avatar.Fallback>UI</Avatar.Fallback></Avatar.Root>
    <Avatar.Root size="lg"><Avatar.Fallback>LG</Avatar.Fallback></Avatar.Root>
</div>
```
