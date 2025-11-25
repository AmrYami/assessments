@extends('dashboard.mt.main', ['page' => $page])

@section('content')
    @include('assessments::admin.assessments.partials.alerts')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3 class="mb-0">Exams</h3>
        <a href="{{ route('dashboard.assessments.exams.create') }}" class="btn btn-primary">New Exam</a>
    </div>
    <div class="card">
        <div class="card-body p-0">
            <table class="table table-striped mb-0">
                <thead>
                <tr>
                    <th>ID</th>
                    <th>Title</th>
                    <th>Mode</th>
                    <th>Status</th>
                    <th style="width: 220px"></th>
                </tr>
                </thead>
                <tbody>
                @foreach($items as $e)
                    <tr>
                        <td>{{ $e->id }}</td>
                        <td>{{ $e->title }}</td>
                        <td>{{ $e->assembly_mode }}</td>
                        <td>{{ $e->status ?? ($e->is_published ? 'published' : 'draft') }}</td>
                        <td class="text-end">
                            <a href="{{ route('dashboard.assessments.exams.edit', $e) }}" class="btn btn-sm btn-light">Edit</a>
                            <a href="{{ route('dashboard.assessments.exams.preview', $e) }}" class="btn btn-sm btn-secondary">Preview</a>
                            <form action="{{ route('dashboard.assessments.exams.publish', $e) }}" method="post" style="display:inline-block">@csrf
                                <button class="btn btn-sm btn-success">Publish</button>
                            </form>
                            <form action="{{ route('dashboard.assessments.exams.unpublish', $e) }}" method="post" style="display:inline-block">@csrf
                                <button class="btn btn-sm btn-warning">Unpublish</button>
                            </form>
                            <form action="{{ route('dashboard.assessments.exams.archive', $e) }}" method="post" style="display:inline-block">@csrf
                                <button class="btn btn-sm btn-danger">Archive</button>
                            </form>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        <div class="card-footer">{{ $items->links() }}</div>
    </div>
@endsection
