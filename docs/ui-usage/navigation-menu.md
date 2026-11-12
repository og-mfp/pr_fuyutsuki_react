> **Historical Svelte source reference:** This is preserved from `pr_fuyutsuki`, not current React documentation. Examples, routes, dependencies, commands and conclusions below describe the source at the time of writing and have not been verified for this React application. Do not use them as React instructions.

# Navigation Menu

A collection of links for navigating websites.

Showcase: `/admin/ui/navigation-menu`. Source: ui/components/NavigationMenu.svelte (source-only path: `../../resources/js/pages/ui/components/NavigationMenu.svelte`).

[Official documentation](https://shadcn-svelte.com/docs/components/navigation-menu). Check the installed barrel for all exported parts.

```svelte
<script lang="ts">
    import * as NavigationMenu from '@/components/ui/navigation-menu';
</script>

<NavigationMenu.Root>
    <NavigationMenu.List>
        <NavigationMenu.Item>
            <NavigationMenu.Link href="/admin/ui">
                Components
            </NavigationMenu.Link>
        </NavigationMenu.Item>
    </NavigationMenu.List>
</NavigationMenu.Root>
```
