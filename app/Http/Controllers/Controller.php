<?php

namespace App\Http\Controllers;

use Illuminate\Database\Eloquent\Builder;

abstract class Controller
{
    protected function applyPropertyScope(Builder $query, string $column = 'property_id'): Builder
    {
        $user = auth()->user();

        if (!$user || $user->hasRole('super-admin')) {
            return $query;
        }

        $propertyIds = $user->properties()->pluck('property_id');

        if ($propertyIds->isNotEmpty()) {
            $query->whereIn($column, $propertyIds);
        } else {
            $query->whereRaw('1 = 0');
        }

        return $query;
    }

    protected function authorizePropertyAccess(object $model, string $column = 'property_id'): void
    {
        $user = auth()->user();

        if (!$user || $user->hasRole('super-admin')) {
            return;
        }

        $allowed = $user->properties()
            ->where($column, $model->{$column} ?? $model->getAttribute($column))
            ->exists();

        abort_unless($allowed, 403);
    }

    protected function getExcelType(string $format): string
    {
        return match($format) {
            'csv' => \Maatwebsite\Excel\Excel::CSV,
            'xls' => \Maatwebsite\Excel\Excel::XLS,
            'xlsx' => \Maatwebsite\Excel\Excel::XLSX,
            default => \Maatwebsite\Excel\Excel::XLSX,
        };
    }
}
