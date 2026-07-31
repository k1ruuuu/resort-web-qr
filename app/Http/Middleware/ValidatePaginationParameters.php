<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ValidatePaginationParameters
{
    /**
     * Handle an incoming request.
     *
     * SECURITY: Prevent pagination parameter manipulation attacks
     * Validates and sanitizes pagination parameters to prevent SQL injection,
     * resource exhaustion, and other attacks via pagination parameters
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Validate 'page' parameter
        if ($request->has('page')) {
            $page = $request->input('page');
            
            // SECURITY: Only allow positive integers for page parameter
            if (!is_numeric($page) || $page < 1 || $page > 10000) {
                \Log::warning('[SECURITY] Invalid pagination page parameter', [
                    'page' => $page,
                    'ip' => $request->ip(),
                    'url' => $request->fullUrl(),
                ]);
                
                // Reset to safe default
                $request->merge(['page' => 1]);
            } else {
                $request->merge(['page' => (int) $page]);
            }
        }
        
        // Validate 'per_page' parameter if present
        if ($request->has('per_page')) {
            $perPage = $request->input('per_page');
            
            // SECURITY: Limit per_page to prevent resource exhaustion
            // Maximum 100 items per page to prevent DOS attacks
            if (!is_numeric($perPage) || $perPage < 1 || $perPage > 100) {
                \Log::warning('[SECURITY] Invalid pagination per_page parameter', [
                    'per_page' => $perPage,
                    'ip' => $request->ip(),
                    'url' => $request->fullUrl(),
                ]);
                
                // Reset to safe default
                $request->merge(['per_page' => 20]);
            } else {
                $request->merge(['per_page' => (int) $perPage]);
            }
        }
        
        // Validate 'sort' parameter if present
        if ($request->has('sort')) {
            $sort = $request->input('sort');

            // SECURITY: Only allow alphanumeric characters and underscores in sort field
            // Prevents SQL injection through ORDER BY clause
            // L-03: additionally reject sensitive / non-displayable columns
            $sensitiveColumns = [
                'password', 'remember_token', 'secret', 'token', 'api_token',
                'created_at', 'updated_at', 'deleted_at', 'ip_address',
            ];

            if (
                !preg_match('/^[a-zA-Z0-9_]+$/', $sort)
                || in_array(strtolower($sort), $sensitiveColumns, true)
            ) {
                \Log::warning('[SECURITY] Invalid sort parameter', [
                    'sort' => $sort,
                    'ip' => $request->ip(),
                    'url' => $request->fullUrl(),
                ]);

                // L-02: remove from both the POST and QUERY bags so GET listings
                // cannot bypass the guard via raw query string.
                $request->request->remove('sort');
                $request->query->remove('sort');
            }
        }
        
        // Validate 'order' or 'direction' parameter if present
        if ($request->has('order') || $request->has('direction')) {
            $order = $request->input('order') ?? $request->input('direction');
            
            // SECURITY: Only allow 'asc' or 'desc'
            if (!in_array(strtolower($order), ['asc', 'desc'], true)) {
                \Log::warning('[SECURITY] Invalid order/direction parameter', [
                    'order' => $order,
                    'ip' => $request->ip(),
                    'url' => $request->fullUrl(),
                ]);
                
                // Reset to safe default
                $request->merge(['order' => 'asc', 'direction' => 'asc']);
            } else {
                $order = strtolower($order);
                $request->merge(['order' => $order, 'direction' => $order]);
            }
        }

        return $next($request);
    }
}
