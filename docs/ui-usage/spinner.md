> **Historical Svelte source reference:** This is preserved from `pr_fuyutsuki`, not current React documentation. Examples, routes, dependencies, commands and conclusions below describe the source at the time of writing and have not been verified for this React application. Do not use them as React instructions.

# Spinner

An indicator that can be used to show a loading state.

Showcase: `/admin/ui/spinner`. Source: ui/components/Spinner.svelte (source-only path: `../../resources/js/pages/ui/components/Spinner.svelte`).

[Official documentation](https://shadcn-svelte.com/docs/components/spinner). Check the installed barrel for all exported parts.

```svelte
<script lang="ts">
    import { Spinner } from '@/components/ui/spinner';
</script>

<div class="flex items-center gap-4">
    <Spinner class="size-3" aria-label="Small loading indicator" /><Spinner
        aria-label="Loading indicator"
    /><Spinner class="size-6" aria-label="Large loading indicator" />
</div>
```
