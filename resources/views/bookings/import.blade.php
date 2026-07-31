@extends('layouts.app')
@section('title', 'Import Bookings')
@section('page_title', 'Import Bookings')
@section('content')
<div class="row justify-content-center">
    <div class="col-md-10 col-lg-8">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Import Bookings from File</h5>
            </div>
            <div class="card-body">
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i>
                    <strong>Instructions:</strong>
                    <ul class="mb-0 mt-2">
                        <li>Download the template file to see the required format</li>
                        <li>Fill in your booking data in the template</li>
                        <li>Required fields: guest info, property, check-in, check-out dates</li>
                        <li>Date format must be YYYY-MM-DD (e.g., 2024-12-25)</li>
                        <li>Status options: expected_arrival, check_in, expected_departure, cancelled</li>
                        <li>Save as CSV, XLS, or XLSX format</li>
                        <li>Upload the file below to import</li>
                    </ul>
                </div>

                <div class="mb-4">
                    <a href="{{ route('bookings.download-template') }}" class="btn btn-outline-primary">
                        <i class="fas fa-download"></i> Download Template
                    </a>
                </div>

                <form method="POST" action="{{ route('bookings.process-import') }}" enctype="multipart/form-data">
                    @csrf
                    
                    <div class="mb-3">
                        <label for="file" class="form-label">Select File</label>
                        <input type="file" 
                               class="form-control @error('file') is-invalid @enderror" 
                               id="file" 
                               name="file" 
                               accept=".csv,.xls,.xlsx"
                               required>
                        @error('file')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="text-muted">Maximum file size: 10 MB. Supported formats: CSV, XLS, XLSX</small>
                    </div>

                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle"></i>
                        <strong>Notes:</strong>
                        <ul class="mb-0">
                            <li>Bookings with duplicate references will be skipped</li>
                            <li>If guest email is provided, existing guest will be used, otherwise new guest will be created</li>
                            <li>Property must exist in the system (match by name or ID)</li>
                            <li>Room is optional, but must exist if provided</li>
                        </ul>
                    </div>

                    <div class="d-flex justify-content-between">
                        <a href="{{ route('bookings.index') }}" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Cancel
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-upload"></i> Import Bookings
                        </button>
                    </div>
                </form>

                @if(session('import_failures') && count(session('import_failures')) > 0)
                <div class="mt-4">
                    <h6 class="text-danger">Import Failures</h6>
                    <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                        <table class="table table-sm table-bordered">
                            <thead class="sticky-top bg-white">
                                <tr>
                                    <th>Row</th>
                                    <th>Field</th>
                                    <th>Errors</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach(session('import_failures') as $failure)
                                <tr>
                                    <td>{{ $failure['row'] }}</td>
                                    <td>{{ $failure['attribute'] }}</td>
                                    <td>{{ implode(', ', $failure['errors']) }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                @endif

                @if(session('import_errors') && count(session('import_errors')) > 0)
                <div class="mt-4">
                    <h6 class="text-danger">General Errors</h6>
                    <ul class="text-danger">
                        @foreach(session('import_errors') as $error)
                        <li>{{ is_string($error) ? $error : (is_array($error) ? ($error['message'] ?? json_encode($error)) : '') }}</li>
                        @endforeach
                    </ul>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
