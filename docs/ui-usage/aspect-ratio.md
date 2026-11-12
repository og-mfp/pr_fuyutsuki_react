> **Historical Svelte source reference:** This is preserved from `pr_fuyutsuki`, not current React documentation. Examples, routes, dependencies, commands and conclusions below describe the source at the time of writing and have not been verified for this React application. Do not use them as React instructions.

# Aspect Ratio

Displays content within a desired ratio.

Showcase: `/admin/ui/aspect-ratio`. Source: ui/components/AspectRatio.svelte (source-only path: `../../resources/js/pages/ui/components/AspectRatio.svelte`).

[Official documentation](https://shadcn-svelte.com/docs/components/aspect-ratio). Check the installed barrel for all exported parts.

```svelte
<script lang="ts">
    import { AspectRatio } from '@/components/ui/aspect-ratio';
</script>

<div class="grid max-w-2xl gap-4 sm:grid-cols-2">
    <AspectRatio
        ratio={16 / 9}
        class="rounded-lg bg-muted flex items-center justify-center"
    >
        16:9
    </AspectRatio>
    <AspectRatio
        ratio={1}
        class="max-w-48 rounded-lg bg-muted flex items-center justify-center"
    >
        1:1
    </AspectRatio>
</div>
```
