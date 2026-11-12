> **Historical Svelte source reference:** This is preserved from `pr_fuyutsuki`, not current React documentation. Examples, routes, dependencies, commands and conclusions below describe the source at the time of writing and have not been verified for this React application. Do not use them as React instructions.

# Kbd

Used to display textual user input from keyboard.

Showcase: `/admin/ui/kbd`. Source: ui/components/Kbd.svelte (source-only path: `../../resources/js/pages/ui/components/Kbd.svelte`).

[Official documentation](https://shadcn-svelte.com/docs/components/kbd). Check the installed barrel for all exported parts.

```svelte
<script lang="ts">
    import * as Kbd from '@/components/ui/kbd';
</script>

<p>Press <Kbd.Root>Ctrl</Kbd.Root> + <Kbd.Root>K</Kbd.Root></p>
```
