> **Historical Svelte source reference:** This is preserved from `pr_fuyutsuki`, not current React documentation. Examples, routes, dependencies, commands and conclusions below describe the source at the time of writing and have not been verified for this React application. Do not use them as React instructions.

# Attachment

Displays a file or image attachment with media, metadata, upload state, and actions.

Showcase: `/admin/ui/attachment`. Source: ui/components/Attachment.svelte (source-only path: `../../resources/js/pages/ui/components/Attachment.svelte`).

[Official documentation](https://shadcn-svelte.com/docs/components/attachment). Check the installed barrel for all exported parts.

```svelte
<script lang="ts">
    import * as Attachment from '@/components/ui/attachment';
</script>

<div class="flex flex-wrap items-start gap-4">
    <Attachment.Root>
        <Attachment.Content>
            <Attachment.Title>
                design.pdf
            </Attachment.Title><Attachment.Description>
                Default size
            </Attachment.Description>
        </Attachment.Content>
    </Attachment.Root>
    <Attachment.Root size="sm" state="uploading">
        <Attachment.Content>
            <Attachment.Title>
                photo.jpg
            </Attachment.Title><Attachment.Description>
                Uploading
            </Attachment.Description>
        </Attachment.Content>
    </Attachment.Root>
    <Attachment.Root orientation="vertical">
        <Attachment.Content>
            <Attachment.Title>
                notes.txt
            </Attachment.Title><Attachment.Description>
                Vertical
            </Attachment.Description>
        </Attachment.Content>
    </Attachment.Root>
</div>
```
