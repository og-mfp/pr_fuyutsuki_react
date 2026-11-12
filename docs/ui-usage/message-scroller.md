> **Historical Svelte source reference:** This is preserved from `pr_fuyutsuki`, not current React documentation. Examples, routes, dependencies, commands and conclusions below describe the source at the time of writing and have not been verified for this React application. Do not use them as React instructions.

# Message Scroller

A conversation viewport with scroll anchoring and a jump-to-latest control.

Showcase: `/admin/ui/message-scroller`. Source: ui/components/MessageScroller.svelte (source-only path: `../../resources/js/pages/ui/components/MessageScroller.svelte`).

[Official documentation](https://shadcn-svelte.com/docs/components/message-scroller). Check the installed barrel for all exported parts.

```svelte
<script lang="ts">
    import * as MessageScroller from '@/components/ui/message-scroller';
    import * as Bubble from '@/components/ui/bubble';
    import { Button } from '@/components/ui/button';
    let messages = $state(
        Array.from({ length: 15 }, (_, i) => ({
            id: String(i),
            text: 'Message ' + (i + 1),
        })),
    );
</script>

<div class="flex flex-col gap-4">
    <div class="h-64 rounded-lg border">
        <MessageScroller.Provider>
            <MessageScroller.Root>
                <MessageScroller.Viewport aria-label="Conversation">
                    <MessageScroller.Content>
                        {#each messages as message (message.id)}<MessageScroller.Item
                                messageId={message.id}
                            >
                                <Bubble.Root variant="secondary">
                                    <Bubble.Content>
                                        {message.text}
                                    </Bubble.Content>
                                </Bubble.Root>
                            </MessageScroller.Item>{/each}
                    </MessageScroller.Content>
                </MessageScroller.Viewport><MessageScroller.Button />
            </MessageScroller.Root>
        </MessageScroller.Provider>
    </div>
    <Button
        variant="outline"
        onclick={() =>
            (messages = [
                ...messages,
                {
                    id: String(messages.length),
                    text: 'New message ' + (messages.length + 1),
                },
            ])}
    >
        Add message
    </Button>
</div>
```
