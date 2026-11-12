> **Historical Svelte source reference:** This is preserved from `pr_fuyutsuki`, not current React documentation. Examples, routes, dependencies, commands and conclusions below describe the source at the time of writing and have not been verified for this React application. Do not use them as React instructions.

# Questionnaire

A multi-step questionnaire with single-choice, multiple-choice, freeform, and skippable questions.

Showcase: `/admin/ui/questionnaire`. Source: ui/components/Questionnaire.svelte (source-only path: `../../resources/js/pages/ui/components/Questionnaire.svelte`).

[Official documentation](https://shadcn-svelte.com/docs/components/questionnaire). Check the installed barrel for all exported parts.

```svelte
<script lang="ts">
    import * as Questionnaire from '@/components/ui/questionnaire';
    const items = [
        {
            name: 'frequency',
            required: true,
            choices: [
                { value: 'daily', label: 'Daily' },
                { value: 'weekly', label: 'Weekly' },
            ],
        },
    ] as const;
    let answer = $state('');
</script>

<div class="flex flex-col gap-4">
    <Questionnaire.Root
        {items}
        onSubmit={(event) => {
            event.preventDefault();
            answer = String(
                new FormData(event.currentTarget as HTMLFormElement).get(
                    'frequency',
                ),
            );
        }}
    >
        <Questionnaire.Item name="frequency" required>
            <Questionnaire.Title>
                How often do you want updates?
            </Questionnaire.Title><Questionnaire.Description>
                Select one answer and submit.
            </Questionnaire.Description><Questionnaire.Choices>
                <Questionnaire.Choice value="daily">
                    Daily
                </Questionnaire.Choice><Questionnaire.Choice value="weekly">
                    Weekly
                </Questionnaire.Choice>
            </Questionnaire.Choices>
        </Questionnaire.Item><Questionnaire.Actions>
            <Questionnaire.Submit>Submit answer</Questionnaire.Submit>
        </Questionnaire.Actions>
    </Questionnaire.Root>
    <p role="status">Submitted: {answer || 'Nothing yet'}</p>
</div>
```
