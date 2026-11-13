import { Head } from '@inertiajs/react';
import { SubmitForm } from '@/components/migration/common';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
type Localization = {
    language: string;
    timezone: string;
    date_format: string;
    time_format: string;
    number_format: string;
    currency: string;
    currency_display: string;
};
export default function Localization({
    localization,
    timezones,
}: {
    localization: Localization;
    timezones: string[];
}) {
    const options: Record<string, string[]> = {
        language: ['en', 'id'],
        timezone: timezones,
        date_format: ['Y-m-d', 'd/m/Y', 'm/d/Y'],
        time_format: ['H:i', 'h:i A'],
        number_format: ['1,234.56', '1.234,56', '1 234,56'],
    };
    return (
        <div className="space-y-4">
            <Head title="Localization settings" />
            <h1 className="text-xl font-semibold">Localization</h1>
            <p>
                Choose how dates, times and numbers appear. Stored timestamps
                use UTC.
            </p>
            <SubmitForm
                action="/settings/localization"
                method="patch"
                label="Save localization"
            >
                {Object.entries(options).map(([name, values]) => (
                    <div className="grid gap-1" key={name}>
                        <label htmlFor={name}>
                            {name.replaceAll('_', ' ')}
                        </label>
                        <Select
                            name={name}
                            defaultValue={
                                localization[name as keyof Localization]
                            }
                        >
                            <SelectTrigger id={name} className="w-full">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                {values.map((value) => (
                                    <SelectItem key={value} value={value}>
                                        {value}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    </div>
                ))}
                <label className="grid gap-1">
                    Currency code
                    <input
                        className="rounded-md border bg-background p-2"
                        name="currency"
                        defaultValue={localization.currency}
                        pattern="[A-Z]{3}"
                        maxLength={3}
                        required
                    />
                </label>
                <div className="grid gap-1">
                    <label htmlFor="currency_display">Currency display</label>
                    <Select
                        name="currency_display"
                        defaultValue={localization.currency_display}
                    >
                        <SelectTrigger id="currency_display" className="w-full">
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="symbol">Symbol</SelectItem>
                            <SelectItem value="code">Code</SelectItem>
                        </SelectContent>
                    </Select>
                </div>
            </SubmitForm>
        </div>
    );
}
