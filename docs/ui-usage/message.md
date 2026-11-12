> **Historical Svelte source reference:** This is preserved from `pr_fuyutsuki`, not current React documentation. Examples, routes, dependencies, commands and conclusions below describe the source at the time of writing and have not been verified for this React application. Do not use them as React instructions.

# Message

Displays a message in a conversation, with optional avatar, header, footer, and alignment.

Showcase: `/admin/ui/message`. Source: ui/components/Message.svelte (source-only path: `../../resources/js/pages/ui/components/Message.svelte`).

[Official documentation](https://shadcn-svelte.com/docs/components/message). Check the installed barrel for all exported parts.

```svelte
<script lang="ts">
    import * as Message from '@/components/ui/message';
    import * as Bubble from '@/components/ui/bubble';
    import * as Avatar from '@/components/ui/avatar';
</script>

<div class="flex flex-col gap-4">
    <Message.Root>
        <Message.Avatar>
            <Avatar.Root><Avatar.Fallback>AD</Avatar.Fallback></Avatar.Root>
        </Message.Avatar><Message.Content>
            <Message.Header>Ada</Message.Header><Bubble.Root
                variant="secondary"
            >
                <Bubble.Content>Can you review this example?</Bubble.Content>
            </Bubble.Root><Message.Footer>10:30</Message.Footer>
        </Message.Content>
    </Message.Root><Message.Root align="end">
        <Message.Content>
            <Message.Header>You</Message.Header><Bubble.Root>
                <Bubble.Content>
                    Yes, the layout supports both directions.
                </Bubble.Content>
            </Bubble.Root>
        </Message.Content>
    </Message.Root>
</div>
```
