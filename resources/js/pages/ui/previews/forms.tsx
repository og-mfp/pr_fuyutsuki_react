import { useState, type ComponentType, type ReactNode } from 'react';
import { format } from 'date-fns';
import type { DateRange } from 'react-day-picker';
import { Button } from '@/components/ui/button';
import { ButtonGroup } from '@/components/ui/button-group';
import { Calendar } from '@/components/ui/calendar';
import { Checkbox } from '@/components/ui/checkbox';
import {
    Combobox,
    ComboboxContent,
    ComboboxEmpty,
    ComboboxInput,
    ComboboxItem,
    ComboboxList,
} from '@/components/ui/combobox';
import { Field, FieldDescription, FieldLabel } from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import {
    InputGroup,
    InputGroupAddon,
    InputGroupInput,
    InputGroupText,
} from '@/components/ui/input-group';
import {
    InputOTP,
    InputOTPGroup,
    InputOTPSlot,
} from '@/components/ui/input-otp';
import { Label } from '@/components/ui/label';
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/components/ui/popover';
import {
    Questionnaire,
    QuestionnaireActions,
    QuestionnaireChoice,
    QuestionnaireChoices,
    QuestionnaireInput,
    QuestionnaireItem,
    QuestionnaireNext,
    QuestionnairePrevious,
    QuestionnaireProgress,
    QuestionnaireSubmit,
    QuestionnaireTitle,
} from '@/components/ui/questionnaire';
import { RadioGroup, RadioGroupItem } from '@/components/ui/radio-group';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Slider } from '@/components/ui/slider';
import { Switch } from '@/components/ui/switch';
import { Textarea } from '@/components/ui/textarea';
import { Toggle } from '@/components/ui/toggle';
import { ToggleGroup, ToggleGroupItem } from '@/components/ui/toggle-group';

function ButtonPreview() {
    const [count, setCount] = useState(0);
    return (
        <div className="flex items-center gap-3">
            <Button onClick={() => setCount(count + 1)}>Click me</Button>
            <span aria-live="polite">Clicked {count} times</span>
        </div>
    );
}

function ButtonGroupPreview() {
    const [count, setCount] = useState(0);
    return (
        <div className="flex items-center gap-3">
            <ButtonGroup>
                <Button
                    variant="outline"
                    onClick={() => setCount(count - 1)}
                    aria-label="Decrease"
                >
                    −
                </Button>
                <Button
                    variant="outline"
                    onClick={() => setCount(count + 1)}
                    aria-label="Increase"
                >
                    +
                </Button>
            </ButtonGroup>
            <span aria-live="polite">{count}</span>
        </div>
    );
}

function CalendarPreview() {
    const [date, setDate] = useState<Date | undefined>();
    return (
        <div className="flex flex-col items-start gap-2">
            <Calendar mode="single" selected={date} onSelect={setDate} />
            <p aria-live="polite">
                {date ? format(date, 'PPP') : 'Choose a date'}
            </p>
        </div>
    );
}

function RangeCalendarPreview() {
    const [range, setRange] = useState<DateRange | undefined>();
    return (
        <div className="flex flex-col items-start gap-2">
            <Calendar mode="range" selected={range} onSelect={setRange} />
            <p aria-live="polite">
                {range?.from
                    ? `${format(range.from, 'PPP')}${range.to ? ` – ${format(range.to, 'PPP')}` : ''}`
                    : 'Choose a date range'}
            </p>
        </div>
    );
}

function DatePickerPreview() {
    const [date, setDate] = useState<Date | undefined>();
    const [open, setOpen] = useState(false);
    return (
        <Popover open={open} onOpenChange={setOpen}>
            <PopoverTrigger asChild>
                <Button variant="outline">
                    {date ? format(date, 'PPP') : 'Pick a date'}
                </Button>
            </PopoverTrigger>
            <PopoverContent className="w-auto p-0" align="start">
                <Calendar
                    mode="single"
                    selected={date}
                    onSelect={(selected) => {
                        setDate(selected);
                        if (selected) setOpen(false);
                    }}
                />
            </PopoverContent>
        </Popover>
    );
}

function CheckboxPreview() {
    const [checked, setChecked] = useState(false);
    return (
        <div className="flex items-center gap-2">
            <Checkbox
                id="preview-checkbox"
                checked={checked}
                onCheckedChange={(value) => setChecked(value === true)}
            />
            <Label htmlFor="preview-checkbox">Accept terms</Label>
            <span aria-live="polite">
                {checked ? 'Accepted' : 'Not accepted'}
            </span>
        </div>
    );
}

function ComboboxPreview() {
    const items = ['Apple', 'Banana', 'Cherry', 'Orange'];
    return (
        <div className="w-64">
            <Combobox items={items}>
                <ComboboxInput
                    aria-label="Fruit"
                    placeholder="Choose a fruit"
                    showClear
                />
                <ComboboxContent>
                    <ComboboxEmpty>No fruit found.</ComboboxEmpty>
                    <ComboboxList>
                        {(item: string) => (
                            <ComboboxItem key={item} value={item}>
                                {item}
                            </ComboboxItem>
                        )}
                    </ComboboxList>
                </ComboboxContent>
            </Combobox>
        </div>
    );
}

function FieldPreview() {
    return (
        <Field className="w-64">
            <FieldLabel htmlFor="preview-field">Email address</FieldLabel>
            <Input
                id="preview-field"
                type="email"
                placeholder="you@example.com"
            />
            <FieldDescription>
                We'll only use this to contact you.
            </FieldDescription>
        </Field>
    );
}

function InputPreview() {
    return (
        <div className="w-64">
            <Label htmlFor="preview-input">Email</Label>
            <Input
                id="preview-input"
                type="email"
                placeholder="you@example.com"
            />
        </div>
    );
}

function InputGroupPreview() {
    return (
        <div className="w-64">
            <InputGroup>
                <InputGroupAddon>
                    <InputGroupText>https://</InputGroupText>
                </InputGroupAddon>
                <InputGroupInput
                    aria-label="Website"
                    placeholder="example.com"
                />
            </InputGroup>
        </div>
    );
}

function InputOtpPreview() {
    const [value, setValue] = useState('');
    return (
        <div className="flex flex-col items-start gap-2">
            <Label htmlFor="preview-otp">Verification code</Label>
            <InputOTP
                id="preview-otp"
                maxLength={6}
                value={value}
                onChange={setValue}
            >
                <InputOTPGroup>
                    {Array.from({ length: 6 }, (_, index) => (
                        <InputOTPSlot key={index} index={index} />
                    ))}
                </InputOTPGroup>
            </InputOTP>
            <p aria-live="polite">
                {value.length === 6 ? 'Code complete' : 'Enter six digits'}
            </p>
        </div>
    );
}

function LabelPreview() {
    return (
        <div className="flex w-64 flex-col gap-2">
            <Label htmlFor="preview-label">Your name</Label>
            <Input id="preview-label" placeholder="Jane Doe" />
        </div>
    );
}

function QuestionnairePreview() {
    const [submitted, setSubmitted] = useState(false);
    if (submitted)
        return (
            <div className="flex flex-col items-start gap-2">
                <p>Thanks for your answers!</p>
                <Button variant="outline" onClick={() => setSubmitted(false)}>
                    Start again
                </Button>
            </div>
        );
    return (
        <Questionnaire
            className="max-w-sm"
            items={[{ name: 'purpose', required: true }, { name: 'name' }]}
            onSubmit={(event) => {
                event.preventDefault();
                setSubmitted(true);
            }}
        >
            <QuestionnaireProgress />
            <QuestionnaireItem name="purpose" required>
                <QuestionnaireTitle>What brings you here?</QuestionnaireTitle>
                <QuestionnaireChoices>
                    <QuestionnaireChoice value="learning">
                        Learning
                    </QuestionnaireChoice>
                    <QuestionnaireChoice value="building">
                        Building a project
                    </QuestionnaireChoice>
                </QuestionnaireChoices>
            </QuestionnaireItem>
            <QuestionnaireItem name="name">
                <QuestionnaireTitle>
                    What should we call you?
                </QuestionnaireTitle>
                <QuestionnaireInput placeholder="Your name" />
            </QuestionnaireItem>
            <QuestionnaireActions>
                <QuestionnairePrevious />
                <QuestionnaireNext />
                <QuestionnaireSubmit />
            </QuestionnaireActions>
        </Questionnaire>
    );
}

function RadioGroupPreview() {
    return (
        <RadioGroup defaultValue="comfortable" aria-label="Display density">
            <div className="flex items-center gap-2">
                <RadioGroupItem
                    value="comfortable"
                    id="preview-radio-comfortable"
                />
                <Label htmlFor="preview-radio-comfortable">Comfortable</Label>
            </div>
            <div className="flex items-center gap-2">
                <RadioGroupItem value="compact" id="preview-radio-compact" />
                <Label htmlFor="preview-radio-compact">Compact</Label>
            </div>
        </RadioGroup>
    );
}

function SelectPreview() {
    return (
        <Select>
            <SelectTrigger className="w-64" aria-label="Select a fruit">
                <SelectValue placeholder="Select a fruit" />
            </SelectTrigger>
            <SelectContent>
                <SelectItem value="apple">Apple</SelectItem>
                <SelectItem value="banana">Banana</SelectItem>
                <SelectItem value="cherry">Cherry</SelectItem>
            </SelectContent>
        </Select>
    );
}

function SliderPreview() {
    const [value, setValue] = useState([40]);
    return (
        <div className="flex w-64 flex-col gap-3">
            <Label htmlFor="preview-slider">Volume: {value[0]}%</Label>
            <Slider
                id="preview-slider"
                value={value}
                onValueChange={setValue}
                max={100}
                step={1}
            />
        </div>
    );
}

function SwitchPreview() {
    const [enabled, setEnabled] = useState(false);
    return (
        <div className="flex items-center gap-2">
            <Switch
                id="preview-switch"
                checked={enabled}
                onCheckedChange={setEnabled}
            />
            <Label htmlFor="preview-switch">
                Notifications {enabled ? 'on' : 'off'}
            </Label>
        </div>
    );
}

function TextareaPreview() {
    return (
        <div className="flex w-64 flex-col gap-2">
            <Label htmlFor="preview-textarea">Message</Label>
            <Textarea id="preview-textarea" placeholder="Write a message..." />
        </div>
    );
}

function TogglePreview() {
    return (
        <Toggle variant="outline" aria-label="Toggle bold">
            <strong>B</strong>
        </Toggle>
    );
}

function ToggleGroupPreview() {
    return (
        <ToggleGroup
            type="multiple"
            variant="outline"
            aria-label="Text formatting"
        >
            <ToggleGroupItem value="bold" aria-label="Bold">
                <strong>B</strong>
            </ToggleGroupItem>
            <ToggleGroupItem value="italic" aria-label="Italic">
                <em>I</em>
            </ToggleGroupItem>
            <ToggleGroupItem value="underline" aria-label="Underline">
                <span className="underline">U</span>
            </ToggleGroupItem>
        </ToggleGroup>
    );
}

function Example({ title, children }: { title: string; children: ReactNode }) {
    return (
        <section className="flex flex-col items-start gap-2">
            <p className="text-xs font-medium text-muted-foreground">{title}</p>
            {children}
        </section>
    );
}

function AdditionalFormExamples({ name }: { name: string }) {
    const [feedbackSubmitted, setFeedbackSubmitted] = useState(false);
    switch (name) {
        case 'button':
            return (
                <>
                    <Example title="Variants">
                        <div className="flex flex-wrap gap-2">
                            {(
                                [
                                    'default',
                                    'secondary',
                                    'outline',
                                    'ghost',
                                    'destructive',
                                    'link',
                                ] as const
                            ).map((variant) => (
                                <Button key={variant} variant={variant}>
                                    {variant}
                                </Button>
                            ))}
                        </div>
                    </Example>
                    <Example title="Sizes">
                        <div className="flex flex-wrap items-center gap-2">
                            {(['xs', 'sm', 'default', 'lg'] as const).map(
                                (size) => (
                                    <Button key={size} size={size}>
                                        {size}
                                    </Button>
                                ),
                            )}
                            {(
                                [
                                    'icon-xs',
                                    'icon-sm',
                                    'icon',
                                    'icon-lg',
                                ] as const
                            ).map((size) => (
                                <Button
                                    key={size}
                                    size={size}
                                    variant="outline"
                                    aria-label={size}
                                >
                                    +
                                </Button>
                            ))}
                        </div>
                    </Example>
                    <Example title="Disabled">
                        <Button disabled>Disabled</Button>
                    </Example>
                </>
            );
        case 'button-group':
            return (
                <Example title="Vertical">
                    <ButtonGroup orientation="vertical">
                        <Button variant="outline">Top</Button>
                        <Button variant="outline">Middle</Button>
                        <Button variant="outline">Bottom</Button>
                    </ButtonGroup>
                </Example>
            );
        case 'calendar':
            return (
                <Example title="Range selection">
                    <RangeCalendarPreview />
                </Example>
            );
        case 'range-calendar':
            return (
                <Example title="Single selection">
                    <CalendarPreview />
                </Example>
            );
        case 'date-picker':
            return (
                <Example title="Disabled">
                    <Button variant="outline" disabled>
                        Pick a date
                    </Button>
                </Example>
            );
        case 'checkbox':
            return (
                <Example title="States">
                    <div className="flex flex-col gap-3">
                        <div className="flex items-center gap-2">
                            <Checkbox id="checkbox-checked" defaultChecked />
                            <Label htmlFor="checkbox-checked">Checked</Label>
                        </div>
                        <div className="flex items-center gap-2">
                            <Checkbox
                                id="checkbox-indeterminate"
                                defaultChecked="indeterminate"
                            />
                            <Label htmlFor="checkbox-indeterminate">
                                Indeterminate
                            </Label>
                        </div>
                        <div className="flex items-center gap-2">
                            <Checkbox id="checkbox-disabled" disabled />
                            <Label htmlFor="checkbox-disabled">Disabled</Label>
                        </div>
                    </div>
                </Example>
            );
        case 'combobox':
            return (
                <Example title="Disabled">
                    <Combobox items={['Apple', 'Banana']} disabled>
                        <ComboboxInput
                            aria-label="Disabled fruit"
                            placeholder="Choose a fruit"
                        />
                    </Combobox>
                </Example>
            );
        case 'field':
            return (
                <>
                    <Example title="Horizontal">
                        <Field orientation="horizontal" className="w-64">
                            <Checkbox id="field-terms" />
                            <FieldLabel htmlFor="field-terms">
                                Accept terms
                            </FieldLabel>
                        </Field>
                    </Example>
                    <Example title="Invalid">
                        <Field data-invalid className="w-64">
                            <FieldLabel htmlFor="field-invalid">
                                Email
                            </FieldLabel>
                            <Input
                                id="field-invalid"
                                aria-invalid="true"
                                defaultValue="invalid"
                            />
                            <FieldDescription>
                                Please enter a valid email.
                            </FieldDescription>
                        </Field>
                    </Example>
                </>
            );
        case 'input':
            return (
                <>
                    <Example title="Password and file">
                        <div className="flex w-64 flex-col gap-2">
                            <Input
                                type="password"
                                aria-label="Password"
                                placeholder="Password"
                            />
                            <Input type="file" aria-label="File" />
                        </div>
                    </Example>
                    <Example title="Disabled and invalid">
                        <div className="flex w-64 flex-col gap-2">
                            <Input
                                disabled
                                aria-label="Disabled input"
                                placeholder="Disabled"
                            />
                            <Input
                                aria-invalid="true"
                                aria-label="Invalid input"
                                defaultValue="Invalid value"
                            />
                        </div>
                    </Example>
                </>
            );
        case 'input-group':
            return (
                <Example title="Suffix">
                    <InputGroup className="w-64">
                        <InputGroupInput
                            aria-label="Amount"
                            placeholder="0.00"
                        />
                        <InputGroupAddon align="inline-end">
                            <InputGroupText>USD</InputGroupText>
                        </InputGroupAddon>
                    </InputGroup>
                </Example>
            );
        case 'input-otp':
            return (
                <Example title="Disabled">
                    <InputOTP
                        maxLength={4}
                        disabled
                        aria-label="Disabled verification code"
                    >
                        <InputOTPGroup>
                            {Array.from({ length: 4 }, (_, index) => (
                                <InputOTPSlot key={index} index={index} />
                            ))}
                        </InputOTPGroup>
                    </InputOTP>
                </Example>
            );
        case 'label':
            return (
                <Example title="Disabled control">
                    <div className="flex w-64 flex-col gap-2">
                        <Label htmlFor="label-disabled">Disabled name</Label>
                        <Input
                            id="label-disabled"
                            disabled
                            placeholder="Unavailable"
                        />
                    </div>
                </Example>
            );
        case 'questionnaire':
            return (
                <Example title="Single required text answer">
                    {feedbackSubmitted ? (
                        <div className="flex flex-col items-start gap-2">
                            <p role="status">Feedback submitted.</p>
                            <Button
                                variant="outline"
                                onClick={() => setFeedbackSubmitted(false)}
                            >
                                Start again
                            </Button>
                        </div>
                    ) : (
                        <Questionnaire
                            className="max-w-sm"
                            items={[{ name: 'feedback', required: true }]}
                            onSubmit={(event) => {
                                event.preventDefault();
                                setFeedbackSubmitted(true);
                            }}
                        >
                            <QuestionnaireItem name="feedback" required>
                                <QuestionnaireTitle>
                                    Share your feedback
                                </QuestionnaireTitle>
                                <QuestionnaireInput placeholder="Your feedback" />
                            </QuestionnaireItem>
                            <QuestionnaireActions>
                                <QuestionnaireSubmit>
                                    Submit feedback
                                </QuestionnaireSubmit>
                            </QuestionnaireActions>
                        </Questionnaire>
                    )}
                </Example>
            );
        case 'radio-group':
            return (
                <Example title="Disabled option">
                    <RadioGroup
                        defaultValue="available"
                        aria-label="Availability"
                    >
                        <div className="flex items-center gap-2">
                            <RadioGroupItem
                                id="radio-available"
                                value="available"
                            />
                            <Label htmlFor="radio-available">Available</Label>
                        </div>
                        <div className="flex items-center gap-2">
                            <RadioGroupItem
                                id="radio-unavailable"
                                value="unavailable"
                                disabled
                            />
                            <Label htmlFor="radio-unavailable">
                                Unavailable
                            </Label>
                        </div>
                    </RadioGroup>
                </Example>
            );
        case 'select':
            return (
                <>
                    <Example title="Small">
                        <Select>
                            <SelectTrigger
                                size="sm"
                                className="w-64"
                                aria-label="Small select"
                            >
                                <SelectValue placeholder="Choose a size" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="small">Small</SelectItem>
                                <SelectItem value="large">Large</SelectItem>
                            </SelectContent>
                        </Select>
                    </Example>
                    <Example title="Disabled">
                        <Select disabled>
                            <SelectTrigger
                                className="w-64"
                                aria-label="Disabled select"
                            >
                                <SelectValue placeholder="Unavailable" />
                            </SelectTrigger>
                        </Select>
                    </Example>
                </>
            );
        case 'slider':
            return (
                <>
                    <Example title="Range">
                        <Slider
                            className="w-64"
                            defaultValue={[25, 75]}
                            aria-label="Price range"
                        />
                    </Example>
                    <Example title="Disabled">
                        <Slider
                            className="w-64"
                            defaultValue={[50]}
                            disabled
                            aria-label="Disabled slider"
                        />
                    </Example>
                </>
            );
        case 'switch':
            return (
                <Example title="Sizes and disabled">
                    <div className="flex flex-col gap-3">
                        <div className="flex items-center gap-2">
                            <Switch
                                id="switch-small"
                                size="sm"
                                defaultChecked
                            />
                            <Label htmlFor="switch-small">Small</Label>
                        </div>
                        <div className="flex items-center gap-2">
                            <Switch id="switch-disabled" disabled />
                            <Label htmlFor="switch-disabled">Disabled</Label>
                        </div>
                    </div>
                </Example>
            );
        case 'textarea':
            return (
                <Example title="Disabled and invalid">
                    <div className="flex w-64 flex-col gap-2">
                        <Textarea
                            aria-label="Disabled message"
                            disabled
                            placeholder="Disabled"
                        />
                        <Textarea
                            aria-label="Invalid message"
                            aria-invalid="true"
                            defaultValue="Invalid message"
                        />
                    </div>
                </Example>
            );
        case 'toggle':
            return (
                <>
                    <Example title="Variants">
                        <div className="flex gap-2">
                            <Toggle aria-label="Default toggle">Default</Toggle>
                            <Toggle
                                variant="outline"
                                aria-label="Outline toggle"
                            >
                                Outline
                            </Toggle>
                            <Toggle disabled aria-label="Disabled toggle">
                                Disabled
                            </Toggle>
                        </div>
                    </Example>
                    <Example title="Sizes">
                        <div className="flex items-center gap-2">
                            {(['sm', 'default', 'lg'] as const).map((size) => (
                                <Toggle
                                    key={size}
                                    size={size}
                                    variant="outline"
                                    aria-label={`${size} toggle`}
                                >
                                    {size}
                                </Toggle>
                            ))}
                        </div>
                    </Example>
                </>
            );
        case 'toggle-group':
            return (
                <>
                    <Example title="Single selection">
                        <ToggleGroup
                            type="single"
                            defaultValue="left"
                            aria-label="Alignment"
                        >
                            <ToggleGroupItem value="left">Left</ToggleGroupItem>
                            <ToggleGroupItem value="center">
                                Center
                            </ToggleGroupItem>
                            <ToggleGroupItem value="right" disabled>
                                Right
                            </ToggleGroupItem>
                        </ToggleGroup>
                    </Example>
                    <Example title="Small and vertical">
                        <ToggleGroup
                            type="single"
                            size="sm"
                            variant="outline"
                            orientation="vertical"
                            aria-label="Vertical alignment"
                        >
                            <ToggleGroupItem value="top">Top</ToggleGroupItem>
                            <ToggleGroupItem value="bottom">
                                Bottom
                            </ToggleGroupItem>
                        </ToggleGroup>
                    </Example>
                </>
            );
        default:
            return null;
    }
}

const baseFormPreviews: Record<string, ComponentType> = {
    button: ButtonPreview,
    'button-group': ButtonGroupPreview,
    calendar: CalendarPreview,
    checkbox: CheckboxPreview,
    combobox: ComboboxPreview,
    'date-picker': DatePickerPreview,
    field: FieldPreview,
    input: InputPreview,
    'input-group': InputGroupPreview,
    'input-otp': InputOtpPreview,
    label: LabelPreview,
    questionnaire: QuestionnairePreview,
    'radio-group': RadioGroupPreview,
    'range-calendar': RangeCalendarPreview,
    select: SelectPreview,
    slider: SliderPreview,
    switch: SwitchPreview,
    textarea: TextareaPreview,
    toggle: TogglePreview,
    'toggle-group': ToggleGroupPreview,
};

export const formPreviews: Record<string, ComponentType> = Object.fromEntries(
    Object.entries(baseFormPreviews).map(([name, Preview]) => [
        name,
        function FormPreview() {
            return (
                <div className="flex flex-col items-start gap-6">
                    <Example title="Interactive">
                        <Preview />
                    </Example>
                    <AdditionalFormExamples name={name} />
                </div>
            );
        },
    ]),
);
