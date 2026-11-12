> **Historical Svelte source reference:** This is preserved from `pr_fuyutsuki`, not current React documentation. Examples, routes, dependencies, commands and conclusions below describe the source at the time of writing and have not been verified for this React application. Do not use them as React instructions.

# Tabs

Display one tab panel at a time using the reusable Bits UI-based components.

Showcase: `/admin/ui/tabs`. Source: Tabs.svelte (source-only path: `../../resources/js/pages/ui/components/Tabs.svelte`).

[Official documentation](https://shadcn-svelte.com/docs/components/tabs). The showcase includes default, line, vertical, disabled, and icon examples.

```svelte
<script lang="ts">
    import * as Tabs from '@/components/ui/tabs';
</script>

<Tabs.Root value="account">
    <Tabs.List aria-label="Account settings">
        <Tabs.Trigger value="account">Account</Tabs.Trigger>
        <Tabs.Trigger value="password">Password</Tabs.Trigger>
    </Tabs.List>
    <Tabs.Content value="account">Manage your account.</Tabs.Content>
    <Tabs.Content value="password">Manage your password.</Tabs.Content>
</Tabs.Root>
```

Use `variant="line"` on `Tabs.List` for the line style, `orientation="vertical"` on `Tabs.Root` for vertical tabs, and `disabled` on a trigger to prevent selection.
