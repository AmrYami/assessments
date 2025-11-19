@extends('dashboard.mt.main', ['page' => $page])

@section('content')
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <div>Exam Family: E-{{ $rootId }}</div>
        </div>
        <div class="table-responsive">
            <table class="table table-sm">
                <thead><tr><th>ID</th><th>Version</th><th>Assembly</th><th>Created</th><th>Placements</th><th>Scheduled</th><th>Actions</th></tr></thead>
                <tbody>
                @foreach($versions as $v)
                    <tr>
                        <td>{{ $v->id }}</td>
                        <td>{{ (int)($v->version_int ?? 1) }}</td>
                        <td>{{ $v->assembly_mode }}</td>
                        <td>{{ optional($v->created_at)->toDateTimeString() }}</td>
                        <td>{{ (int)($placements[$v->id]->cnt ?? 0) }}</td>
                        <td>{{ (int)($placements[$v->id]->scheduled ?? 0) }}</td>
                        <td>
                            <a class="btn btn-sm btn-light" href="{{ route('dashboard.assessments.exams.edit', $v) }}">Open</a>
                            @if(!$loop->first)
                                <a class="btn btn-sm btn-outline-secondary" href="{{ route('dashboard.assessments.exams.diff', ['left' => $versions[$loop->index-1]->id, 'right' => $v->id]) }}">Diff vs prev</a>
                            @endif
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endsection
