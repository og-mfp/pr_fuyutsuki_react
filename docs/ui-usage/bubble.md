> **Historical Svelte source reference:** This is preserved from `pr_fuyutsuki`, not current React documentation. Examples, routes, dependencies, commands and conclusions below describe the source at the time of writing and have not been verified for this React application. Do not use them as React instructions.

# Bubble

Displays conversational content in a message bubble. Supports variants, alignment, grouping, reactions, and collapsible content.

Showcase: `/admin/ui/bubble`. Source: ui/components/Bubble.svelte (source-only path: `../../resources/js/pages/ui/components/Bubble.svelte`).

[Official documentation](https://shadcn-svelte.com/docs/components/bubble). Check the installed barrel for all exported parts.

```svelte
<script lang="ts">
    import * as Bubble from '@/components/ui/bubble';
    const variants = [
        'default',
        'secondary',
        'muted',
        'tinted',
        'outline',
        'ghost',
        'destructive',
    ] as const;
</script>

<div class="flex flex-col gap-4">
    {#each variants as variant}<Bubble.Root {variant}>
            <Bubble.Content>{variant} message bubble</Bubble.Content>
        </Bubble.Root>{/each}<Bubble.Root align="end">
        <Bubble.Content>Aligned to the end</Bubble.Content>
    </Bubble.Root>
</div>
```
