> **Historical Svelte source reference:** This is preserved from `pr_fuyutsuki`, not current React documentation. Examples, routes, dependencies, commands and conclusions below describe the source at the time of writing and have not been verified for this React application. Do not use them as React instructions.

# Card

Displays a card with header, content, and footer.

Showcase: `/admin/ui/card`. Source: ui/components/Card.svelte (source-only path: `../../resources/js/pages/ui/components/Card.svelte`).

[Official documentation](https://shadcn-svelte.com/docs/components/card). Check the installed barrel for all exported parts.

```svelte
<script lang="ts">
    import { Button } from '@/components/ui/button';
    import * as Card from '@/components/ui/card';
</script>

<div class="grid max-w-2xl gap-4 sm:grid-cols-2">
    <Card.Root>
        <Card.Header>
            <Card.Title>Basic card</Card.Title><Card.Description>
                A short description.
            </Card.Description>
        </Card.Header><Card.Content>Card content goes here.</Card.Content>
    </Card.Root>
    <Card.Root>
        <Card.Header>
            <Card.Title>Card with footer</Card.Title><Card.Description>
                Useful for actions.
            </Card.Description>
        </Card.Header><Card.Content>
            Additional content.
        </Card.Content><Card.Footer>
            <Button size="sm">Continue</Button>
        </Card.Footer>
    </Card.Root>
</div>
```
