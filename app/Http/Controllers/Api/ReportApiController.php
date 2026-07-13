<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\ApiController;
use App\Models\Outlet;
use App\Models\Property;
use App\Models\FacilityTemplate;
use App\Services\ReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReportApiController extends ApiController
{
    public function __construct(
        private readonly ReportService $reports,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorizePermission('reports.view');

        $data = $this->reports->generate(
            $request->input('filter_type', 'date_range'),
            $request->input('from'),
            $request->input('to'),
            $request->integer('month'),
            $request->integer('year'),
            $request->integer('property_id'),
            $request->integer('facility_id'),
            $request->integer('outlet_id'),
        );

        return $this->respond($data);
    }

    public function formData(): JsonResponse
    {
        $this->authorizePermission('reports.view');

        return $this->respond([
            'properties' => Property::query()->orderBy('name')->get(),
            'facilities' => FacilityTemplate::query()->where('is_active', true)->orderBy('name')->get(),
            'outlets' => Outlet::query()->where('is_active', true)->orderBy('name')->get(),
        ]);
    }
}
