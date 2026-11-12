> **Historical Svelte source reference:** This is preserved from `pr_fuyutsuki`, not current React documentation. Examples, routes, dependencies, commands and conclusions below describe the source at the time of writing and have not been verified for this React application. Do not use them as React instructions.

# Sidebar

A composable, themeable and customizable sidebar component.

Showcase: `/admin/ui/sidebar`. Source: ui/components/Sidebar.svelte (source-only path: `../../resources/js/pages/ui/components/Sidebar.svelte`).

[Official documentation](https://shadcn-svelte.com/docs/components/sidebar). Check the installed barrel for all exported parts.

```svelte
<script lang="ts">
    import * as Sidebar from '@/components/ui/sidebar';
</script>

<div class="flex flex-col gap-4">
    <p>
        This page uses the live application sidebar. Toggle it here or with
        Ctrl/Cmd+B; on mobile it opens as a modal sheet.
    </p>
    <Sidebar.Trigger /><Sidebar.Group class="max-w-xs rounded-lg border">
        <Sidebar.GroupLabel>Sample navigation</Sidebar.GroupLabel><Sidebar.Menu>
            <Sidebar.MenuItem>
                <Sidebar.MenuButton isActive>
                    {#snippet child({ props })}<a
                            {...props}
                            href="/admin/ui/sidebar"
                        >
                            Sidebar example
                        </a>{/snippet}
                </Sidebar.MenuButton>
            </Sidebar.MenuItem><Sidebar.MenuItem>
                <Sidebar.MenuButton>
                    {#snippet child({ props })}<a {...props} href="/admin/ui">
                            Catalog
                        </a>{/snippet}
                </Sidebar.MenuButton>
            </Sidebar.MenuItem>
        </Sidebar.Menu>
    </Sidebar.Group>
</div>
```
