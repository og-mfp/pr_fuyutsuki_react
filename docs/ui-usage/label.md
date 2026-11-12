> **Historical Svelte source reference:** This is preserved from `pr_fuyutsuki`, not current React documentation. Examples, routes, dependencies, commands and conclusions below describe the source at the time of writing and have not been verified for this React application. Do not use them as React instructions.

# Label

Renders an accessible label associated with controls.

Showcase: `/admin/ui/label`. Source: ui/components/Label.svelte (source-only path: `../../resources/js/pages/ui/components/Label.svelte`).

[Official documentation](https://shadcn-svelte.com/docs/components/label). Check the installed barrel for all exported parts.

```svelte
<script lang="ts">
    import { Input } from '@/components/ui/input';
    import { Label } from '@/components/ui/label';
    let value = $state('');
</script>

<div class="flex max-w-sm items-center gap-3">
    <Label for="ui-label">Name</Label><Input bind:value id="ui-label" />
</div>

<p class="mt-3" role="status">Current value: {value || 'Empty'}</p>
```
