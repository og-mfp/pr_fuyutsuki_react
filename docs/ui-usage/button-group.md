> **Historical Svelte source reference:** This is preserved from `pr_fuyutsuki`, not current React documentation. Examples, routes, dependencies, commands and conclusions below describe the source at the time of writing and have not been verified for this React application. Do not use them as React instructions.

# Button Group

A container that groups related buttons together with consistent styling.

Showcase: `/admin/ui/button-group`. Source: ui/components/ButtonGroup.svelte (source-only path: `../../resources/js/pages/ui/components/ButtonGroup.svelte`).

[Official documentation](https://shadcn-svelte.com/docs/components/button-group). Check the installed barrel for all exported parts.

```svelte
<script lang="ts">
    import { Button } from '@/components/ui/button';
    import * as ButtonGroup from '@/components/ui/button-group';
</script>

<div class="flex flex-wrap items-start gap-6">
    <ButtonGroup.Root>
        <Button variant="outline">Left</Button><Button variant="outline">
            Center
        </Button><Button variant="outline">Right</Button>
    </ButtonGroup.Root>
    <ButtonGroup.Root orientation="vertical">
        <Button variant="outline">Top</Button><Button variant="outline">
            Middle
        </Button><Button variant="outline">Bottom</Button>
    </ButtonGroup.Root>
</div>
```
