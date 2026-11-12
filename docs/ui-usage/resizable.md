> **Historical Svelte source reference:** This is preserved from `pr_fuyutsuki`, not current React documentation. Examples, routes, dependencies, commands and conclusions below describe the source at the time of writing and have not been verified for this React application. Do not use them as React instructions.

# Resizable

Accessible resizable panel groups and layouts with keyboard support.

Showcase: `/admin/ui/resizable`. Source: ui/components/Resizable.svelte (source-only path: `../../resources/js/pages/ui/components/Resizable.svelte`).

[Official documentation](https://shadcn-svelte.com/docs/components/resizable). Check the installed barrel for all exported parts.

```svelte
<script lang="ts">
    import * as Resizable from '@/components/ui/resizable';
</script>

<Resizable.PaneGroup
    direction="horizontal"
    class="min-h-32 max-w-md rounded-lg border"
>
    <Resizable.Pane defaultSize={50}>
        <div class="p-4">Left</div>
    </Resizable.Pane><Resizable.Handle withHandle /><Resizable.Pane
        defaultSize={50}
    >
        <div class="p-4">Right</div>
    </Resizable.Pane>
</Resizable.PaneGroup>
```
