@extends('dashboard.mt.main', ['page' => $page])

@section('content')
    @include('admin.assessments.partials.alerts')
    <form method="post" action="{{ route('dashboard.assessments.topics.store') }}" class="card">
        @csrf
        <div class="card-body">
            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label">Name</label>
                    <input name="name" class="form-control" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Slug</label>
                    <input name="slug" class="form-control" required>
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label">Description</label>
                <textarea name="description" class="form-control"></textarea>
            </div>
            <div class="row mb-3">
                <div class="col-md-3 form-check form-switch">
                    <input class="form-check-input" type="checkbox" name="is_active" value="1" checked>
                    <label class="form-check-label">Active</label>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Position</label>
                    <input name="position" class="form-control" type="number" value="0">
                </div>
            </div>
        </div>
        <div class="card-footer d-flex justify-content-end">
            <button class="btn btn-primary">Save</button>
        </div>
    </form>
@endsection

