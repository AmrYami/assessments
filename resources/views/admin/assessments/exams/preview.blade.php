@extends('dashboard.mt.main', ['page' => $page])

@section('content')
    <div class="card">
        <div class="card-header"><h3 class="card-title">Preview: {{ $exam->title }}</h3></div>
        <div class="card-body">
            @foreach($exam->questions as $i => $q)
                <div class="mb-4">
                    <div class="fw-bold">Q{{ $i+1 }}. {{ $q->text }} <span class="text-muted">(w={{ $q->weight }}, {{ $q->selection_mode }})</span></div>
                    <ul class="mb-0">
                        @foreach($q->options as $opt)
                            <li>{{ $opt->label }}</li>
                        @endforeach
                    </ul>
                </div>
            @endforeach
        </div>
    </div>
@endsection

