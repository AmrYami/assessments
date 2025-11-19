@extends('dashboard.mt.main', ['page' => $page])

@section('content')
    <form method="post" action="{{ route('dashboard.assessments.presets.update', $preset) }}" class="card">
        @csrf @method('put')
        <div class="card-body">
            <div class="row mb-3">
                <div class="col-md-4">
                    <label class="form-label">Slug</label>
                    <input class="form-control" value="{{ $preset->slug }}" disabled>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Label</label>
                    <input name="label" class="form-control" value="{{ $preset->label }}" required>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Input Type</label>
                    <select name="input_type" class="form-select">
                        <option value="text" @selected($preset->input_type==='text')>Text</option>
                        <option value="textarea" @selected($preset->input_type==='textarea')>Textarea</option>
                    </select>
                </div>
                <div class="col-md-2 form-check form-switch d-flex align-items-end">
                    <input class="form-check-input" type="checkbox" name="is_active" value="1" {{ $preset->is_active ? 'checked' : '' }}>
                    <label class="form-check-label ms-2">Active</label>
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label">Spec (JSON)</label>
                <textarea name="spec_json" class="form-control" rows="8">{{ json_encode($preset->spec_json, JSON_PRETTY_PRINT) }}</textarea>
            </div>
        </div>
        <div class="card-footer d-flex justify-content-between">
            <a href="{{ route('dashboard.assessments.presets.index') }}" class="btn btn-light">Back</a>
            <button class="btn btn-primary">Update</button>
        </div>
    </form>
@endsection

