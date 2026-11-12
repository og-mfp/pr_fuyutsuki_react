> **Historical Svelte source reference:** This is preserved from `pr_fuyutsuki`, not current React documentation. Examples, routes, dependencies, commands and conclusions below describe the source at the time of writing and have not been verified for this React application. Do not use them as React instructions.

# Badge

Displays a badge or a component that looks like a badge.

Showcase: `/admin/ui/badge`. Source: ui/components/Badge.svelte (source-only path: `../../resources/js/pages/ui/components/Badge.svelte`).

[Official documentation](https://shadcn-svelte.com/docs/components/badge). Check the installed barrel for all exported parts.

```svelte
<script lang="ts">
    import { Badge } from '@/components/ui/badge';
</script>

<div class="flex flex-wrap gap-2">
    <Badge>Default</Badge><Badge variant="secondary">Secondary</Badge><Badge
        variant="destructive"
    >
        Destructive
    </Badge><Badge variant="outline">Outline</Badge><Badge variant="ghost">
        Ghost
    </Badge><Badge variant="link">Link</Badge>
</div>
```
