<?php

namespace App\Services;

use App\Models\DiagEvent;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class DiagnosticEventService
{
    /** Fixed public registry; never derive codes from exception data. */
    private const REGISTRY = [
        'server' => ['suffix' => 'SYS-000', 'summary' => 'An unexpected server error occurred.', 'severity' => 'critical'],
        'database' => ['suffix' => 'SYS-001', 'summary' => 'A database operation failed.', 'severity' => 'critical'],
        'unavailable' => ['suffix' => 'SYS-002', 'summary' => 'The service is temporarily unavailable.', 'severity' => 'error'],
    ];

    /** @return array{reference_id: string, error_code: string} */
    public function record(Request $request, Throwable $exception, int $status): array
    {
        $reference = (string) Str::uuid();
        $key = $exception instanceof QueryException ? 'database' : ($status === 503 ? 'unavailable' : 'server');
        $entry = self::REGISTRY[$key];
        $prefix = (string) config('app.error_prefix', 'FUY');
        $code = $prefix.'-'.$entry['suffix'];

        try {
            // Logs deliberately omit exception message, trace, request data, and SQL bindings.
            Log::error('Unhandled application exception.', [
                'reference_id' => $reference,
                'error_code' => $code,
                'http_status' => $status,
                'exception_class' => $exception::class,
                'route_name' => $request->route()?->getName(),
            ]);
        } catch (Throwable) {
            // A failed private logging backend must not replace the public error response.
        }

        try {
            DiagEvent::query()->create([
                'reference_id' => $reference,
                'error_code' => $code,
                'http_status' => $status,
                'severity' => $entry['severity'],
                'route_name' => $request->route()?->getName(),
                'exception_class' => $exception::class,
                'summary' => $entry['summary'],
                'user_id' => $request->user()?->getAuthIdentifier(),
                'occured_at' => now(),
            ]);
        } catch (Throwable) {
            // Diagnostics must never prevent a safe error response (including DB outages).
        }

        return ['reference_id' => $reference, 'error_code' => $code];
    }
}
