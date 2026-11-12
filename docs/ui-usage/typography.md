> **Historical Svelte source reference:** This is preserved from `pr_fuyutsuki`, not current React documentation. Examples, routes, dependencies, commands and conclusions below describe the source at the time of writing and have not been verified for this React application. Do not use them as React instructions.

# Typography

Styles for headings, paragraphs, lists, etc.

Showcase: `/admin/ui/typography`. Source: ui/components/Typography.svelte (source-only path: `../../resources/js/pages/ui/components/Typography.svelte`).

[Official documentation](https://shadcn-svelte.com/docs/components/typography). Check the installed barrel for all exported parts.

```svelte
<script lang="ts">
</script>

<article class="flex max-w-prose flex-col gap-4">
    <h2 class="text-3xl font-semibold tracking-tight">Readable content</h2>
    <p class="leading-7">
        Use semantic headings, paragraphs and lists. Typography is a documented
        composition, not an installed primitive.
    </p>
    <blockquote class="border-l-2 pl-6 italic">
        Content remains readable in both themes.
    </blockquote>
    <ul class="list-disc pl-6">
        <li>Clear headings</li>
        <li>Useful descriptions</li>
    </ul>
    <p class="text-sm text-muted-foreground">
        Supporting text and <code class="rounded bg-muted px-1">
            inline code
        </code>
        .
    </p>
</article>
```
