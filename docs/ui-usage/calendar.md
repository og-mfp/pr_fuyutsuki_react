> **Historical Svelte source reference:** This is preserved from `pr_fuyutsuki`, not current React documentation. Examples, routes, dependencies, commands and conclusions below describe the source at the time of writing and have not been verified for this React application. Do not use them as React instructions.

# Calendar

A calendar component that allows users to select a date or a range of dates.

Showcase: `/admin/ui/calendar`. Source: ui/components/Calendar.svelte (source-only path: `../../resources/js/pages/ui/components/Calendar.svelte`).

[Official documentation](https://shadcn-svelte.com/docs/components/calendar). Check the installed barrel for all exported parts.

```svelte
<script lang="ts">
    import {
        getLocalTimeZone,
        today,
        type DateValue,
    } from '@internationalized/date';
    import { Button } from '@/components/ui/button';
    import { Calendar } from '@/components/ui/calendar';
    import * as Card from '@/components/ui/card';

    const currentDate = today(getLocalTimeZone());
    const presets = [
        { label: 'Today', date: currentDate },
        { label: 'Tomorrow', date: currentDate.add({ days: 1 }) },
        { label: 'In three days', date: currentDate.add({ days: 3 }) },
        { label: 'In one week', date: currentDate.add({ weeks: 1 }) },
    ];
    const unavailableDates = Array.from({ length: 5 }, (_, index) =>
        currentDate.add({ days: index + 3 }),
    );

    let basicValue = $state<DateValue | undefined>(currentDate);
    let selectorValue = $state<DateValue | undefined>(currentDate);
    let presetValue = $state<DateValue | undefined>(currentDate);
    let presetPlaceholder = $state<DateValue>(currentDate);
    let availabilityValue = $state<DateValue | undefined>(currentDate);

    function selectPreset(date: DateValue) {
        presetValue = date;
        presetPlaceholder = date;
    }

    function isUnavailable(date: DateValue) {
        return unavailableDates.some(
            (unavailableDate) => unavailableDate.compare(date) === 0,
        );
    }
</script>

<div class="grid gap-4 xl:grid-cols-2">
    <Card.Root>
        <Card.Header>
            <Card.Title>Basic</Card.Title>
            <Card.Description>Select a single date.</Card.Description>
        </Card.Header>
        <Card.Content class="flex justify-center">
            <Calendar
                type="single"
                bind:value={basicValue}
                aria-label="Basic calendar"
            />
        </Card.Content>
    </Card.Root>

    <Card.Root>
        <Card.Header>
            <Card.Title>Month and year selector</Card.Title>
            <Card.Description>
                Jump directly to another month or year.
            </Card.Description>
        </Card.Header>
        <Card.Content class="flex justify-center">
            <Calendar
                type="single"
                bind:value={selectorValue}
                captionLayout="dropdown"
                aria-label="Calendar with month and year selectors"
            />
        </Card.Content>
    </Card.Root>

    <Card.Root>
        <Card.Header>
            <Card.Title>Presets</Card.Title>
            <Card.Description>
                Select from the calendar or choose a common date.
            </Card.Description>
        </Card.Header>
        <Card.Content class="flex justify-center">
            <Calendar
                type="single"
                bind:value={presetValue}
                bind:placeholder={presetPlaceholder}
                fixedWeeks
                aria-label="Calendar with date presets"
            />
        </Card.Content>
        <Card.Footer class="flex flex-wrap gap-2">
            {#each presets as preset (preset.label)}
                <Button
                    size="sm"
                    variant="outline"
                    onclick={() => selectPreset(preset.date)}
                >
                    {preset.label}
                </Button>
            {/each}
        </Card.Footer>
    </Card.Root>

    <Card.Root>
        <Card.Header>
            <Card.Title>Unavailable dates</Card.Title>
            <Card.Description>
                The five dates beginning three days from today cannot be
                selected.
            </Card.Description>
        </Card.Header>
        <Card.Content class="flex justify-center">
            <Calendar
                type="single"
                bind:value={availabilityValue}
                isDateUnavailable={isUnavailable}
                aria-label="Calendar with unavailable dates"
            />
        </Card.Content>
    </Card.Root>
</div>
```
