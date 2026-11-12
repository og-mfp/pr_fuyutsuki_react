> **Historical Svelte source reference:** This is preserved from `pr_fuyutsuki`, not current React documentation. Examples, routes, dependencies, commands and conclusions below describe the source at the time of writing and have not been verified for this React application. Do not use them as React instructions.

# Toggle

A two-state button that can be either on or off.

Showcase: `/admin/ui/toggle`. Source: ui/components/Toggle.svelte (source-only path: `../../resources/js/pages/ui/components/Toggle.svelte`).

[Official documentation](https://shadcn-svelte.com/docs/components/toggle). Check the installed barrel for all exported parts.

```svelte
<script lang="ts">
    import { Toggle } from '@/components/ui/toggle';

    let pressed = $state(true);
</script>

<div class="flex flex-wrap items-center gap-2">
    <Toggle aria-label="Default toggle">Default</Toggle><Toggle
        aria-label="Outline toggle"
        variant="outline"
    >
        Outline
    </Toggle><Toggle aria-label="Pressed toggle" bind:pressed>
        Pressed: {pressed ? 'on' : 'off'}
    </Toggle><Toggle aria-label="Small toggle" size="sm">Small</Toggle><Toggle
        aria-label="Large toggle"
        size="lg"
    >
        Large
    </Toggle><Toggle aria-label="Disabled toggle" disabled>Disabled</Toggle>
</div>
```
