> **Historical Svelte source reference:** This is preserved from `pr_fuyutsuki`, not current React documentation. Examples, routes, dependencies, commands and conclusions below describe the source at the time of writing and have not been verified for this React application. Do not use them as React instructions.

# Skeleton

Use to show a placeholder while content is loading.

Showcase: `/admin/ui/skeleton`. Source: ui/components/Skeleton.svelte (source-only path: `../../resources/js/pages/ui/components/Skeleton.svelte`).

[Official documentation](https://shadcn-svelte.com/docs/components/skeleton). Check the installed barrel for all exported parts.

```svelte
<script lang="ts">
    import { Skeleton } from '@/components/ui/skeleton';
</script>

<div class="grid max-w-2xl gap-6 sm:grid-cols-2">
    <div class="flex items-center gap-3">
        <Skeleton class="size-10 rounded-full" />
        <div class="flex flex-1 flex-col gap-2">
            <Skeleton class="h-4 w-3/4" /><Skeleton class="h-3 w-1/2" />
        </div>
    </div>
    <div class="flex flex-col gap-3">
        <Skeleton class="h-24 w-full rounded-lg" /><Skeleton
            class="h-4 w-2/3"
        />
    </div>
</div>
```
