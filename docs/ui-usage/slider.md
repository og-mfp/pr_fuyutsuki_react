> **Historical Svelte source reference:** This is preserved from `pr_fuyutsuki`, not current React documentation. Examples, routes, dependencies, commands and conclusions below describe the source at the time of writing and have not been verified for this React application. Do not use them as React instructions.

# Slider

An input where the user selects a value from within a given range.

Showcase: `/admin/ui/slider`. Source: ui/components/Slider.svelte (source-only path: `../../resources/js/pages/ui/components/Slider.svelte`).

[Official documentation](https://shadcn-svelte.com/docs/components/slider). Check the installed barrel for all exported parts.

```svelte
<script lang="ts">
    import { Slider } from '@/components/ui/slider';
    let value = $state(50);
</script>

<div class="flex max-w-md flex-col gap-6">
    <Slider type="single" bind:value aria-label="Single value" />
    <Slider type="single" value={50} step={10} aria-label="Stepper" />
    <Slider type="multiple" value={[25, 75]} aria-label="Range" />
    <Slider type="single" value={40} aria-label="Disabled" disabled />
</div>

<p class="mt-3" role="status">Current value: {value || 'Empty'}</p>
```
