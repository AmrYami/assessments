@extends('dashboard.mt.main', ['page' => $page])

@section('content')
    <form method="post" action="{{ route('dashboard.assessments.presets.store') }}" class="card">
        @csrf
        <div class="card-body">
            <div class="row mb-3">
                <div class="col-md-4">
                    <label class="form-label">Slug</label>
                    <input name="slug" class="form-control" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Label</label>
                    <input name="label" class="form-control" required>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Input Type</label>
                    <select name="input_type" class="form-select"><option value="text">Text</option><option value="textarea">Textarea</option></select>
                </div>
                <div class="col-md-2 form-check form-switch d-flex align-items-end">
                    <input class="form-check-input" type="checkbox" name="is_active" value="1" checked>
                    <label class="form-check-label ms-2">Active</label>
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label">Spec (JSON)</label>
                <textarea name="spec_json" class="form-control" rows="6" placeholder='{"required":true,"placeholder":"name@example.com","regex":"^[^\\\s@]+@[^\\\s@]+\\\\.[^\\\s@]+$"}'></textarea>
            </div>
        </div>
        <div class="card-footer d-flex justify-content-end">
            <button class="btn btn-primary">Save</button>
        </div>
    </form>
@endsection

