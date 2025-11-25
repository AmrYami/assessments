@extends('dashboard.mt.main', ['page' => $page])

@section('content')
    @includeFirst(['assessments::admin.assessments.partials.alerts', 'admin.assessments.partials.alerts'])

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <div>
                <strong>Exam Pool Overview</strong>
                <span class="text-muted ms-2">ASSEMBLY_STRICT = {{ config('assessments.assembly.strict') ? 'true' : 'false' }}</span>
            </div>
            <div class="btn-group">
                <a href="{{ route('dashboard.assessments.reports.export') }}" class="btn btn-sm btn-outline-primary">Export CSV</a>
                <a href="{{ route('dashboard.assessments.reports.export_json') }}" class="btn btn-sm btn-outline-secondary">Export JSON</a>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-striped mb-0">
                <thead>
                    <tr>
                        <th>Exam</th>
                        <th>Mode</th>
                        <th>Target</th>
                        <th>Pool Size</th>
                        <th>Difficulty (count / score)</th>
                        <th>Attempts</th>
                        <th>Coverage</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($exams as $row)
                        @php($exam = $row['exam'])
                        @php($summary = $row['summary'])
                        <tr>
                            <td>
                                <strong>{{ $exam->title }}</strong><br>
                                <small class="text-muted">Slug: {{ $exam->slug }}</small>
                            </td>
                            <td>{{ ucfirst(str_replace('_',' ', $exam->assembly_mode)) }}</td>
                            <td>
                                @if($exam->assembly_mode === 'by_count')
                                    {{ $exam->question_count ?? '—' }} questions
                                @elseif($exam->assembly_mode === 'by_score')
                                    {{ $exam->target_total_score ?? '—' }} score
                                @else
                                    Manual ({{ $exam->questions()->count() }} ordered)
                                @endif
                            </td>
                            <td>{{ $summary['pool_size'] }} questions<br><small>{{ $summary['pool_score'] }} total score</small></td>
                            <td>
                                <ul class="list-unstyled mb-0 small">
                                    @foreach($summary['difficulty'] as $diff => $data)
                                        <li>{{ ucfirst(str_replace('_',' ', $diff)) }}: {{ $data['count'] }} / {{ $data['score'] }}</li>
                                    @endforeach
                                </ul>
                            </td>
                            <td class="small">
                                @php($metrics = $row['metrics'])
                                <div>Total: {{ $metrics['total_attempts'] }}</div>
                                <div>Pass Rate: {{ number_format($metrics['pass_rate'], 2) }}%</div>
                                <div>Avg %: {{ $metrics['average_percent'] }}%</div>
                            </td>
                            <td class="small">
                                @if($summary['coverage'])
                                    @php($coverage = $summary['coverage'])
                                    @if($coverage['actual'] >= $coverage['requested'])
                                        <span class="text-success">Achievable</span>
                                    @else
                                        <span class="text-danger">Not Achievable</span>
                                    @endif
                                    <div>{{ $coverage['actual'] }} / {{ $coverage['requested'] }}</div>
                                    @if(!empty($row['summary']['coverage']['tolerance']))
                                        <div class="text-warning">Tolerance applied (strict off)</div>
                                    @endif
                                    @if(!empty($summary['coverage']['hints']))
                                        <ul class="mb-0">
                                            @foreach($summary['coverage']['hints'] as $hint)
                                                <li>{{ $hint }}</li>
                                            @endforeach
                                        </ul>
                                    @endif
                                @else
                                    <span class="text-muted">Manual / Not applicable</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted">No exams found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
