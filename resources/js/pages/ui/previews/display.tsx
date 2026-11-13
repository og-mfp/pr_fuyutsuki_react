import { useState } from 'react';
import { Bar, BarChart, CartesianGrid, Line, LineChart, XAxis } from 'recharts';
import { toast } from 'sonner';
import {
    RiCheckLine,
    RiFileTextLine,
    RiFolderLine,
    RiHeartLine,
    RiSearchLine,
    RiSettings3Line,
    RiUserLine,
} from '@remixicon/react';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import {
    Attachment,
    AttachmentAction,
    AttachmentActions,
    AttachmentContent,
    AttachmentDescription,
    AttachmentMedia,
    AttachmentTitle,
} from '@/components/ui/attachment';
import {
    Avatar,
    AvatarBadge,
    AvatarFallback,
    AvatarGroup,
    AvatarGroupCount,
} from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import { Bubble, BubbleContent, BubbleGroup } from '@/components/ui/bubble';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardFooter,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import {
    ChartContainer,
    ChartTooltip,
    ChartTooltipContent,
} from '@/components/ui/chart';
import {
    Empty,
    EmptyContent,
    EmptyDescription,
    EmptyHeader,
    EmptyMedia,
    EmptyTitle,
} from '@/components/ui/empty';
import { Input } from '@/components/ui/input';
import {
    Item,
    ItemContent,
    ItemDescription,
    ItemMedia,
    ItemTitle,
} from '@/components/ui/item';
import { Kbd, KbdGroup } from '@/components/ui/kbd';
import { Marker, MarkerContent } from '@/components/ui/marker';
import {
    Message,
    MessageAvatar,
    MessageContent,
    MessageHeader,
    MessageFooter,
} from '@/components/ui/message';
import {
    MessageScroller,
    MessageScrollerButton,
    MessageScrollerContent,
    MessageScrollerItem,
    MessageScrollerProvider,
    MessageScrollerViewport,
} from '@/components/ui/message-scroller';
import { Progress } from '@/components/ui/progress';
import { Skeleton } from '@/components/ui/skeleton';

import { Spinner } from '@/components/ui/spinner';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';

function AttachmentPreview() {
    const [visible, setVisible] = useState(true);
    return (
        <div className="w-full max-w-md space-y-4">
            <p className="text-sm font-medium">Sizes and upload states</p>
            {(
                [
                    ['default', 'done'],
                    ['sm', 'uploading'],
                    ['xs', 'processing'],
                    ['default', 'error'],
                    ['sm', 'idle'],
                ] as const
            ).map(([size, state]) => (
                <div key={state} className="space-y-1">
                    <p className="text-xs text-muted-foreground">
                        {size} · {state}
                    </p>
                    <Attachment size={size} state={state}>
                        <AttachmentMedia>
                            {state === 'uploading' || state === 'processing' ? (
                                <Spinner />
                            ) : (
                                <RiFileTextLine />
                            )}
                        </AttachmentMedia>
                        <AttachmentContent>
                            <AttachmentTitle>project-brief.pdf</AttachmentTitle>
                            <AttachmentDescription>
                                {state === 'error'
                                    ? 'Upload failed'
                                    : 'PDF · 240 KB'}
                            </AttachmentDescription>
                        </AttachmentContent>
                    </Attachment>
                </div>
            ))}
            <p className="text-sm font-medium">Vertical · removable</p>
            {visible ? (
                <Attachment orientation="vertical">
                    <AttachmentMedia>
                        <RiFileTextLine />
                    </AttachmentMedia>
                    <AttachmentContent>
                        <AttachmentTitle>brief.pdf</AttachmentTitle>
                    </AttachmentContent>
                    <AttachmentActions>
                        <AttachmentAction
                            aria-label="Remove attachment"
                            onClick={() => setVisible(false)}
                        >
                            ×
                        </AttachmentAction>
                    </AttachmentActions>
                </Attachment>
            ) : (
                <Button variant="outline" onClick={() => setVisible(true)}>
                    Restore attachment
                </Button>
            )}
        </div>
    );
}

function AvatarPreview() {
    return (
        <div className="space-y-4">
            <p className="text-sm font-medium">Sizes · fallback and status</p>
            <div className="flex items-end gap-4">
                {(['sm', 'default', 'lg'] as const).map((size) => (
                    <div key={size} className="space-y-1">
                        <Avatar size={size}>
                            <AvatarFallback>AL</AvatarFallback>
                            <AvatarBadge />
                        </Avatar>
                        <p className="text-xs text-muted-foreground">{size}</p>
                    </div>
                ))}
            </div>
            <p className="text-sm font-medium">Group</p>
            <AvatarGroup>
                <Avatar>
                    <AvatarFallback>AL</AvatarFallback>
                </Avatar>
                <Avatar>
                    <AvatarFallback>MK</AvatarFallback>
                </Avatar>
                <AvatarGroupCount>+3</AvatarGroupCount>
            </AvatarGroup>
        </div>
    );
}

function BadgePreview() {
    return (
        <div className="flex flex-wrap gap-3">
            {(
                [
                    'default',
                    'secondary',
                    'destructive',
                    'outline',
                    'ghost',
                    'link',
                ] as const
            ).map((variant) => (
                <Badge key={variant} variant={variant}>
                    {variant}
                </Badge>
            ))}
        </div>
    );
}

function BubblePreview() {
    return (
        <BubbleGroup className="w-80">
            {(
                [
                    'default',
                    'secondary',
                    'muted',
                    'tinted',
                    'outline',
                    'ghost',
                    'destructive',
                ] as const
            ).map((variant, index) => (
                <Bubble
                    key={variant}
                    variant={variant}
                    align={index % 2 ? 'end' : 'start'}
                >
                    <BubbleContent>
                        {variant} · {index % 2 ? 'end' : 'start'}
                    </BubbleContent>
                </Bubble>
            ))}
        </BubbleGroup>
    );
}

function CardPreview() {
    return (
        <div className="flex flex-wrap gap-4">
            {(['default', 'sm'] as const).map((size) => (
                <div key={size} className="space-y-2">
                    <p className="text-sm font-medium">{size} size</p>
                    <Card size={size} className="w-72">
                        <CardHeader>
                            <CardTitle>Project overview</CardTitle>
                            <CardDescription>
                                Your team's progress this week
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <p>12 of 16 tasks completed</p>
                            <Progress value={75} className="mt-3" />
                        </CardContent>
                        <CardFooter>
                            <Button size="sm" variant="outline">
                                View project
                            </Button>
                        </CardFooter>
                    </Card>
                </div>
            ))}
        </div>
    );
}

function ChartPreview() {
    const data = [
        { month: 'Jan', visitors: 120 },
        { month: 'Feb', visitors: 180 },
        { month: 'Mar', visitors: 150 },
        { month: 'Apr', visitors: 220 },
    ];
    const config = { visitors: { label: 'Visitors', color: 'var(--primary)' } };
    return (
        <div className="w-full max-w-md space-y-4">
            <p className="text-sm font-medium">Bar chart · monthly visitors</p>
            <ChartContainer className="h-48 w-full" config={config}>
                <BarChart data={data}>
                    <CartesianGrid vertical={false} />
                    <XAxis dataKey="month" tickLine={false} axisLine={false} />
                    <ChartTooltip content={<ChartTooltipContent />} />
                    <Bar
                        dataKey="visitors"
                        fill="var(--color-visitors)"
                        radius={4}
                    />
                </BarChart>
            </ChartContainer>
            <p className="text-sm font-medium">Line chart · monthly visitors</p>
            <ChartContainer className="h-48 w-full" config={config}>
                <LineChart data={data}>
                    <CartesianGrid vertical={false} />
                    <XAxis dataKey="month" tickLine={false} axisLine={false} />
                    <ChartTooltip content={<ChartTooltipContent />} />
                    <Line
                        dataKey="visitors"
                        stroke="var(--color-visitors)"
                        strokeWidth={2}
                    />
                </LineChart>
            </ChartContainer>
        </div>
    );
}

const rows = [
    { name: 'Design review', status: 'Complete' },
    { name: 'Documentation', status: 'In progress' },
    { name: 'Release', status: 'Planned' },
];
function TablePreview() {
    return (
        <Table className="max-w-md" aria-label="Task status table">
            <TableHeader>
                <TableRow>
                    <TableHead>Task</TableHead>
                    <TableHead>Status</TableHead>
                </TableRow>
            </TableHeader>
            <TableBody>
                {rows.map((row) => (
                    <TableRow key={row.name}>
                        <TableCell>{row.name}</TableCell>
                        <TableCell>{row.status}</TableCell>
                    </TableRow>
                ))}
            </TableBody>
        </Table>
    );
}
function DataTablePreview() {
    const [query, setQuery] = useState('');
    const [ascending, setAscending] = useState(true);
    const filtered = rows
        .filter((row) => row.name.toLowerCase().includes(query.toLowerCase()))
        .sort((a, b) =>
            ascending
                ? a.name.localeCompare(b.name)
                : b.name.localeCompare(a.name),
        );
    return (
        <div className="w-full max-w-md space-y-3">
            <p className="text-sm font-medium">
                Filterable and sortable task table
            </p>
            <Input
                aria-label="Filter tasks"
                placeholder="Filter tasks..."
                value={query}
                onChange={(event) => setQuery(event.target.value)}
            />
            <Table>
                <TableHeader>
                    <TableRow>
                        <TableHead>
                            <Button
                                variant="ghost"
                                onClick={() => setAscending(!ascending)}
                            >
                                Task {ascending ? '↑' : '↓'}
                            </Button>
                        </TableHead>
                        <TableHead>Status</TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    {filtered.length ? (
                        filtered.map((row) => (
                            <TableRow key={row.name}>
                                <TableCell>{row.name}</TableCell>
                                <TableCell>{row.status}</TableCell>
                            </TableRow>
                        ))
                    ) : (
                        <TableRow>
                            <TableCell colSpan={2}>No tasks found.</TableCell>
                        </TableRow>
                    )}
                </TableBody>
            </Table>
        </div>
    );
}
function ItemPreview() {
    return (
        <div className="w-80 space-y-3">
            {(['default', 'outline', 'muted'] as const).map(
                (variant, index) => (
                    <div key={variant} className="space-y-1">
                        <p className="text-xs text-muted-foreground">
                            {variant} ·{' '}
                            {(['default', 'sm', 'xs'] as const)[index]}
                        </p>
                        <Item
                            variant={variant}
                            size={(['default', 'sm', 'xs'] as const)[index]}
                        >
                            <ItemMedia variant="icon">
                                <RiFolderLine />
                            </ItemMedia>
                            <ItemContent>
                                <ItemTitle>Project files</ItemTitle>
                                <ItemDescription>
                                    Documents shared with your team
                                </ItemDescription>
                            </ItemContent>
                        </Item>
                    </div>
                ),
            )}
        </div>
    );
}

function KbdPreview() {
    return (
        <div className="space-y-3">
            <p className="flex items-center gap-2">
                Single key <Kbd>Esc</Kbd>
            </p>
            <p className="flex items-center gap-2">
                Shortcut{' '}
                <KbdGroup>
                    <Kbd>⌘</Kbd>
                    <Kbd>K</Kbd>
                </KbdGroup>
            </p>
        </div>
    );
}

function MarkerPreview() {
    return (
        <div className="w-80 space-y-4">
            {(['default', 'separator', 'border'] as const).map((variant) => (
                <div key={variant} className="space-y-1">
                    <p className="text-xs text-muted-foreground">{variant}</p>
                    <Marker variant={variant}>
                        <MarkerContent>Today</MarkerContent>
                    </Marker>
                </div>
            ))}
        </div>
    );
}

function MessagePreview() {
    return (
        <div className="w-80 space-y-4">
            {(['start', 'end'] as const).map((align) => (
                <div key={align} className="space-y-2">
                    <p className="text-xs text-muted-foreground">
                        {align === 'start'
                            ? 'Incoming · start'
                            : 'Outgoing · end'}
                    </p>
                    <Message align={align}>
                        <MessageAvatar>
                            <Avatar>
                                <AvatarFallback>
                                    {align === 'start' ? 'AL' : 'ME'}
                                </AvatarFallback>
                            </Avatar>
                        </MessageAvatar>
                        <MessageContent>
                            <MessageHeader>
                                {align === 'start' ? 'Avery' : 'You'} · 10:30 AM
                            </MessageHeader>
                            <Bubble
                                variant={
                                    align === 'start' ? 'secondary' : 'default'
                                }
                            >
                                <BubbleContent>
                                    {align === 'start'
                                        ? 'The designs are ready.'
                                        : 'Thanks for sharing!'}
                                </BubbleContent>
                            </Bubble>
                            <MessageFooter>
                                {align === 'start' ? 'Received' : 'Delivered'}
                            </MessageFooter>
                        </MessageContent>
                    </Message>
                </div>
            ))}
        </div>
    );
}

function MessageScrollerPreview() {
    return (
        <MessageScrollerProvider>
            <p className="text-sm font-medium">
                Scrollable conversation · jump to first or latest
            </p>
            <MessageScroller className="h-56 w-80 rounded-md border">
                <MessageScrollerViewport>
                    <MessageScrollerContent className="p-4">
                        {Array.from({ length: 8 }, (_, i) => (
                            <MessageScrollerItem key={i}>
                                <Bubble variant="secondary">
                                    <BubbleContent>
                                        Conversation update {i + 1}
                                    </BubbleContent>
                                </Bubble>
                            </MessageScrollerItem>
                        ))}
                    </MessageScrollerContent>
                </MessageScrollerViewport>
                <MessageScrollerButton
                    direction="start"
                    aria-label="Jump to first"
                />
                <MessageScrollerButton aria-label="Jump to latest" />
            </MessageScroller>
        </MessageScrollerProvider>
    );
}
function TypographyPreview() {
    return (
        <div className="max-w-md space-y-3">
            <p className="text-sm font-medium">
                Heading, paragraph and blockquote
            </p>
            <h2 className="text-2xl font-semibold tracking-tight">
                A thoughtful heading
            </h2>
            <p className="leading-7 text-muted-foreground">
                Good typography makes content easier to scan and understand.
            </p>
            <blockquote className="border-l-2 pl-4 italic">
                Details make the difference.
            </blockquote>
            <p className="text-sm font-medium">List and inline code</p>
            <ul className="list-disc space-y-1 pl-6">
                <li>Clear hierarchy</li>
                <li>Readable content</li>
            </ul>
            <code className="rounded bg-muted px-1 py-0.5 text-sm">
                npm run dev
            </code>
        </div>
    );
}
function AlertPreview() {
    return (
        <div className="w-full max-w-md space-y-4">
            {(['default', 'destructive'] as const).map((variant) => (
                <div key={variant} className="space-y-1">
                    <p className="text-xs text-muted-foreground">{variant}</p>
                    <Alert variant={variant}>
                        <RiCheckLine />
                        <AlertTitle>
                            {variant === 'default'
                                ? 'Changes saved'
                                : 'Unable to save'}
                        </AlertTitle>
                        <AlertDescription>
                            {variant === 'default'
                                ? 'Your updates are available to the team.'
                                : 'Please check your connection and try again.'}
                        </AlertDescription>
                    </Alert>
                </div>
            ))}
        </div>
    );
}

function EmptyPreview() {
    return (
        <div className="w-full max-w-md space-y-4">
            <p className="text-sm font-medium">Icon and action</p>
            <Empty className="border">
                <EmptyHeader>
                    <EmptyMedia variant="icon">
                        <RiFolderLine />
                    </EmptyMedia>
                    <EmptyTitle>No projects yet</EmptyTitle>
                    <EmptyDescription>
                        Create a project to get started with your team.
                    </EmptyDescription>
                </EmptyHeader>
                <EmptyContent>
                    <Button size="sm">Create project</Button>
                </EmptyContent>
            </Empty>
            <p className="text-sm font-medium">Search results</p>
            <Empty className="border">
                <EmptyHeader>
                    <EmptyMedia>
                        <RiSearchLine />
                    </EmptyMedia>
                    <EmptyTitle>No results found</EmptyTitle>
                    <EmptyDescription>
                        Try a different search term.
                    </EmptyDescription>
                </EmptyHeader>
            </Empty>
        </div>
    );
}

function ProgressPreview() {
    const [value, setValue] = useState(40);
    return (
        <div className="w-80 space-y-3">
            <div className="flex justify-between text-sm">
                <span>Interactive upload progress</span>
                <span>{value}%</span>
            </div>
            <Progress value={value} />
            <Button
                variant="outline"
                onClick={() =>
                    setValue(value >= 100 ? 0 : Math.min(value + 20, 100))
                }
            >
                {value >= 100 ? 'Reset' : 'Advance'}
            </Button>
        </div>
    );
}
function SkeletonPreview() {
    return (
        <div className="w-80 space-y-4">
            <p className="text-sm font-medium">Profile loading</p>
            <div className="flex items-center gap-4">
                <Skeleton className="size-12 rounded-full" />
                <div className="flex-1 space-y-2">
                    <Skeleton className="h-4 w-3/4" />
                    <Skeleton className="h-4 w-full" />
                </div>
            </div>
            <p className="text-sm font-medium">Card loading</p>
            <Skeleton className="h-28 w-full rounded-lg" />
            <Skeleton className="h-4 w-2/3" />
        </div>
    );
}

function SonnerPreview() {
    return (
        <div className="flex flex-wrap gap-2">
            <Button
                variant="outline"
                onClick={() =>
                    toast('Event created', {
                        description: 'Your event is scheduled.',
                    })
                }
            >
                Default
            </Button>
            <Button
                variant="outline"
                onClick={() => toast.success('Changes saved')}
            >
                Success
            </Button>
            <Button
                variant="outline"
                onClick={() => toast.error('Could not save changes')}
            >
                Error
            </Button>
            <Button
                variant="outline"
                onClick={() => toast.warning('Storage is almost full')}
            >
                Warning
            </Button>
            <Button
                variant="outline"
                onClick={() => toast.info('A new update is available')}
            >
                Info
            </Button>
            <Button
                variant="outline"
                onClick={() =>
                    toast('Item archived', {
                        action: {
                            label: 'Undo',
                            onClick: () => toast.success('Item restored'),
                        },
                    })
                }
            >
                With action
            </Button>
        </div>
    );
}

function SpinnerPreview() {
    return (
        <div className="space-y-4">
            <div className="flex items-center gap-3">
                <span className="text-sm">Default</span>
                <Spinner />
                <span className="text-sm">Large</span>
                <Spinner className="size-6" />
            </div>
            <div className="flex items-center gap-3">
                <span className="text-sm">Loading button</span>
                <Button disabled>
                    <Spinner /> Please wait
                </Button>
            </div>
        </div>
    );
}

const icons = [
    { name: 'Search', Icon: RiSearchLine },
    { name: 'User', Icon: RiUserLine },
    { name: 'Settings', Icon: RiSettings3Line },
    { name: 'Folder', Icon: RiFolderLine },
    { name: 'Heart', Icon: RiHeartLine },
    { name: 'Check', Icon: RiCheckLine },
];
function IconLibraryPreview() {
    const [query, setQuery] = useState('');
    const filtered = icons.filter(({ name }) =>
        name.toLowerCase().includes(query.toLowerCase()),
    );
    return (
        <div className="w-80 space-y-4">
            <p className="text-sm font-medium">Search icons</p>
            <Input
                aria-label="Search icons"
                placeholder="Search icons..."
                value={query}
                onChange={(event) => setQuery(event.target.value)}
            />
            <div className="grid grid-cols-3 gap-3">
                {filtered.map(({ name, Icon }) => (
                    <div
                        key={name}
                        className="flex flex-col items-center gap-2 rounded-md border p-3 text-xs"
                    >
                        <Icon />
                        <span>{name}</span>
                    </div>
                ))}
            </div>
            {filtered.length === 0 && (
                <p className="text-sm text-muted-foreground">No icons found.</p>
            )}
        </div>
    );
}

export const displayPreviews = {
    attachment: AttachmentPreview,
    avatar: AvatarPreview,
    badge: BadgePreview,
    bubble: BubblePreview,
    card: CardPreview,
    chart: ChartPreview,
    'data-table': DataTablePreview,
    item: ItemPreview,
    kbd: KbdPreview,
    marker: MarkerPreview,
    message: MessagePreview,
    'message-scroller': MessageScrollerPreview,
    table: TablePreview,
    typography: TypographyPreview,
    alert: AlertPreview,
    empty: EmptyPreview,
    progress: ProgressPreview,
    skeleton: SkeletonPreview,
    sonner: SonnerPreview,
    spinner: SpinnerPreview,
    'icon-library': IconLibraryPreview,
};
