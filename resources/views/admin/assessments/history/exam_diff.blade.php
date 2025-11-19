@extends('dashboard.mt.main', ['page' => $page])

@section('content')
@if($left->schema_hash && $right->schema_hash && $left->schema_hash === $right->schema_hash)
  <div class="alert alert-info">No differences (schema hash match).</div>
@endif
<div class="row">
  <div class="col-md-6">
    <div class="card"><div class="card-header">Left — E#{{ $left->id }} (v{{ (int)($left->version_int ?? 1) }})</div>
      <div class="card-body">
        <div><b>Mode:</b> {{ $left->assembly_mode }}</div>
        <div><b>Target Score:</b> {{ $left->target_total_score ?? '-' }}</div>
        <div><b>Question Count:</b> {{ $left->question_count ?? '-' }}</div>
        <div><b>Time Limit:</b> {{ $left->time_limit_seconds ?? '-' }}</div>
        <div><b>Shuffle:</b> Q={{ $left->shuffle_questions ? 'yes' : 'no' }}, O={{ $left->shuffle_options ? 'yes' : 'no' }}</div>
      </div>
    </div>
  </div>
  <div class="col-md-6">
    <div class="card"><div class="card-header">Right — E#{{ $right->id }} (v{{ (int)($right->version_int ?? 1) }})</div>
      <div class="card-body">
        <div><b>Mode:</b> {{ $right->assembly_mode }}</div>
        <div><b>Target Score:</b> {{ $right->target_total_score ?? '-' }}</div>
        <div><b>Question Count:</b> {{ $right->question_count ?? '-' }}</div>
        <div><b>Time Limit:</b> {{ $right->time_limit_seconds ?? '-' }}</div>
        <div><b>Shuffle:</b> Q={{ $right->shuffle_questions ? 'yes' : 'no' }}, O={{ $right->shuffle_options ? 'yes' : 'no' }}</div>
      </div>
    </div>
  </div>
</div>
@endsection
