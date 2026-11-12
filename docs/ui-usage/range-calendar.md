> **Historical Svelte source reference:** This is preserved from `pr_fuyutsuki`, not current React documentation. Examples, routes, dependencies, commands and conclusions below describe the source at the time of writing and have not been verified for this React application. Do not use them as React instructions.

# Range Calendar

A calendar component that allows users to select a range of dates.

Showcase: `/admin/ui/range-calendar`. Source: ui/components/RangeCalendar.svelte (source-only path: `../../resources/js/pages/ui/components/RangeCalendar.svelte`).

[Official documentation](https://shadcn-svelte.com/docs/components/range-calendar). Check the installed barrel for all exported parts.

```svelte
<script lang="ts">
    import CalendarIcon from '@lucide/svelte/icons/calendar';
    import {
        CalendarDate,
        DateFormatter,
        getLocalTimeZone,
        today,
    } from '@internationalized/date';
    import type { DateRange } from 'bits-ui';
    import { Button } from '@/components/ui/button';
    import * as Field from '@/components/ui/field';
    import * as Popover from '@/components/ui/popover';
    import { RangeCalendar } from '@/components/ui/range-calendar';

    const formatter = new DateFormatter('en-US', { dateStyle: 'medium' });
    const year = today(getLocalTimeZone()).year;
    let value = $state<DateRange | undefined>({
        start: new CalendarDate(year, 1, 20),
        end: new CalendarDate(year, 2, 9),
    });
</script>

<Field.Field class="w-full max-w-sm">
    <Field.Label for="date-picker-range">Date range</Field.Label>
    <Popover.Root>
        <Popover.Trigger id="date-picker-range">
            {#snippet child({ props })}
                <Button
                    {...props}
                    variant="outline"
                    class="w-full justify-start px-2.5 font-normal"
                >
                    <CalendarIcon data-icon="inline-start" />
                    {#if value?.start}
                        {#if value.end}
                            {formatter.format(
                                value.start.toDate(getLocalTimeZone()),
                            )} – {formatter.format(
                                value.end.toDate(getLocalTimeZone()),
                            )}
                        {:else}
                            {formatter.format(
                                value.start.toDate(getLocalTimeZone()),
                            )}
                        {/if}
                    {:else}
                        Pick a date range
                    {/if}
                </Button>
            {/snippet}
        </Popover.Trigger>
        <Popover.Content class="w-auto p-0" align="start">
            <RangeCalendar
                bind:value
                numberOfMonths={1}
                aria-label="Select a date range"
            />
        </Popover.Content>
    </Popover.Root>
</Field.Field>
```
