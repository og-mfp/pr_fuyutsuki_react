import { Link } from '@inertiajs/react';
import { Screen } from '@/components/migration/common';
import { additionalPreviews } from './previews/additional';
import { displayPreviews } from './previews/display';
import { formPreviews } from './previews/forms';
import { navigationLayoutPreviews } from './previews/navigation-layout';

const previews: Record<string, React.ComponentType> = {
    ...additionalPreviews,
    ...displayPreviews,
    ...formPreviews,
    ...navigationLayoutPreviews,
};

export default function ComponentPreview({
    component,
}: {
    component: {
        slug: string;
        name: string;
        description: string;
        category: string;
    };
}) {
    const Preview = previews[component.slug];

    return (
        <Screen title={component.name}>
            <Link className="underline" href="/admin/ui">
                Back to components
            </Link>
            <p>{component.description}</p>
            <p>Category: {component.category}</p>
            {Preview ? (
                <section
                    aria-label={`${component.name} preview`}
                    className="min-w-0 rounded-lg border bg-card p-6"
                >
                    <Preview />
                </section>
            ) : (
                <p>
                    There is no React implementation available to preview yet.
                </p>
            )}
        </Screen>
    );
}
