> **Historical Svelte source reference:** This is preserved from `pr_fuyutsuki`, not current React documentation. Examples, routes, dependencies, commands and conclusions below describe the source at the time of writing and have not been verified for this React application. Do not use them as React instructions.

# Dropdown Menu

Displays a menu to the user such as a set of actions or functions triggered by a button.

Showcase: `/admin/ui/dropdown-menu`. Source: ui/components/DropdownMenu.svelte (source-only path: `../../resources/js/pages/ui/components/DropdownMenu.svelte`).

[Official documentation](https://shadcn-svelte.com/docs/components/dropdown-menu). Check the installed barrel for all exported parts.

```svelte
<script lang="ts">
    import { Button } from '@/components/ui/button';
    import * as DropdownMenu from '@/components/ui/dropdown-menu';
    let action = $state('None');
    let notifications = $state(true);
</script>

<div class="flex flex-col gap-4">
    <DropdownMenu.Root>
        <DropdownMenu.Trigger>
            {#snippet child({ props })}<Button {...props} variant="outline">
                    Open actions
                </Button>{/snippet}
        </DropdownMenu.Trigger><DropdownMenu.Content>
            <DropdownMenu.Group>
                <DropdownMenu.Label>
                    Actions
                </DropdownMenu.Label><DropdownMenu.Item
                    onSelect={() => (action = 'Profile')}
                >
                    Profile
                </DropdownMenu.Item><DropdownMenu.Item
                    onSelect={() => (action = 'Settings')}
                >
                    Settings
                </DropdownMenu.Item><DropdownMenu.Item disabled>
                    Unavailable
                </DropdownMenu.Item>
            </DropdownMenu.Group><DropdownMenu.Separator
            /><DropdownMenu.CheckboxItem bind:checked={notifications}>
                Notifications
            </DropdownMenu.CheckboxItem>
        </DropdownMenu.Content>
    </DropdownMenu.Root>
    <p role="status">
        Action: {action}. Notifications: {notifications ? 'on' : 'off'}.
    </p>
</div>
```
