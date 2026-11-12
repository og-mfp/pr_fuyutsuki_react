> **Historical Svelte source reference:** This is preserved from `pr_fuyutsuki`, not current React documentation. Examples, routes, dependencies, commands and conclusions below describe the source at the time of writing and have not been verified for this React application. Do not use them as React instructions.

# Menubar

A visually persistent menu common in desktop applications that provides quick access to a consistent set of commands.

Showcase: `/admin/ui/menubar`. Source: ui/components/Menubar.svelte (source-only path: `../../resources/js/pages/ui/components/Menubar.svelte`).

[Official documentation](https://shadcn-svelte.com/docs/components/menubar). Check the installed barrel for all exported parts.

```svelte
<script lang="ts">
    import * as Menubar from '@/components/ui/menubar';
</script>

<Menubar.Root>
    <Menubar.Menu>
        <Menubar.Trigger>File</Menubar.Trigger><Menubar.Content>
            <Menubar.Group>
                <Menubar.Item>New</Menubar.Item><Menubar.Item>
                    Open
                </Menubar.Item>
            </Menubar.Group>
        </Menubar.Content>
    </Menubar.Menu>
</Menubar.Root>
```
