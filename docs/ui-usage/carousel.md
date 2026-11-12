> **Historical Svelte source reference:** This is preserved from `pr_fuyutsuki`, not current React documentation. Examples, routes, dependencies, commands and conclusions below describe the source at the time of writing and have not been verified for this React application. Do not use them as React instructions.

# Carousel

A carousel with motion and swipe built using Embla.

Showcase: `/admin/ui/carousel`. Source: ui/components/Carousel.svelte (source-only path: `../../resources/js/pages/ui/components/Carousel.svelte`).

[Official documentation](https://shadcn-svelte.com/docs/components/carousel). Check the installed barrel for all exported parts.

```svelte
<script lang="ts">
    import * as Carousel from '@/components/ui/carousel';
</script>

<Carousel.Root class="mx-auto w-[calc(100%_-_5rem)] max-w-xs">
    <Carousel.Content>
        {#each [1, 2, 3] as number}<Carousel.Item>
                <div
                    class="flex aspect-video items-center justify-center rounded-lg bg-muted text-2xl"
                >
                    {number}
                </div>
            </Carousel.Item>{/each}
    </Carousel.Content><Carousel.Previous /><Carousel.Next />
</Carousel.Root>
```
