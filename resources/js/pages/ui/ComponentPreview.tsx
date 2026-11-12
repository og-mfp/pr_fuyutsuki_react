import { Link } from '@inertiajs/react';
import { Screen } from '@/components/migration/common';
export default function ComponentPreview({
    component,
}: {
    component: { name: string; description: string; category: string };
}) {
    return (
        <Screen title={component.name}>
            <Link className="underline" href="/admin/ui">
                Back to components
            </Link>
            <p>{component.description}</p>
            <p>Category: {component.category}</p>
            <p>
                The interactive component demonstration has not yet been
                migrated.
            </p>
        </Screen>
    );
}
