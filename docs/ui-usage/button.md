> **Historical Svelte source reference:** This is preserved from `pr_fuyutsuki`, not current React documentation. Examples, routes, dependencies, commands and conclusions below describe the source at the time of writing and have not been verified for this React application. Do not use them as React instructions.

# Button

Displays a button or a component that looks like a button.

Showcase: `/admin/ui/button`. Source: ui/components/Button.svelte (source-only path: `../../resources/js/pages/ui/components/Button.svelte`).

[Official documentation](https://shadcn-svelte.com/docs/components/button). Check the installed barrel for all exported parts.

```svelte
<script lang="ts">
    import { Button } from '@/components/ui/button';
    import { Spinner } from '@/components/ui/spinner';
    let clicks = $state(0);
    const variants = [
        'default',
        'secondary',
        'outline',
        'ghost',
        'destructive',
        'link',
    ] as const;
    const sizes = ['xs', 'sm', 'default', 'lg'] as const;
</script>

<div class="flex flex-col gap-4">
    <div class="flex flex-wrap gap-2">
        {#each variants as variant}<Button {variant} onclick={() => clicks++}>
                {variant}
            </Button>{/each}
    </div>
    <div class="flex flex-wrap items-center gap-2">
        {#each sizes as size}<Button {size} onclick={() => clicks++}>
                {size}
            </Button>{/each}<Button disabled>Disabled</Button><Button disabled>
            <Spinner data-icon="inline-start" />Saving
        </Button>
    </div>
    <p role="status">Button clicks: {clicks}</p>
</div>
```
