> **Historical Svelte source reference:** This is preserved from `pr_fuyutsuki`, not current React documentation. Examples, routes, dependencies, commands and conclusions below describe the source at the time of writing and have not been verified for this React application. Do not use them as React instructions.

# Alert

Displays a callout for user attention.

Showcase: `/admin/ui/alert`. Source: ui/components/Alert.svelte (source-only path: `../../resources/js/pages/ui/components/Alert.svelte`).

[Official documentation](https://shadcn-svelte.com/docs/components/alert). Check the installed barrel for all exported parts.

```svelte
<script lang="ts">
    import * as Alert from '@/components/ui/alert';
</script>

<div class="flex flex-col gap-4">
    <Alert.Root>
        <Alert.Title>Information</Alert.Title><Alert.Description>
            Your work is saved locally.
        </Alert.Description>
    </Alert.Root><Alert.Root variant="destructive">
        <Alert.Title>Unable to save</Alert.Title><Alert.Description>
            Check the highlighted fields before submitting again.
        </Alert.Description>
    </Alert.Root>
</div>
```
