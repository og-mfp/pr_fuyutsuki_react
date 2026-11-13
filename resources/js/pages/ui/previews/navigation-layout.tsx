import { useState, type ComponentType, type ReactNode } from 'react';
import {
    Accordion,
    AccordionContent,
    AccordionItem,
    AccordionTrigger,
} from '@/components/ui/accordion';
import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
    AlertDialogTrigger,
} from '@/components/ui/alert-dialog';
import { AspectRatio } from '@/components/ui/aspect-ratio';
import {
    Breadcrumb,
    BreadcrumbEllipsis,
    BreadcrumbItem,
    BreadcrumbLink,
    BreadcrumbList,
    BreadcrumbPage,
    BreadcrumbSeparator,
} from '@/components/ui/breadcrumb';
import { Button } from '@/components/ui/button';
import {
    Carousel,
    CarouselContent,
    CarouselItem,
    CarouselNext,
    CarouselPrevious,
} from '@/components/ui/carousel';
import {
    Collapsible,
    CollapsibleContent,
    CollapsibleTrigger,
} from '@/components/ui/collapsible';
import {
    Command,
    CommandEmpty,
    CommandGroup,
    CommandInput,
    CommandItem,
    CommandList,
} from '@/components/ui/command';
import {
    ContextMenu,
    ContextMenuContent,
    ContextMenuCheckboxItem,
    ContextMenuItem,
    ContextMenuTrigger,
} from '@/components/ui/context-menu';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import {
    Drawer,
    DrawerClose,
    DrawerContent,
    DrawerDescription,
    DrawerFooter,
    DrawerHeader,
    DrawerTitle,
    DrawerTrigger,
} from '@/components/ui/drawer';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuCheckboxItem,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import {
    HoverCard,
    HoverCardContent,
    HoverCardTrigger,
} from '@/components/ui/hover-card';
import {
    Menubar,
    MenubarContent,
    MenubarItem,
    MenubarMenu,
    MenubarTrigger,
} from '@/components/ui/menubar';
import {
    NavigationMenu,
    NavigationMenuContent,
    NavigationMenuItem,
    NavigationMenuLink,
    NavigationMenuList,
    NavigationMenuTrigger,
} from '@/components/ui/navigation-menu';
import {
    Pagination,
    PaginationContent,
    PaginationItem,
    PaginationLink,
    PaginationNext,
    PaginationPrevious,
} from '@/components/ui/pagination';
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/components/ui/popover';
import {
    ResizableHandle,
    ResizablePanel,
    ResizablePanelGroup,
} from '@/components/ui/resizable';
import { ScrollArea, ScrollBar } from '@/components/ui/scroll-area';
import { Separator } from '@/components/ui/separator';
import {
    Sheet,
    SheetContent,
    SheetDescription,
    SheetHeader,
    SheetTitle,
    SheetTrigger,
} from '@/components/ui/sheet';
import {
    Sidebar,
    SidebarContent,
    SidebarGroup,
    SidebarGroupContent,
    SidebarGroupLabel,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
    SidebarProvider,
} from '@/components/ui/sidebar';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import {
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
} from '@/components/ui/tooltip';

function AccordionPreview() {
    return (
        <Accordion type="single" collapsible className="w-80">
            <AccordionItem value="shipping">
                <AccordionTrigger>
                    How long does shipping take?
                </AccordionTrigger>
                <AccordionContent>
                    Orders usually arrive within three to five business days.
                </AccordionContent>
            </AccordionItem>
            <AccordionItem value="returns">
                <AccordionTrigger>Can I return an item?</AccordionTrigger>
                <AccordionContent>
                    Returns are accepted within 30 days.
                </AccordionContent>
            </AccordionItem>
        </Accordion>
    );
}
function AspectRatioPreview() {
    return (
        <div className="w-80">
            <AspectRatio
                ratio={16 / 9}
                className="flex items-center justify-center rounded-md bg-muted text-muted-foreground"
            >
                16:9 media area
            </AspectRatio>
        </div>
    );
}
function CarouselPreview() {
    return (
        <Carousel className="mx-12 w-56">
            <CarouselContent>
                {['Discover', 'Create', 'Share'].map((item, index) => (
                    <CarouselItem key={item}>
                        <div className="flex h-32 items-center justify-center rounded-lg border bg-muted text-lg">
                            {index + 1}. {item}
                        </div>
                    </CarouselItem>
                ))}
            </CarouselContent>
            <CarouselPrevious />
            <CarouselNext />
        </Carousel>
    );
}
function CollapsiblePreview() {
    return (
        <Collapsible className="w-72 space-y-2">
            <div className="flex items-center justify-between">
                <span>Recent activity</span>
                <CollapsibleTrigger asChild>
                    <Button variant="outline" size="sm">
                        Toggle
                    </Button>
                </CollapsibleTrigger>
            </div>
            <CollapsibleContent className="rounded-md border p-3 text-sm">
                Your project was updated today.
            </CollapsibleContent>
        </Collapsible>
    );
}
function ResizablePreview() {
    return (
        <ResizablePanelGroup
            orientation="horizontal"
            className="h-40 w-80 rounded-md border"
        >
            <ResizablePanel defaultSize={50}>
                <div className="flex h-full items-center justify-center">
                    Explorer
                </div>
            </ResizablePanel>
            <ResizableHandle withHandle />
            <ResizablePanel defaultSize={50}>
                <div className="flex h-full items-center justify-center">
                    Editor
                </div>
            </ResizablePanel>
        </ResizablePanelGroup>
    );
}
function ScrollAreaPreview() {
    return (
        <ScrollArea className="h-40 w-64 rounded-md border p-4">
            <h4 className="mb-2 font-medium">Project activity</h4>
            {Array.from({ length: 12 }, (_, i) => (
                <div key={i} className="border-t py-2 text-sm">
                    Update {i + 1}
                </div>
            ))}
        </ScrollArea>
    );
}
function SeparatorPreview() {
    return (
        <div className="w-64 space-y-3">
            <div>
                <h4 className="font-medium">Project settings</h4>
                <p className="text-sm text-muted-foreground">
                    Manage your workspace.
                </p>
            </div>
            <Separator />
            <div className="flex h-5 items-center gap-3 text-sm">
                <span>General</span>
                <Separator orientation="vertical" />
                <span>Members</span>
                <Separator orientation="vertical" />
                <span>Billing</span>
            </div>
        </div>
    );
}
function BreadcrumbPreview() {
    return (
        <Breadcrumb>
            <BreadcrumbList>
                <BreadcrumbItem>
                    <BreadcrumbLink
                        href="#"
                        onClick={(e) => e.preventDefault()}
                    >
                        Home
                    </BreadcrumbLink>
                </BreadcrumbItem>
                <BreadcrumbSeparator />
                <BreadcrumbItem>
                    <BreadcrumbLink
                        href="#"
                        onClick={(e) => e.preventDefault()}
                    >
                        Projects
                    </BreadcrumbLink>
                </BreadcrumbItem>
                <BreadcrumbSeparator />
                <BreadcrumbItem>
                    <BreadcrumbPage>Overview</BreadcrumbPage>
                </BreadcrumbItem>
            </BreadcrumbList>
        </Breadcrumb>
    );
}
function CommandPreview() {
    const [selection, setSelection] = useState('None');
    return (
        <div className="w-72 space-y-2">
            <Command className="rounded-lg border">
                <CommandInput placeholder="Search actions..." />
                <CommandList>
                    <CommandEmpty>No actions found.</CommandEmpty>
                    <CommandGroup heading="Actions">
                        {[
                            'Create project',
                            'View settings',
                            'Invite member',
                        ].map((item) => (
                            <CommandItem
                                key={item}
                                onSelect={() => setSelection(item)}
                            >
                                {item}
                            </CommandItem>
                        ))}
                    </CommandGroup>
                </CommandList>
            </Command>
            <p className="text-sm text-muted-foreground">
                Selected: {selection}
            </p>
        </div>
    );
}
function ContextMenuPreview() {
    const [action, setAction] = useState('No action selected');
    return (
        <div className="space-y-2">
            <ContextMenu>
                <ContextMenuTrigger className="flex h-32 w-64 items-center justify-center rounded-md border border-dashed text-sm">
                    Right-click here
                </ContextMenuTrigger>
                <ContextMenuContent>
                    <ContextMenuItem onSelect={() => setAction('Copied')}>
                        Copy
                    </ContextMenuItem>
                    <ContextMenuItem onSelect={() => setAction('Renamed')}>
                        Rename
                    </ContextMenuItem>
                </ContextMenuContent>
            </ContextMenu>
            <p className="text-sm text-muted-foreground">{action}</p>
        </div>
    );
}
function DropdownPreview() {
    const [action, setAction] = useState('Choose an action');
    return (
        <div className="space-y-2">
            <DropdownMenu>
                <DropdownMenuTrigger asChild>
                    <Button variant="outline">Actions</Button>
                </DropdownMenuTrigger>
                <DropdownMenuContent>
                    <DropdownMenuItem
                        onSelect={() => setAction('Edit selected')}
                    >
                        Edit
                    </DropdownMenuItem>
                    <DropdownMenuItem
                        onSelect={() => setAction('Archive selected')}
                    >
                        Archive
                    </DropdownMenuItem>
                </DropdownMenuContent>
            </DropdownMenu>
            <p className="text-sm text-muted-foreground">{action}</p>
        </div>
    );
}
function MenubarPreview() {
    const [action, setAction] = useState('Ready');
    return (
        <div className="space-y-2">
            <Menubar>
                <MenubarMenu>
                    <MenubarTrigger>File</MenubarTrigger>
                    <MenubarContent>
                        <MenubarItem onSelect={() => setAction('New document')}>
                            New document
                        </MenubarItem>
                        <MenubarItem onSelect={() => setAction('Saved')}>
                            Save
                        </MenubarItem>
                    </MenubarContent>
                </MenubarMenu>
                <MenubarMenu>
                    <MenubarTrigger>Edit</MenubarTrigger>
                    <MenubarContent>
                        <MenubarItem onSelect={() => setAction('Copied')}>
                            Copy
                        </MenubarItem>
                    </MenubarContent>
                </MenubarMenu>
            </Menubar>
            <p className="text-sm text-muted-foreground">{action}</p>
        </div>
    );
}
function NavigationMenuPreview() {
    const [section, setSection] = useState('Overview');
    return (
        <div className="space-y-3">
            <NavigationMenu>
                <NavigationMenuList>
                    <NavigationMenuItem>
                        <NavigationMenuTrigger>Explore</NavigationMenuTrigger>
                        <NavigationMenuContent className="min-w-40">
                            {['Overview', 'Guides', 'Examples'].map((item) => (
                                <NavigationMenuLink
                                    key={item}
                                    href="#"
                                    onClick={(e) => {
                                        e.preventDefault();
                                        setSection(item);
                                    }}
                                >
                                    {item}
                                </NavigationMenuLink>
                            ))}
                        </NavigationMenuContent>
                    </NavigationMenuItem>
                </NavigationMenuList>
            </NavigationMenu>
            <p className="text-sm text-muted-foreground">Selected: {section}</p>
        </div>
    );
}
function PaginationPreview() {
    const [page, setPage] = useState(1);
    const go =
        (next: number) => (event: React.MouseEvent<HTMLAnchorElement>) => {
            event.preventDefault();
            setPage(Math.max(1, Math.min(3, next)));
        };
    return (
        <div className="space-y-2">
            <Pagination>
                <PaginationContent>
                    <PaginationItem>
                        <PaginationPrevious
                            href="#"
                            onClick={go(page - 1)}
                            aria-disabled={page === 1}
                        />
                    </PaginationItem>
                    {[1, 2, 3].map((n) => (
                        <PaginationItem key={n}>
                            <PaginationLink
                                href="#"
                                isActive={page === n}
                                onClick={go(n)}
                            >
                                {n}
                            </PaginationLink>
                        </PaginationItem>
                    ))}
                    <PaginationItem>
                        <PaginationNext
                            href="#"
                            onClick={go(page + 1)}
                            aria-disabled={page === 3}
                        />
                    </PaginationItem>
                </PaginationContent>
            </Pagination>
            <p className="text-center text-sm text-muted-foreground">
                Page {page} of 3
            </p>
        </div>
    );
}
function SidebarPreview() {
    const [active, setActive] = useState('Overview');
    return (
        <SidebarProvider className="min-h-0 w-72 overflow-hidden rounded-md border">
            <Sidebar collapsible="none" className="w-full">
                <SidebarContent>
                    <SidebarGroup>
                        <SidebarGroupLabel>Workspace</SidebarGroupLabel>
                        <SidebarGroupContent>
                            <SidebarMenu>
                                {['Overview', 'Projects', 'Settings'].map(
                                    (item) => (
                                        <SidebarMenuItem key={item}>
                                            <SidebarMenuButton
                                                isActive={active === item}
                                                onClick={() => setActive(item)}
                                            >
                                                {item}
                                            </SidebarMenuButton>
                                        </SidebarMenuItem>
                                    ),
                                )}
                            </SidebarMenu>
                        </SidebarGroupContent>
                    </SidebarGroup>
                </SidebarContent>
            </Sidebar>
        </SidebarProvider>
    );
}
function TabsPreview() {
    return (
        <Tabs defaultValue="overview" className="w-72">
            <TabsList>
                <TabsTrigger value="overview">Overview</TabsTrigger>
                <TabsTrigger value="activity">Activity</TabsTrigger>
            </TabsList>
            <TabsContent value="overview">
                Your project is on track.
            </TabsContent>
            <TabsContent value="activity">
                There are no recent updates.
            </TabsContent>
        </Tabs>
    );
}
function AlertDialogPreview() {
    const [status, setStatus] = useState('Project is active');
    return (
        <div className="space-y-2">
            <AlertDialog>
                <AlertDialogTrigger asChild>
                    <Button variant="destructive">Archive project</Button>
                </AlertDialogTrigger>
                <AlertDialogContent>
                    <AlertDialogHeader>
                        <AlertDialogTitle>
                            Archive this project?
                        </AlertDialogTitle>
                        <AlertDialogDescription>
                            You can restore it later from your settings.
                        </AlertDialogDescription>
                    </AlertDialogHeader>
                    <AlertDialogFooter>
                        <AlertDialogCancel>Cancel</AlertDialogCancel>
                        <AlertDialogAction
                            onClick={() => setStatus('Project archived')}
                        >
                            Archive
                        </AlertDialogAction>
                    </AlertDialogFooter>
                </AlertDialogContent>
            </AlertDialog>
            <p className="text-sm text-muted-foreground">{status}</p>
        </div>
    );
}
function DialogPreview() {
    return (
        <Dialog>
            <DialogTrigger asChild>
                <Button variant="outline">Open dialog</Button>
            </DialogTrigger>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Project details</DialogTitle>
                    <DialogDescription>
                        Review the details of your workspace.
                    </DialogDescription>
                </DialogHeader>
                <p className="text-sm">This project is ready for your team.</p>
            </DialogContent>
        </Dialog>
    );
}
function DrawerPreview() {
    return (
        <Drawer>
            <DrawerTrigger asChild>
                <Button variant="outline">Open drawer</Button>
            </DrawerTrigger>
            <DrawerContent>
                <DrawerHeader>
                    <DrawerTitle>Notifications</DrawerTitle>
                    <DrawerDescription>You're all caught up.</DrawerDescription>
                </DrawerHeader>
                <DrawerFooter>
                    <DrawerClose asChild>
                        <Button variant="outline">Close</Button>
                    </DrawerClose>
                </DrawerFooter>
            </DrawerContent>
        </Drawer>
    );
}
function HoverCardPreview() {
    return (
        <HoverCard>
            <HoverCardTrigger asChild>
                <Button variant="link">@designteam</Button>
            </HoverCardTrigger>
            <HoverCardContent>
                <strong>Design team</strong>
                <p className="text-sm text-muted-foreground">
                    Building thoughtful experiences together.
                </p>
            </HoverCardContent>
        </HoverCard>
    );
}
function PopoverPreview() {
    return (
        <Popover>
            <PopoverTrigger asChild>
                <Button variant="outline">Show details</Button>
            </PopoverTrigger>
            <PopoverContent>
                <h4 className="font-medium">Workspace</h4>
                <p className="text-sm text-muted-foreground">
                    Your workspace has three active projects.
                </p>
            </PopoverContent>
        </Popover>
    );
}
function SheetPreview() {
    return (
        <Sheet>
            <SheetTrigger asChild>
                <Button variant="outline">Open sheet</Button>
            </SheetTrigger>
            <SheetContent>
                <SheetHeader>
                    <SheetTitle>Activity</SheetTitle>
                    <SheetDescription>
                        Recent changes in your workspace.
                    </SheetDescription>
                </SheetHeader>
                <p className="px-4 text-sm">Your project was updated today.</p>
            </SheetContent>
        </Sheet>
    );
}
function TooltipPreview() {
    return (
        <TooltipProvider>
            <Tooltip>
                <TooltipTrigger asChild>
                    <Button variant="outline">Hover or focus</Button>
                </TooltipTrigger>
                <TooltipContent>Helpful information</TooltipContent>
            </Tooltip>
        </TooltipProvider>
    );
}

function Example({ label, children }: { label: string; children: ReactNode }) {
    return (
        <section className="space-y-3">
            <h3 className="text-sm font-medium text-muted-foreground">
                {label}
            </h3>
            {children}
        </section>
    );
}

function AdditionalExample({ name }: { name: string }) {
    const [checked, setChecked] = useState(true);
    const [selection, setSelection] = useState('Overview');

    switch (name) {
        case 'accordion':
            return (
                <Example label="Multiple open and disabled">
                    <Accordion
                        type="multiple"
                        defaultValue={['one', 'two']}
                        className="w-80"
                    >
                        {['one', 'two', 'disabled'].map((item) => (
                            <AccordionItem
                                key={item}
                                value={item}
                                disabled={item === 'disabled'}
                            >
                                <AccordionTrigger>
                                    {item === 'disabled'
                                        ? 'Disabled section'
                                        : `Section ${item}`}
                                </AccordionTrigger>
                                <AccordionContent>
                                    Multiple sections can remain open.
                                </AccordionContent>
                            </AccordionItem>
                        ))}
                    </Accordion>
                </Example>
            );
        case 'aspect-ratio':
            return (
                <Example label="Square and portrait">
                    <div className="flex gap-4">
                        {[
                            [1, '1:1'],
                            [3 / 4, '3:4'],
                        ].map(([ratio, label]) => (
                            <div key={label} className="w-28">
                                <AspectRatio
                                    ratio={Number(ratio)}
                                    className="flex items-center justify-center rounded-md bg-muted text-sm"
                                >
                                    {label}
                                </AspectRatio>
                            </div>
                        ))}
                    </div>
                </Example>
            );
        case 'carousel':
            return (
                <Example label="Vertical carousel">
                    <Carousel orientation="vertical" className="my-12 w-56">
                        <CarouselContent className="h-32">
                            {['First', 'Second', 'Third'].map((item) => (
                                <CarouselItem key={item}>
                                    <div className="flex h-32 items-center justify-center rounded-md border">
                                        {item}
                                    </div>
                                </CarouselItem>
                            ))}
                        </CarouselContent>
                        <CarouselPrevious />
                        <CarouselNext />
                    </Carousel>
                </Example>
            );
        case 'collapsible':
            return (
                <Example label="Initially open">
                    <Collapsible defaultOpen className="w-72 space-y-2">
                        <CollapsibleTrigger asChild>
                            <Button variant="outline" size="sm">
                                Toggle details
                            </Button>
                        </CollapsibleTrigger>
                        <CollapsibleContent className="rounded-md border p-3 text-sm">
                            These details start expanded.
                        </CollapsibleContent>
                    </Collapsible>
                </Example>
            );
        case 'resizable':
            return (
                <Example label="Vertical panels">
                    <ResizablePanelGroup
                        orientation="vertical"
                        className="h-40 w-72 rounded-md border"
                    >
                        <ResizablePanel defaultSize={50}>
                            <div className="p-3">Editor</div>
                        </ResizablePanel>
                        <ResizableHandle withHandle />
                        <ResizablePanel defaultSize={50}>
                            <div className="p-3">Console</div>
                        </ResizablePanel>
                    </ResizablePanelGroup>
                </Example>
            );
        case 'scroll-area':
            return (
                <Example label="Horizontal scrolling">
                    <ScrollArea className="w-64 rounded-md border">
                        <div className="flex w-max gap-3 p-4">
                            {[
                                'Planning',
                                'Design',
                                'Development',
                                'Review',
                            ].map((item) => (
                                <div
                                    key={item}
                                    className="rounded-md bg-muted p-4 text-sm"
                                >
                                    {item}
                                </div>
                            ))}
                        </div>
                        <ScrollBar orientation="horizontal" />
                    </ScrollArea>
                </Example>
            );
        case 'separator':
            return (
                <Example label="Vertical separator">
                    <div className="flex h-8 items-center gap-3 text-sm">
                        <span>Documentation</span>
                        <Separator orientation="vertical" />
                        <span>Support</span>
                    </div>
                </Example>
            );
        case 'breadcrumb':
            return (
                <Example label="Collapsed with custom separator">
                    <Breadcrumb>
                        <BreadcrumbList>
                            <BreadcrumbItem>
                                <BreadcrumbLink
                                    href="#"
                                    onClick={(event) => event.preventDefault()}
                                >
                                    Home
                                </BreadcrumbLink>
                            </BreadcrumbItem>
                            <BreadcrumbSeparator>/</BreadcrumbSeparator>
                            <BreadcrumbItem>
                                <BreadcrumbEllipsis />
                            </BreadcrumbItem>
                            <BreadcrumbSeparator>/</BreadcrumbSeparator>
                            <BreadcrumbItem>
                                <BreadcrumbPage>Current page</BreadcrumbPage>
                            </BreadcrumbItem>
                        </BreadcrumbList>
                    </Breadcrumb>
                </Example>
            );
        case 'command':
            return (
                <Example label="Groups and disabled action">
                    <Command className="w-72 rounded-lg border">
                        <CommandInput placeholder="Search settings..." />
                        <CommandList>
                            <CommandEmpty>No settings found.</CommandEmpty>
                            <CommandGroup heading="Settings">
                                <CommandItem
                                    onSelect={() => setSelection('Profile')}
                                >
                                    Profile
                                </CommandItem>
                                <CommandItem disabled>
                                    Billing (disabled)
                                </CommandItem>
                            </CommandGroup>
                        </CommandList>
                    </Command>
                    <p className="text-sm text-muted-foreground">
                        Selected: {selection}
                    </p>
                </Example>
            );
        case 'context-menu':
            return (
                <Example label="Checkbox, disabled and destructive">
                    <ContextMenu>
                        <ContextMenuTrigger className="flex h-24 w-64 items-center justify-center rounded-md border border-dashed text-sm">
                            Right-click for options
                        </ContextMenuTrigger>
                        <ContextMenuContent>
                            <ContextMenuCheckboxItem
                                checked={checked}
                                onCheckedChange={setChecked}
                            >
                                Show details
                            </ContextMenuCheckboxItem>
                            <ContextMenuItem disabled>
                                Unavailable
                            </ContextMenuItem>
                            <ContextMenuItem
                                variant="destructive"
                                onSelect={() => setSelection('Delete selected')}
                            >
                                Delete
                            </ContextMenuItem>
                        </ContextMenuContent>
                    </ContextMenu>
                    <p className="text-sm text-muted-foreground">
                        {selection === 'Overview'
                            ? `Details: ${checked ? 'shown' : 'hidden'}`
                            : selection}
                    </p>
                </Example>
            );
        case 'dropdown-menu':
            return (
                <Example label="Checkbox, disabled and destructive">
                    <DropdownMenu>
                        <DropdownMenuTrigger asChild>
                            <Button variant="outline">Options</Button>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent>
                            <DropdownMenuCheckboxItem
                                checked={checked}
                                onCheckedChange={setChecked}
                            >
                                Show details
                            </DropdownMenuCheckboxItem>
                            <DropdownMenuItem disabled>
                                Unavailable
                            </DropdownMenuItem>
                            <DropdownMenuItem
                                variant="destructive"
                                onSelect={() => setSelection('Delete selected')}
                            >
                                Delete
                            </DropdownMenuItem>
                        </DropdownMenuContent>
                    </DropdownMenu>
                    <p className="text-sm text-muted-foreground">
                        {selection === 'Overview'
                            ? `Details: ${checked ? 'shown' : 'hidden'}`
                            : selection}
                    </p>
                </Example>
            );
        case 'menubar':
            return (
                <Example label="Disabled and destructive items">
                    <Menubar>
                        <MenubarMenu>
                            <MenubarTrigger>Project</MenubarTrigger>
                            <MenubarContent>
                                <MenubarItem disabled>
                                    Export unavailable
                                </MenubarItem>
                                <MenubarItem
                                    variant="destructive"
                                    onSelect={() =>
                                        setSelection('Project removed')
                                    }
                                >
                                    Remove project
                                </MenubarItem>
                            </MenubarContent>
                        </MenubarMenu>
                    </Menubar>
                    <p className="text-sm text-muted-foreground">{selection}</p>
                </Example>
            );
        case 'navigation-menu':
            return (
                <Example label="Direct and active links">
                    <NavigationMenu>
                        <NavigationMenuList>
                            {['Overview', 'Guides'].map((item) => (
                                <NavigationMenuItem key={item}>
                                    <NavigationMenuLink
                                        href="#"
                                        active={selection === item}
                                        onClick={(event) => {
                                            event.preventDefault();
                                            setSelection(item);
                                        }}
                                    >
                                        {item}
                                    </NavigationMenuLink>
                                </NavigationMenuItem>
                            ))}
                        </NavigationMenuList>
                    </NavigationMenu>
                </Example>
            );
        case 'pagination':
            return (
                <Example label="Compact page links">
                    <Pagination>
                        <PaginationContent>
                            {[1, 2, 3].map((page) => (
                                <PaginationItem key={page}>
                                    <PaginationLink
                                        href="#"
                                        size="icon-sm"
                                        isActive={selection === String(page)}
                                        onClick={(event) => {
                                            event.preventDefault();
                                            setSelection(String(page));
                                        }}
                                    >
                                        {page}
                                    </PaginationLink>
                                </PaginationItem>
                            ))}
                        </PaginationContent>
                    </Pagination>
                </Example>
            );
        case 'sidebar':
            return (
                <Example label="Menu button sizes and outline">
                    <SidebarProvider className="min-h-0 w-72 overflow-hidden rounded-md border">
                        <Sidebar collapsible="none" className="w-full">
                            <SidebarContent>
                                <SidebarGroup>
                                    <SidebarGroupLabel>
                                        Menu button examples
                                    </SidebarGroupLabel>
                                    <SidebarGroupContent>
                                        <SidebarMenu>
                                            {(
                                                ['sm', 'default', 'lg'] as const
                                            ).map((size) => (
                                                <SidebarMenuItem key={size}>
                                                    <SidebarMenuButton
                                                        size={size}
                                                        variant={
                                                            size === 'default'
                                                                ? 'outline'
                                                                : 'default'
                                                        }
                                                        isActive={
                                                            selection === size
                                                        }
                                                        onClick={() =>
                                                            setSelection(size)
                                                        }
                                                    >
                                                        {size}
                                                        {size === 'default'
                                                            ? ' / outline'
                                                            : ''}
                                                    </SidebarMenuButton>
                                                </SidebarMenuItem>
                                            ))}
                                            <SidebarMenuItem>
                                                <SidebarMenuButton disabled>
                                                    Disabled
                                                </SidebarMenuButton>
                                            </SidebarMenuItem>
                                        </SidebarMenu>
                                    </SidebarGroupContent>
                                </SidebarGroup>
                            </SidebarContent>
                        </Sidebar>
                    </SidebarProvider>
                </Example>
            );
        case 'tabs':
            return (
                <>
                    <Example label="Line and disabled">
                        <Tabs defaultValue="one" className="w-72">
                            <TabsList variant="line">
                                <TabsTrigger value="one">Account</TabsTrigger>
                                <TabsTrigger value="two">Security</TabsTrigger>
                                <TabsTrigger value="disabled" disabled>
                                    Disabled
                                </TabsTrigger>
                            </TabsList>
                            <TabsContent value="one">
                                Account settings
                            </TabsContent>
                            <TabsContent value="two">
                                Security settings
                            </TabsContent>
                        </Tabs>
                    </Example>
                    <Example label="Vertical">
                        <Tabs
                            orientation="vertical"
                            defaultValue="one"
                            className="w-72"
                        >
                            <TabsList>
                                <TabsTrigger value="one">Profile</TabsTrigger>
                                <TabsTrigger value="two">Team</TabsTrigger>
                            </TabsList>
                            <TabsContent value="one">Your profile</TabsContent>
                            <TabsContent value="two">Your team</TabsContent>
                        </Tabs>
                    </Example>
                </>
            );
        case 'alert-dialog':
            return (
                <Example label="Small confirmation">
                    <AlertDialog>
                        <AlertDialogTrigger asChild>
                            <Button variant="outline">Open small alert</Button>
                        </AlertDialogTrigger>
                        <AlertDialogContent size="sm">
                            <AlertDialogHeader>
                                <AlertDialogTitle>
                                    Discard changes?
                                </AlertDialogTitle>
                                <AlertDialogDescription>
                                    Unsaved changes will be lost.
                                </AlertDialogDescription>
                            </AlertDialogHeader>
                            <AlertDialogFooter>
                                <AlertDialogCancel>Cancel</AlertDialogCancel>
                                <AlertDialogAction
                                    variant="destructive"
                                    onClick={() =>
                                        setSelection('Changes discarded')
                                    }
                                >
                                    Discard
                                </AlertDialogAction>
                            </AlertDialogFooter>
                        </AlertDialogContent>
                    </AlertDialog>
                    <p className="text-sm text-muted-foreground">
                        {selection === 'Overview'
                            ? 'Changes pending'
                            : selection}
                    </p>
                </Example>
            );
        case 'dialog':
            return (
                <Example label="Form dialog">
                    <Dialog>
                        <DialogTrigger asChild>
                            <Button variant="outline">Edit name</Button>
                        </DialogTrigger>
                        <DialogContent>
                            <DialogHeader>
                                <DialogTitle>Edit name</DialogTitle>
                                <DialogDescription>
                                    Update the preview name.
                                </DialogDescription>
                            </DialogHeader>
                            <label className="grid gap-2 text-sm">
                                Name
                                <input
                                    className="rounded-md border bg-background p-2"
                                    value={selection}
                                    onChange={(event) =>
                                        setSelection(event.target.value)
                                    }
                                />
                            </label>
                        </DialogContent>
                    </Dialog>
                    <p className="text-sm text-muted-foreground">
                        Name: {selection}
                    </p>
                </Example>
            );
        case 'drawer':
            return (
                <Example label="Drawer directions">
                    <div className="flex flex-wrap gap-2">
                        {(['top', 'left', 'right'] as const).map(
                            (direction) => (
                                <Drawer key={direction} direction={direction}>
                                    <DrawerTrigger asChild>
                                        <Button variant="outline">
                                            {direction}
                                        </Button>
                                    </DrawerTrigger>
                                    <DrawerContent>
                                        <DrawerHeader>
                                            <DrawerTitle>
                                                {direction} drawer
                                            </DrawerTitle>
                                            <DrawerDescription>
                                                A drawer opening from the{' '}
                                                {direction}.
                                            </DrawerDescription>
                                        </DrawerHeader>
                                        <DrawerFooter>
                                            <DrawerClose asChild>
                                                <Button variant="outline">
                                                    Close
                                                </Button>
                                            </DrawerClose>
                                        </DrawerFooter>
                                    </DrawerContent>
                                </Drawer>
                            ),
                        )}
                    </div>
                </Example>
            );
        case 'hover-card':
            return (
                <Example label="Placement">
                    <div className="flex gap-2">
                        {(['top', 'bottom'] as const).map((side) => (
                            <HoverCard key={side}>
                                <HoverCardTrigger asChild>
                                    <Button variant="outline">{side}</Button>
                                </HoverCardTrigger>
                                <HoverCardContent side={side}>
                                    A card positioned on the {side}.
                                </HoverCardContent>
                            </HoverCard>
                        ))}
                    </div>
                </Example>
            );
        case 'popover':
            return (
                <Example label="Placement">
                    <div className="flex gap-2">
                        {(['top', 'bottom'] as const).map((side) => (
                            <Popover key={side}>
                                <PopoverTrigger asChild>
                                    <Button variant="outline">{side}</Button>
                                </PopoverTrigger>
                                <PopoverContent side={side}>
                                    Popover on the {side}.
                                </PopoverContent>
                            </Popover>
                        ))}
                    </div>
                </Example>
            );
        case 'sheet':
            return (
                <Example label="Sheet sides">
                    <div className="flex flex-wrap gap-2">
                        {(['top', 'right', 'bottom', 'left'] as const).map(
                            (side) => (
                                <Sheet key={side}>
                                    <SheetTrigger asChild>
                                        <Button variant="outline">
                                            {side}
                                        </Button>
                                    </SheetTrigger>
                                    <SheetContent side={side}>
                                        <SheetHeader>
                                            <SheetTitle>
                                                {side} sheet
                                            </SheetTitle>
                                            <SheetDescription>
                                                Content from the {side} edge.
                                            </SheetDescription>
                                        </SheetHeader>
                                    </SheetContent>
                                </Sheet>
                            ),
                        )}
                    </div>
                </Example>
            );
        case 'tooltip':
            return (
                <Example label="Tooltip sides">
                    <TooltipProvider>
                        <div className="flex flex-wrap gap-2">
                            {(['top', 'right', 'bottom', 'left'] as const).map(
                                (side) => (
                                    <Tooltip key={side}>
                                        <TooltipTrigger asChild>
                                            <Button variant="outline">
                                                {side}
                                            </Button>
                                        </TooltipTrigger>
                                        <TooltipContent side={side}>
                                            Tooltip on the {side}
                                        </TooltipContent>
                                    </Tooltip>
                                ),
                            )}
                        </div>
                    </TooltipProvider>
                </Example>
            );
        default:
            return null;
    }
}

function withExamples(name: string, Preview: ComponentType) {
    return function PreviewExamples() {
        return (
            <div className="flex flex-col gap-8">
                <Example label="Default">
                    <Preview />
                </Example>
                <AdditionalExample name={name} />
            </div>
        );
    };
}

export const navigationLayoutPreviews = {
    accordion: withExamples('accordion', AccordionPreview),
    'aspect-ratio': withExamples('aspect-ratio', AspectRatioPreview),
    carousel: withExamples('carousel', CarouselPreview),
    collapsible: withExamples('collapsible', CollapsiblePreview),
    resizable: withExamples('resizable', ResizablePreview),
    'scroll-area': withExamples('scroll-area', ScrollAreaPreview),
    separator: withExamples('separator', SeparatorPreview),
    breadcrumb: withExamples('breadcrumb', BreadcrumbPreview),
    command: withExamples('command', CommandPreview),
    'context-menu': withExamples('context-menu', ContextMenuPreview),
    'dropdown-menu': withExamples('dropdown-menu', DropdownPreview),
    menubar: withExamples('menubar', MenubarPreview),
    'navigation-menu': withExamples('navigation-menu', NavigationMenuPreview),
    pagination: withExamples('pagination', PaginationPreview),
    sidebar: withExamples('sidebar', SidebarPreview),
    tabs: withExamples('tabs', TabsPreview),
    'alert-dialog': withExamples('alert-dialog', AlertDialogPreview),
    dialog: withExamples('dialog', DialogPreview),
    drawer: withExamples('drawer', DrawerPreview),
    'hover-card': withExamples('hover-card', HoverCardPreview),
    popover: withExamples('popover', PopoverPreview),
    sheet: withExamples('sheet', SheetPreview),
    tooltip: withExamples('tooltip', TooltipPreview),
};
