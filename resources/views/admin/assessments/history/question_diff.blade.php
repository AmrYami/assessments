@extends('dashboard.mt.main', ['page' => $page])

@section('content')
@if($left->schema_hash && $right->schema_hash && $left->schema_hash === $right->schema_hash)
  <div class="alert alert-info">No differences (schema hash match).</div>
@endif
<div class="row">
  <div class="col-md-6">
    <div class="card"><div class="card-header">Left — Q#{{ $left->id }} (v{{ (int)($left->version_int ?? 1) }})</div>
      <div class="card-body">
        <div><b>Mode:</b> {{ $left->selection_mode }}, <b>Weight:</b> {{ $left->weight }}, <b>Difficulty:</b> {{ $left->difficulty }}</div>
        <div><b>Note:</b> {{ $left->note_enabled ? ($left->note_required ? 'required' : 'enabled') : 'off' }}</div>
        <div class="mt-2"><b>Text:</b><br><pre class="mb-0" style="white-space:pre-wrap">{{ $left->text }}</pre></div>
      </div>
    </div>
  </div>
  <div class="col-md-6">
    <div class="card"><div class="card-header">Right — Q#{{ $right->id }} (v{{ (int)($right->version_int ?? 1) }})</div>
      <div class="card-body">
        <div><b>Mode:</b> {{ $right->selection_mode }}, <b>Weight:</b> {{ $right->weight }}, <b>Difficulty:</b> {{ $right->difficulty }}</div>
        <div><b>Note:</b> {{ $right->note_enabled ? ($right->note_required ? 'required' : 'enabled') : 'off' }}</div>
        <div class="mt-2"><b>Text:</b><br><pre class="mb-0" style="white-space:pre-wrap">{{ $right->text }}</pre></div>
      </div>
    </div>
  </div>
</div>

<div class="card mt-3"><div class="card-header">Options Diff</div>
  <div class="card-body">
    <div><b>Added:</b> @if(empty($diff['added'])) <span class="text-muted">none</span> @else {{ implode(', ', array_values($diff['added'])) }} @endif</div>
    <div><b>Removed:</b> @if(empty($diff['removed'])) <span class="text-muted">none</span> @else {{ implode(', ', array_values($diff['removed'])) }} @endif</div>
    <div class="mt-2"><b>Changed labels:</b>
      @if(empty($diff['changed'])) <span class="text-muted">none</span>
      @else
        <ul>@foreach($diff['changed'] as $k => $pair)<li>{{ $k }}: "{{ $pair[0] }}" → "{{ $pair[1] }}"</li>@endforeach</ul>
      @endif
    </div>
  </div>
</div>

<div class="card mt-3"><div class="card-header">Input Widgets Diff</div>
  <div class="card-body">
    <div><b>Added:</b> @if(empty($diff['inputsAdded'])) <span class="text-muted">none</span> @else {{ implode(', ', array_keys($diff['inputsAdded'])) }} @endif</div>
    <div><b>Removed:</b> @if(empty($diff['inputsRemoved'])) <span class="text-muted">none</span> @else {{ implode(', ', array_keys($diff['inputsRemoved'])) }} @endif</div>
    <div class="mt-2"><b>Changed params:</b>
      @if(empty($diff['inputsChanged'])) <span class="text-muted">none</span>
      @else
        <ul>@foreach($diff['inputsChanged'] as $slug => $pair)<li>{{ $slug }}: <code>{{ json_encode($pair[0]) }}</code> → <code>{{ json_encode($pair[1]) }}</code></li>@endforeach</ul>
      @endif
    </div>
  </div>
</div>
@endsection
