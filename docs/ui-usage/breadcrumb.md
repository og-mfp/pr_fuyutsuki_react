> **Historical Svelte source reference:** This is preserved from `pr_fuyutsuki`, not current React documentation. Examples, routes, dependencies, commands and conclusions below describe the source at the time of writing and have not been verified for this React application. Do not use them as React instructions.

# Breadcrumb

Displays the path to the current resource using a hierarchy of links.

Showcase: `/admin/ui/breadcrumb`. Source: ui/components/Breadcrumb.svelte (source-only path: `../../resources/js/pages/ui/components/Breadcrumb.svelte`).

[Official documentation](https://shadcn-svelte.com/docs/components/breadcrumb). Check the installed barrel for all exported parts.

```svelte
<script lang="ts">
    import * as Breadcrumb from '@/components/ui/breadcrumb';
</script>

<Breadcrumb.Root>
    <Breadcrumb.List>
        <Breadcrumb.Item>
            <Breadcrumb.Link href="/admin/ui">UI</Breadcrumb.Link>
        </Breadcrumb.Item><Breadcrumb.Separator /><Breadcrumb.Item>
            <Breadcrumb.Page>Breadcrumb</Breadcrumb.Page>
        </Breadcrumb.Item>
    </Breadcrumb.List>
</Breadcrumb.Root>
```
