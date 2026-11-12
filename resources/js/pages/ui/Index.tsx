import { Link } from '@inertiajs/react';
import { useState } from 'react';
import { Screen } from '@/components/migration/common';
type Entry = {
    slug: string;
    name: string;
    description: string;
    category: string;
};
export default function Catalog({ components }: { components: Entry[] }) {
    const [search, setSearch] = useState('');
    return (
        <Screen title="UI components">
            <label className="grid gap-1">
                Search
                <input
                    className="rounded border p-2"
                    value={search}
                    onChange={(event) => setSearch(event.target.value)}
                />
            </label>
            <div className="grid gap-3 md:grid-cols-2">
                {components
                    .filter((component) =>
                        `${component.name} ${component.category}`
                            .toLowerCase()
                            .includes(search.toLowerCase()),
                    )
                    .map((component) => (
                        <Link
                            key={component.slug}
                            href={`/admin/ui/${component.slug}`}
                            className="rounded-lg border p-4"
                        >
                            <strong>{component.name}</strong>
                            <p>{component.description}</p>
                            <small>{component.category}</small>
                        </Link>
                    ))}
            </div>
        </Screen>
    );
}
