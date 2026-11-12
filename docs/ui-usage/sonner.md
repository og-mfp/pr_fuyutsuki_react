> **Historical Svelte source reference:** This is preserved from `pr_fuyutsuki`, not current React documentation. Examples, routes, dependencies, commands and conclusions below describe the source at the time of writing and have not been verified for this React application. Do not use them as React instructions.

# Sonner

An opinionated toast component for Svelte.

Showcase: `/admin/ui/sonner`. Source: ui/components/Sonner.svelte (source-only path: `../../resources/js/pages/ui/components/Sonner.svelte`).

[Official documentation](https://shadcn-svelte.com/docs/components/sonner). Check the installed barrel for all exported parts.

```svelte
<script lang="ts">
    import { toast } from 'svelte-sonner';
    import { Button } from '@/components/ui/button';
</script>

<div class="flex flex-wrap gap-3">
    <Button onclick={() => toast('Hello from the catalog')}>
        Default
    </Button><Button
        variant="outline"
        onclick={() => toast.success('Changes saved')}
    >
        Success
    </Button><Button
        variant="outline"
        onclick={() => toast.warning('Review your changes')}
    >
        Warning
    </Button><Button
        variant="outline"
        onclick={() => toast.info('More information')}
    >
        Information
    </Button><Button
        variant="destructive"
        onclick={() => toast.error('Example error')}
    >
        Error
    </Button>
</div>
```
