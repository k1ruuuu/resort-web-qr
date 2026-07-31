@extends('layouts.app')
@section('title', 'Import Rooms')
@section('page_title', 'Import Rooms')
@section('content')
<div class="row justify-content-center">
    <div class="col-md-8 col-lg-6">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Import Rooms from File</h5>
            </div>
            <div class="card-body">
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i>
                    <strong>Instructions:</strong>
                    <ul class="mb-0 mt-2">
                        <li>Download the template file to see the required format</li>
                        <li>Fill in your room data in the template</li>
                        <li>Save as CSV, XLS, or XLSX format</li>
                        <li>Upload the file below to import</li>
                    </ul>
                </div>

                <div class="mb-4">
                    <a href="{{ route('rooms.download-template') }}" class="btn btn-outline-primary">
                        <i class="fas fa-download"></i> Download Template
                    </a>
                </div>

                <form method="POST" action="{{ route('rooms.process-import') }}" enctype="multipart/form-data">
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
                        <strong>Note:</strong> Rooms with duplicate numbers within the same property will be skipped. Property, room type, and area must already exist in the system.
                    </div>

                    <div class="d-flex justify-content-between">
                        <a href="{{ route('rooms.index') }}" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Cancel
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-upload"></i> Import Rooms
                        </button>
                    </div>
                </form>

                @if(session('import_failures') && count(session('import_failures')) > 0)
                <div class="mt-4">
                    <h6 class="text-danger">Import Failures</h6>
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered">
                            <thead>
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
                        <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
