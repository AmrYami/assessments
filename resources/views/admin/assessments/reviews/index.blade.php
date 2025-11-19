@extends('dashboard.mt.main', ['page' => $page])

@section('content')
    <div class="card">
        <div class="card-header">Pending/In-Review Attempts</div>
        <div class="table-responsive">
            <table class="table table-sm">
                <thead><tr><th>ID</th><th>Exam</th><th>User</th><th>Status</th><th>Auto</th><th>Manual</th><th>Updated</th><th></th></tr></thead>
                <tbody>
                @foreach($items as $it)
                    <tr>
                        <td>{{ $it->id }}</td>
                        <td>{{ optional($exams[$it->exam_id] ?? null)->title }}</td>
                        <td>{{ $it->user_id }}</td>
                        <td><span class="badge bg-warning text-dark">{{ $it->review_status }}</span></td>
                        <td>{{ (int)($it->score_auto ?? 0) }}</td>
                        <td>{{ (int)($it->score_manual ?? 0) }}</td>
                        <td>{{ optional($it->updated_at)->diffForHumans() }}</td>
                        <td class="text-end"><a class="btn btn-sm btn-primary" href="{{ route('dashboard.assessments.reviews.show', $it) }}">Review</a></td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        <div class="card-footer">{{ $items->links() }}</div>
    </div>
@endsection
