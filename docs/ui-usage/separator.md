> **Historical Svelte source reference:** This is preserved from `pr_fuyutsuki`, not current React documentation. Examples, routes, dependencies, commands and conclusions below describe the source at the time of writing and have not been verified for this React application. Do not use them as React instructions.

# Separator

Visually or semantically separates content.

Showcase: `/admin/ui/separator`. Source: ui/components/Separator.svelte (source-only path: `../../resources/js/pages/ui/components/Separator.svelte`).

[Official documentation](https://shadcn-svelte.com/docs/components/separator). Check the installed barrel for all exported parts.

```svelte
<script lang="ts">
    import { Separator } from '@/components/ui/separator';
</script>

<div class="flex max-w-sm flex-col gap-6 text-sm">
    <div class="flex flex-col gap-4">
        <div class="flex flex-col gap-1.5">
            <div class="leading-none font-medium">shadcn/ui</div>
            <div class="text-muted-foreground">
                The Foundation for your Design System
            </div>
        </div>
        <Separator />
        <div>
            A set of beautifully designed components that you can customize,
            extend, and build on.
        </div>
    </div>

    <div class="flex h-5 items-center gap-4">
        <div>Blog</div>
        <Separator orientation="vertical" />
        <div>Docs</div>
        <Separator orientation="vertical" />
        <div>Source</div>
    </div>
</div>
```
