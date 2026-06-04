<?php

namespace App\Http\Middleware;

use App\Services\AuditService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuditRequest
{
    public function __construct(private readonly AuditService $audit) {}

    public function handle(Request $request, Closure $next, string $action): Response
    {
        $response = $next($request);

        if ($request->user()) {
            $this->audit->log($action, null, null, [
                'method' => $request->method(),
                'path' => $request->path(),
                'status' => $response->getStatusCode(),
            ], $request);
        }

        return $response;
    }
}
