> **Historical Svelte source reference:** This is preserved from `pr_fuyutsuki`, not current React documentation. Examples, routes, dependencies, commands and conclusions below describe the source at the time of writing and have not been verified for this React application. Do not use them as React instructions.

# Dialog

A window overlaid on either the primary window or another dialog window, rendering the content underneath inert.

Showcase: `/admin/ui/dialog`. Source: ui/components/Dialog.svelte (source-only path: `../../resources/js/pages/ui/components/Dialog.svelte`).

[Official documentation](https://shadcn-svelte.com/docs/components/dialog). Check the installed barrel for all exported parts.

```svelte
<script lang="ts">
    import { Button } from '@/components/ui/button';
    import * as Dialog from '@/components/ui/dialog';
    import * as Field from '@/components/ui/field';
    import { Input } from '@/components/ui/input';
    let open = $state(false);
    let name = $state('Ada');
    let saved = $state('');
</script>

<div class="flex flex-col gap-4">
    <Dialog.Root bind:open>
        <Dialog.Trigger>
            {#snippet child({ props })}<Button {...props} variant="outline">
                    Edit profile example
                </Button>{/snippet}
        </Dialog.Trigger>
        <Dialog.Content>
            <Dialog.Header>
                <Dialog.Title>Edit profile</Dialog.Title><Dialog.Description>
                    Tab stays inside this dialog. Escape closes it and returns
                    focus.
                </Dialog.Description>
            </Dialog.Header>
            <form
                onsubmit={(event) => {
                    event.preventDefault();
                    saved = name;
                    open = false;
                }}
                class="flex flex-col gap-4"
            >
                <Field.Field>
                    <Field.Label for="dialog-name">Name</Field.Label><Input
                        id="dialog-name"
                        bind:value={name}
                        required
                    />
                </Field.Field><Dialog.Footer>
                    <Dialog.Close>
                        {#snippet child({ props })}<Button
                                {...props}
                                variant="outline"
                            >
                                Cancel
                            </Button>{/snippet}
                    </Dialog.Close><Button type="submit">Save example</Button>
                </Dialog.Footer>
            </form>
        </Dialog.Content>
    </Dialog.Root>
    <Dialog.Root>
        <Dialog.Trigger>
            {#snippet child({ props })}<Button {...props} variant="outline">
                    Protected form example
                </Button>{/snippet}
        </Dialog.Trigger><Dialog.Content
            escapeKeydownBehavior="ignore"
            interactOutsideBehavior="ignore"
        >
            <Dialog.Header>
                <Dialog.Title>Protected form</Dialog.Title><Dialog.Description>
                    Explicit close is required, like the user administration
                    forms.
                </Dialog.Description>
            </Dialog.Header><Dialog.Footer showCloseButton />
        </Dialog.Content>
    </Dialog.Root>
    <p role="status">Saved name: {saved || 'Nothing yet'}</p>
</div>
```
