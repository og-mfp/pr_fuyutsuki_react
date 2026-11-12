> **Historical Svelte source reference:** This is preserved from `pr_fuyutsuki`, not current React documentation. Examples, routes, dependencies, commands and conclusions below describe the source at the time of writing and have not been verified for this React application. Do not use them as React instructions.

# Date Picker

A date picker component with range and presets.

Showcase: `/admin/ui/date-picker`. Source: ui/components/DatePicker.svelte (source-only path: `../../resources/js/pages/ui/components/DatePicker.svelte`).

[Official documentation](https://shadcn-svelte.com/docs/components/date-picker). Check the installed barrel for all exported parts.

```svelte
<script lang="ts">
    import {
        DateFormatter,
        getLocalTimeZone,
        type DateValue,
    } from '@internationalized/date';
    import { Button } from '@/components/ui/button';
    import { Calendar } from '@/components/ui/calendar';
    import * as Popover from '@/components/ui/popover';
    let value = $state<DateValue>();
    const formatter = new DateFormatter('en-US', { dateStyle: 'long' });
</script>

<div class="flex flex-col gap-4">
    <Popover.Root>
        <Popover.Trigger>
            {#snippet child({ props })}<Button
                    {...props}
                    variant="outline"
                    aria-label="Choose a date"
                >
                    {value
                        ? formatter.format(value.toDate(getLocalTimeZone()))
                        : 'Choose a date'}
                </Button>{/snippet}
        </Popover.Trigger><Popover.Content class="w-auto p-0">
            <Calendar
                type="single"
                bind:value
                initialFocus
                captionLayout="dropdown"
            />
        </Popover.Content>
    </Popover.Root>
    <p role="status">Selected date: {value?.toString() || 'None'}</p>
</div>
```
