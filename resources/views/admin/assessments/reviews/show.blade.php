@extends('dashboard.mt.main', ['page' => $page])

@section('content')
    <form method="post" action="{{ route('dashboard.assessments.reviews.update', $attempt) }}" class="card">@csrf @method('patch')
        <div class="card-header d-flex justify-content-between align-items-center">
            <div>Attempt #{{ $attempt->id }} — Review</div>
            <div><span class="badge bg-warning text-dark">{{ ucfirst($attempt->review_status) }}</span></div>
        </div>
        <div class="card-body">
    @php $manualIndex = 0; @endphp
    @foreach(($attempt->frozen_question_ids ?? []) as $qid)
        @php $question = $questions[$qid] ?? null; @endphp
        @if(!$question)
            @continue
        @endif
                @php
                    $answer = $answers[$qid] ?? null;
                    $selected = collect(json_decode(optional($answer)->option_ids ?? '[]', true))->map(fn($v) => (int) $v)->all();
                    $noteText = optional($answer)->note_text;
                    $parts = $partsMap[$qid] ?? collect();
                    $textRows = ($textAnswers[$qid] ?? collect())->keyBy('part_key');
                    $correctOptions = $question->answerKeys->pluck('option_id')->map(fn($v) => (int) $v)->all();
                    $isChoice = in_array($question->response_type, ['single_choice','multiple_choice']);
                    if ($question->response_type === 'single_choice') {
                        $typeLabel = 'Single choice';
                    } elseif ($question->response_type === 'multiple_choice') {
                        $typeLabel = 'Multiple choice';
                    } elseif ($question->response_type === 'textarea') {
                        $typeLabel = 'Textarea';
                    } else {
                        $typeLabel = 'Text';
                    }
                @endphp
                <div class="mb-3 p-3 border rounded">
                    <div class="fw-bold">Q#{{ $qid }} (w={{ $question->weight }}) — {{ \Illuminate\Support\Str::limit($question->text, 140) }}</div>
                    <div class="text-muted small">Type: {{ $typeLabel }}</div>

                    @if($isChoice)
                        <div class="mt-2">
                            <ul class="mb-0">
                                @foreach($question->options as $opt)
                                    @php
                                        $isSelected = in_array($opt->id, $selected, true);
                                        $isCorrect = in_array($opt->id, $correctOptions, true);
                                    @endphp
                                    <li>
                                        {{ $opt->label }}
                                        @if($isCorrect)
                                            <span class="badge bg-success ms-1">Correct</span>
                                        @endif
                                        @if($isSelected)
                                            <span class="badge bg-primary ms-1">Selected</span>
                                        @endif
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @else
                        <div class="mt-3">
                            <div class="table-responsive">
                                <table class="table table-sm align-middle mb-2">
                                    <thead>
                                    <tr>
                                        <th style="width: 30%">Part</th>
                                        <th>Candidate response</th>
                                        <th style="width: 25%">Validation</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    @foreach($parts as $part)
                                    @php $value = optional($textRows->get($part->key))->text_value; @endphp
                                        <tr>
                                            <td>
                                                {{ $part->label }}
                                                @if($part->required)
                                                    <span class="badge bg-secondary ms-1">Required</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if($value !== null && $value !== '')
                                                    <div class="bg-light rounded p-2">{!! nl2br(e($value)) !!}</div>
                                                @else
                                                    <span class="text-muted">—</span>
                                                @endif
                                            </td>
                                            <td class="text-muted small">
                                                @if($part->validation_mode === 'exact')
                                                    Must equal “{{ $part->validation_value }}”
                                                @elseif($part->validation_mode === 'regex')
                                                    Regex: {{ $part->validation_value }}
                                                @else
                                                    —
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                    </tbody>
                                </table>
                            </div>
                            <div class="row g-2 align-items-end">
                                <div class="col-md-3 col-lg-2">
                                    <label class="form-label">Awarded score</label>
                                    <input type="number" class="form-control" name="items[{{ $manualIndex }}][awarded_score]"
                                           value="{{ old("items.$manualIndex.awarded_score", (int)($answer->awarded_score ?? 0)) }}"
                                           min="0" max="{{ (int) $question->weight }}">
                                    <input type="hidden" name="items[{{ $manualIndex }}][question_id]" value="{{ $qid }}">
                                </div>
                                <div class="col text-muted small">Max {{ (int) $question->weight }} points</div>
                            </div>
                            @php $manualIndex++; @endphp
                        </div>
                    @endif

            @php $noteFallback = optional($textRows->get('__note__'))->text_value; @endphp
            @php $displayNote = $noteText ?? $noteFallback; @endphp
                    @if($question->note_enabled)
                        <div class="mt-3">
                            <div class="small text-muted">Note {{ $question->note_required ? '(required)' : '(optional)' }}@if($question->note_hint) — {{ $question->note_hint }}@endif</div>
                            @if($displayNote)
                                <div class="bg-light rounded p-2 mt-1">{!! nl2br(e($displayNote)) !!}</div>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </div>
                    @elseif($displayNote)
                        <div class="mt-3">
                            <div class="small text-muted">Note</div>
                            <div class="bg-light rounded p-2 mt-1">{!! nl2br(e($displayNote)) !!}</div>
                        </div>
                    @endif
                </div>
            @endforeach

            @if($manualIndex === 0)
                <p class="text-muted">This attempt contains no manual scoring items.</p>
            @endif

            <div class="mb-3">
                <label class="form-label">Reviewer notes</label>
                <textarea name="review_notes" class="form-control" rows="3">{{ old('review_notes', $attempt->review_notes) }}</textarea>
            </div>
        </div>
        <div class="card-footer d-flex justify-content-between">
            <div><button class="btn btn-light" name="finalize" value="0">Save &amp; Continue</button></div>
            <div><button class="btn btn-primary" name="finalize" value="1">Finalize Attempt</button></div>
        </div>
    </form>
@endsection
