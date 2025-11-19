@extends('dashboard.mt.main', ['page' => $page])

@section('content')
    @if(isset($requirement))
        <div class="alert {{ $requirement->status==='passed' ? 'alert-success' : ($requirement->status==='failed' ? 'alert-danger' : 'alert-warning') }}">
            @if($requirement->status==='passed')
                Entrance Exam passed. You now have full access.
            @elseif($requirement->status==='failed')
                <div><strong>Entrance Exam Failed.</strong>
                {!! nl2br(e(optional($failCategory ?? null)->fail_message)) !!}</div>
                <div class="mt-1">Attempts used: {{ $requirement->attempts_used }}{{ $requirement->max_attempts ? ' / '.$requirement->max_attempts : '' }}</div>
            @else
                <strong>Entrance Exam Required:</strong> {{ $entranceExam?->title }}. You must pass this exam to continue.
            @endif
        </div>
    @endif
    <div class="card">
        <div class="card-header"><h3 class="card-title">Available Exams</h3></div>
        <div class="card-body p-0">
            <table class="table table-striped mb-0">
                <thead>
                <tr>
                    <th>Title</th>
                    <th>Mode</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                @foreach($items as $exam)
                    <tr>
                        <td>{{ $exam->title }}</td>
                        <td>{{ $exam->assembly_mode }}</td>
                        <td class="text-end">
                            <a class="btn btn-sm btn-primary" href="{{ route('assessments.candidate.exams.preview', $exam) }}">Preview</a>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        <div class="card-footer"></div>
    </div>
@endsection
