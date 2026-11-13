import { useState } from 'react';
import { Button } from '@/components/ui/button';
import {
    CodeBlock,
    CodeBlockContent,
    CodeBlockCopyButton,
    CodeBlockHeader,
    CodeBlockLine,
    CodeBlockTitle,
} from '@/components/ui/code-block';
import { DirectionProvider, useDirection } from '@/components/ui/direction';

import {
    Trace,
    TraceCollapsible,
    TraceCollapsibleContent,
    TraceCollapsibleTrigger,
    TraceDescription,
    TraceLine,
    TraceTitle,
} from '@/components/ui/trace';

function CodeBlockPreview() {
    const code = 'const greeting = "Hello, world!";';
    return (
        <div className="grid w-full gap-4">
            <div className="grid gap-2">
                <p>Default · line numbers and copy</p>
                <CodeBlock>
                    <CodeBlockHeader>
                        <CodeBlockTitle>example.ts</CodeBlockTitle>
                        <CodeBlockCopyButton value={code} />
                    </CodeBlockHeader>
                    <CodeBlockContent lineNumbers>{code}</CodeBlockContent>
                </CodeBlock>
            </div>
            <div className="grid gap-2">
                <p>Small · wrapped</p>
                <CodeBlock size="sm">
                    <CodeBlockContent wrap>
                        {
                            'A long line of text can wrap instead of requiring horizontal scrolling.'
                        }
                    </CodeBlockContent>
                </CodeBlock>
            </div>
            <div className="grid gap-2">
                <p>Destructive</p>
                <CodeBlock variant="destructive">
                    <CodeBlockContent>
                        {'Error: Unable to complete the request.'}
                    </CodeBlockContent>
                </CodeBlock>
            </div>
            <div className="grid gap-2">
                <p>Line variants · default, added, removed, highlighted</p>
                <CodeBlock>
                    <CodeBlockContent lineNumbers startLine={10}>
                        <CodeBlockLine>
                            const status = getStatus();
                        </CodeBlockLine>
                        <CodeBlockLine variant="added">
                            return status;
                        </CodeBlockLine>
                        <CodeBlockLine variant="removed">
                            return null;
                        </CodeBlockLine>
                        <CodeBlockLine variant="highlighted">
                            // Review this line
                        </CodeBlockLine>
                    </CodeBlockContent>
                </CodeBlock>
            </div>
        </div>
    );
}

function DirectionContent() {
    const direction = useDirection();
    return <p dir={direction}>Current direction: {direction}</p>;
}

function DirectionPreview() {
    const [direction, setDirection] = useState<'ltr' | 'rtl'>('ltr');
    return (
        <div className="space-y-4">
            <p>Interactive · left-to-right / right-to-left</p>
            <Button
                variant="outline"
                onClick={() =>
                    setDirection(direction === 'ltr' ? 'rtl' : 'ltr')
                }
            >
                Toggle direction
            </Button>
            <DirectionProvider dir={direction}>
                <DirectionContent />
            </DirectionProvider>
        </div>
    );
}

function TracePreview() {
    return (
        <div className="grid w-full gap-4">
            <div className="grid gap-2">
                <p>Statuses · default, running, pending, error</p>
                <Trace>
                    <TraceLine>
                        <TraceTitle>Request received</TraceTitle>
                        <TraceDescription>120 ms</TraceDescription>
                    </TraceLine>
                    <TraceLine status="running">
                        <TraceTitle>Running</TraceTitle>
                    </TraceLine>
                    <TraceLine status="pending">
                        <TraceTitle>Pending</TraceTitle>
                    </TraceLine>
                    <TraceLine status="error">
                        <TraceTitle>Error</TraceTitle>
                    </TraceLine>
                </Trace>
            </div>
            <div className="grid gap-2">
                <p>Collapsible details</p>
                <Trace>
                    <TraceCollapsible>
                        <TraceCollapsibleTrigger>
                            <TraceLine>
                                <TraceTitle>Request details</TraceTitle>
                            </TraceLine>
                        </TraceCollapsibleTrigger>
                        <TraceCollapsibleContent>
                            <TraceLine>
                                <TraceTitle>Response received</TraceTitle>
                                <TraceDescription>200 OK</TraceDescription>
                            </TraceLine>
                        </TraceCollapsibleContent>
                    </TraceCollapsible>
                </Trace>
            </div>
        </div>
    );
}

export const additionalPreviews = {
    'code-block': CodeBlockPreview,
    direction: DirectionPreview,

    trace: TracePreview,
};
