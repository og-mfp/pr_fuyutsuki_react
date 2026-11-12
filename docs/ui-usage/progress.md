> **Historical Svelte source reference:** This is preserved from `pr_fuyutsuki`, not current React documentation. Examples, routes, dependencies, commands and conclusions below describe the source at the time of writing and have not been verified for this React application. Do not use them as React instructions.

# Progress

Displays an indicator showing the completion progress of a task, typically displayed as a progress bar.

Showcase: `/admin/ui/progress`. Source: ui/components/Progress.svelte (source-only path: `../../resources/js/pages/ui/components/Progress.svelte`).

[Official documentation](https://shadcn-svelte.com/docs/components/progress). Check the installed barrel for all exported parts.

```svelte
<script lang="ts">
    import { Progress } from '@/components/ui/progress';
</script>

<div class="flex max-w-md flex-col gap-4">
    <div class="flex flex-col gap-1">
        <span class="text-muted-foreground text-xs">25%</span>
        <Progress value={25} />
    </div>
    <div class="flex flex-col gap-1">
        <span class="text-muted-foreground text-xs">65%</span>
        <Progress value={65} />
    </div>
    <div class="flex flex-col gap-1">
        <span class="text-muted-foreground text-xs">Complete</span>
        <Progress value={100} />
    </div>
</div>
```
