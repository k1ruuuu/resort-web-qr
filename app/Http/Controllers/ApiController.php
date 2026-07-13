<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;

abstract class ApiController extends Controller
{
    protected function respond(mixed $data, int $status = 200, array $headers = []): JsonResponse
    {
        return response()->json(['data' => $data], $status, $headers);
    }

    protected function respondMessage(string $message, int $status = 200): JsonResponse
    {
        return response()->json(['message' => $message], $status);
    }

    protected function respondCreated(mixed $data): JsonResponse
    {
        return $this->respond($data, 201);
    }

    protected function respondError(string $message, int $status = 422): JsonResponse
    {
        return response()->json(['message' => $message], $status);
    }

    protected function respondPaginated(\Illuminate\Contracts\Pagination\LengthAwarePaginator $paginator): JsonResponse
    {
        return response()->json([
            'data' => $paginator->items(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
            ],
        ]);
    }

    protected function authorizePermission(string $permission): void
    {
        abort_unless(request()->user()?->can($permission), 403);
    }
}
