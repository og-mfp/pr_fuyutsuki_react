> **Historical Svelte source reference:** This is preserved from `pr_fuyutsuki`, not current React documentation. Examples, routes, dependencies, commands and conclusions below describe the source at the time of writing and have not been verified for this React application. Do not use them as React instructions.

# Alert Dialog

A modal dialog that interrupts the user with important content and expects a response.

Showcase: `/admin/ui/alert-dialog`. Source: ui/components/AlertDialog.svelte (source-only path: `../../resources/js/pages/ui/components/AlertDialog.svelte`).

[Official documentation](https://shadcn-svelte.com/docs/components/alert-dialog). Check the installed barrel for all exported parts.

```svelte
<script lang="ts">
    import * as AlertDialog from '@/components/ui/alert-dialog';
    import { Button } from '@/components/ui/button';
</script>

<AlertDialog.Root>
    <AlertDialog.Trigger>
        {#snippet child({ props })}<Button variant="outline" {...props}>
                Open alert dialog
            </Button>{/snippet}
    </AlertDialog.Trigger>
    <AlertDialog.Content>
        <AlertDialog.Header>
            <AlertDialog.Title>
                Continue?
            </AlertDialog.Title><AlertDialog.Description>
                This is an alert dialog.
            </AlertDialog.Description>
        </AlertDialog.Header><AlertDialog.Footer>
            <AlertDialog.Cancel>Cancel</AlertDialog.Cancel><AlertDialog.Action>
                Continue
            </AlertDialog.Action>
        </AlertDialog.Footer>
    </AlertDialog.Content>
</AlertDialog.Root>
```
