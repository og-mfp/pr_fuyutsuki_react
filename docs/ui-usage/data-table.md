> **Historical Svelte source reference:** This is preserved from `pr_fuyutsuki`, not current React documentation. Examples, routes, dependencies, commands and conclusions below describe the source at the time of writing and have not been verified for this React application. Do not use them as React instructions.

# Data Table

Powerful table and datagrids built using TanStack Table.

Showcase: `/admin/ui/data-table`. Source: ui/components/DataTable.svelte (source-only path: `../../resources/js/pages/ui/components/DataTable.svelte`).

[Official documentation](https://shadcn-svelte.com/docs/components/data-table). Check the installed barrel for all exported parts.

```svelte
<script lang="ts">
    import {
        columnFilteringFeature,
        columnVisibilityFeature,
        createFilteredRowModel,
        createPaginatedRowModel,
        createSortedRowModel,
        createTable,
        FlexRender,
        rowPaginationFeature,
        rowSortingFeature,
        tableFeatures,
        type ColumnDef,
    } from '@tanstack/svelte-table';
    import DataTable from '@/components/shared/DataTable.svelte';
    type Row = { name: string; status: string };
    const features = tableFeatures({
        columnFilteringFeature,
        filteredRowModel: createFilteredRowModel(),
        columnVisibilityFeature,
        rowSortingFeature,
        sortedRowModel: createSortedRowModel(),
        rowPaginationFeature,
        paginatedRowModel: createPaginatedRowModel(),
    });
    const columns: ColumnDef<typeof features, Row>[] = [
        {
            accessorKey: 'name',
            header: 'Name',
            filterFn: (row, _id, value: string) =>
                row.original.name.toLowerCase().includes(value.toLowerCase()),
        },
        { accessorKey: 'status', header: 'Status' },
    ];
    const data: Row[] = [
        { name: 'Ada', status: 'Active' },
        { name: 'Grace', status: 'Pending' },
        { name: 'Linus', status: 'Active' },
        { name: 'Margaret', status: 'Archived' },
        { name: 'Alan', status: 'Active' },
    ];
    const table = createTable({
        features,
        columns,
        data,
        initialState: { pagination: { pageIndex: 0, pageSize: 2 } },
    });
</script>

<DataTable
    {table}
    filterColumn="name"
    filterLabel="Filter names"
    filterPlaceholder="Search names"
    emptyMessage="No matching names."
>
    {#snippet header(header)}<FlexRender {header} />{/snippet}{#snippet cell(
        cell,
    )}{cell.getValue()}{/snippet}
</DataTable>
```
