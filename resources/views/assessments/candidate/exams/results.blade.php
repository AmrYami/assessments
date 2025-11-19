@extends('dashboard.mt.main', ['page' => $page])

@section('content')
    @php($questions = $questions ?? collect())
    <div class="card">
        <div class="card-header"><h3 class="card-title">My Results</h3></div>
        <div class="card-body p-0">
            <table class="table table-striped mb-0">
                <thead>
                <tr>
                    <th>Exam</th>
                    <th>Score</th>
                    <th>Percent</th>
                    <th>Status</th>
                    <th>Review</th>
                    <th>Explanations</th>
                    <th>Started</th>
                </tr>
                </thead>
                <tbody>
                @foreach($attempts as $a)
                    @php
                        $exam = $exams[$a->exam_id] ?? null;
                        $details = $a->result_json['details'] ?? [];
                        $showExplanations = $exam && ($exam->show_explanations ?? false);
                        $detailsWithExplanations = $showExplanations
                            ? collect($details)->filter(fn($detail) => !empty($detail['explanation']))
                            : collect();
                    @endphp
                    <tr>
                        <td>{{ $exams[$a->exam_id]->title ?? ('#'.$a->exam_id) }}</td>
                        <td>{{ $a->total_score }}</td>
                        <td>{{ $a->percent }}%</td>
                        <td>{{ $a->passed ? 'Passed' : 'Failed' }}</td>
                        <td>
                            @if(in_array($a->review_status, ['pending','in_review']))
                                <span class="badge bg-warning text-dark">{{ ucfirst($a->review_status) }}</span>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td>
                            @if($detailsWithExplanations->isNotEmpty())
                                <details>
                                    <summary class="text-primary">View explanations</summary>
                                    <ul class="mb-0 small mt-2">
                                        @foreach($detailsWithExplanations as $detail)
                                            @php($question = $questions->get($detail['question_id'] ?? null))
                                            <li class="mb-2">
                                                <strong>{{ $question ? \Illuminate\Support\Str::limit(strip_tags($question->text), 80) : 'Question #'.$detail['question_id'] }}</strong>
                                                <div>{{ $detail['explanation'] }}</div>
                                            </li>
                                        @endforeach
                                    </ul>
                                </details>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td>{{ optional($a->started_at)->format('Y-m-d H:i') }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        <div class="card-footer">{{ $attempts->links() }}</div>
    </div>
@endsection
