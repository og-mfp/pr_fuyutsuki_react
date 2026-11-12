> **Historical Svelte source reference:** This is preserved from `pr_fuyutsuki`, not current React documentation. Examples, routes, dependencies, commands and conclusions below describe the source at the time of writing and have not been verified for this React application. Do not use them as React instructions.

# Pagination

Pagination with page navigation, next and previous links.

Showcase: `/admin/ui/pagination`. Source: ui/components/Pagination.svelte (source-only path: `../../resources/js/pages/ui/components/Pagination.svelte`).

[Official documentation](https://shadcn-svelte.com/docs/components/pagination). Check the installed barrel for all exported parts.

```svelte
<script lang="ts">
    import * as Pagination from '@/components/ui/pagination';
    let page = $state(1);
</script>

<div class="flex flex-col gap-4">
    <Pagination.Root count={50} perPage={10} bind:page>
        {#snippet children({ pages, currentPage })}<Pagination.Content>
                <Pagination.Item>
                    <Pagination.Previous />
                </Pagination.Item>{#each pages as item (item.key)}{#if item.type === 'ellipsis'}<Pagination.Item
                        >
                            <Pagination.Ellipsis />
                        </Pagination.Item>{:else}<Pagination.Item>
                            <Pagination.Link
                                page={item}
                                isActive={currentPage === item.value}
                            >
                                {item.value}
                            </Pagination.Link>
                        </Pagination.Item>{/if}{/each}<Pagination.Item>
                    <Pagination.Next />
                </Pagination.Item>
            </Pagination.Content>{/snippet}
    </Pagination.Root>
    <p role="status">Page {page} of 5</p>
</div>
```
