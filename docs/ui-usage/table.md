> **Historical Svelte source reference:** This is preserved from `pr_fuyutsuki`, not current React documentation. Examples, routes, dependencies, commands and conclusions below describe the source at the time of writing and have not been verified for this React application. Do not use them as React instructions.

# Table

A responsive table component.

Showcase: `/admin/ui/table`. Source: ui/components/Table.svelte (source-only path: `../../resources/js/pages/ui/components/Table.svelte`).

[Official documentation](https://shadcn-svelte.com/docs/components/table). Check the installed barrel for all exported parts.

```svelte
<script lang="ts">
    import * as Table from '@/components/ui/table';
</script>

<Table.Root>
    <Table.Header>
        <Table.Row>
            <Table.Head>Name</Table.Head><Table.Head>Status</Table.Head>
        </Table.Row>
    </Table.Header><Table.Body>
        <Table.Row>
            <Table.Cell>Alpha</Table.Cell><Table.Cell>Active</Table.Cell>
        </Table.Row><Table.Row>
            <Table.Cell>Beta</Table.Cell><Table.Cell>Pending</Table.Cell>
        </Table.Row><Table.Row>
            <Table.Cell>Gamma</Table.Cell><Table.Cell>Archived</Table.Cell>
        </Table.Row>
    </Table.Body>
</Table.Root>
```
