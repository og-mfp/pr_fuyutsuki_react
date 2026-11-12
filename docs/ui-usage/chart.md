> **Historical Svelte source reference:** This is preserved from `pr_fuyutsuki`, not current React documentation. Examples, routes, dependencies, commands and conclusions below describe the source at the time of writing and have not been verified for this React application. Do not use them as React instructions.

# Chart

Beautiful charts. Built using LayerChart. Copy and paste into your apps.

Showcase: `/admin/ui/chart`. Source: ui/components/Chart.svelte (source-only path: `../../resources/js/pages/ui/components/Chart.svelte`).

[Official documentation](https://shadcn-svelte.com/docs/components/chart). Check the installed barrel for all exported parts.

```svelte
<script lang="ts">
    import { BarChart } from 'layerchart';
    import * as Chart from '@/components/ui/chart';
    import { Button } from '@/components/ui/button';
    let data = $state([
        { month: 'Jan', count: 20 },
        { month: 'Feb', count: 35 },
        { month: 'Mar', count: 25 },
    ]);
    const config = { count: { label: 'Requests', color: 'var(--chart-1)' } };
</script>

<div class="flex flex-col gap-4">
    <Chart.Container {config} class="h-64 w-full">
        <BarChart
            {data}
            x="month"
            y="count"
            series={[
                { key: 'count', label: 'Requests', color: 'var(--chart-1)' },
            ]}
        />
    </Chart.Container><Button
        variant="outline"
        onclick={() =>
            (data = data.map((item) => ({ ...item, count: item.count + 5 })))}
    >
        Add five requests per month
    </Button>
    <p role="status">
        {data.map((item) => item.month + ': ' + item.count).join(', ')}
    </p>
</div>
```
