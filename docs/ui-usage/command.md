> **Historical Svelte source reference:** This is preserved from `pr_fuyutsuki`, not current React documentation. Examples, routes, dependencies, commands and conclusions below describe the source at the time of writing and have not been verified for this React application. Do not use them as React instructions.

# Command

Command menu for search and quick actions.

Showcase: `/admin/ui/command`. Source: ui/components/Command.svelte (source-only path: `../../resources/js/pages/ui/components/Command.svelte`).

[Official documentation](https://shadcn-svelte.com/docs/components/command). Check the installed barrel for all exported parts.

```svelte
<script lang="ts">
    import * as Command from '@/components/ui/command';
</script>

<Command.Root class="max-w-sm rounded-lg border">
    <Command.Input placeholder="Search commands" /><Command.List>
        <Command.Empty>No results.</Command.Empty><Command.Group
            heading="Suggestions"
        >
            <Command.Item value="profile">Profile</Command.Item><Command.Item
                value="settings"
            >
                Settings
            </Command.Item>
        </Command.Group>
    </Command.List>
</Command.Root>
```
