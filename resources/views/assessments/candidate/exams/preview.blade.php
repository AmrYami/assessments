@extends('dashboard.mt.main', ['page' => $page])

@section('content')
    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h3 class="card-title">Preview: {{ $exam->title }}</h3>
            <form id="start-form" onsubmit="return startAttempt(event)">
                <input type="hidden" name="seed" id="seed" value="{{ $seed }}">
                <button class="btn btn-success">Start</button>
            </form>
        </div>
        <div class="card-body">
            <div class="mb-3">
                <div><b>Mode:</b> {{ $exam->assembly_mode }}</div>
                <div><b>Pass:</b> {{ $exam->pass_type }} {{ $exam->pass_value }}</div>
                <div><b>Max Attempts:</b> {{ $exam->max_attempts }}</div>
                <div><b>Time Limit:</b> {{ $exam->time_limit_seconds ? $exam->time_limit_seconds.'s' : 'None' }}</div>
            </div>
            <p class="text-muted">Refresh to preview a different valid sample (dynamic exams).</p>
            @foreach($questions as $i => $q)
                @php
                    $typeLabel = match($q->response_type) {
                        'single_choice' => 'Single choice',
                        'multiple_choice' => 'Multiple choice',
                        'textarea' => 'Textarea',
                        default => 'Text',
                    };
                    $options = $q->options->where('is_active', true)->sortBy('position');
                    $parts = $q->responseParts->sortBy('position');
                @endphp
                <div class="mb-4">
                    <div class="fw-bold">
                        Q{{ $i+1 }}. {{ $q->text }}
                        <span class="text-muted">(w={{ $q->weight }}, {{ $typeLabel }})</span>
                    </div>
                    @if($q->note_enabled)
                        <div class="text-muted small">
                            Note {{ $q->note_required ? 'required' : 'optional' }}@if($q->note_hint) — {{ $q->note_hint }}@endif
                        </div>
                    @endif
                    @if(in_array($q->response_type, ['single_choice','multiple_choice']) && $options->count())
                        <ul class="mb-0 mt-2">
                            @foreach($options as $opt)
                                <li>{{ $opt->label }}</li>
                            @endforeach
                        </ul>
                        @if($q->response_type === 'multiple_choice' && $q->max_choices)
                            <div class="text-muted small mt-1">Select up to {{ $q->max_choices }} options.</div>
                        @endif
                    @else
                        <ul class="mb-0 mt-2">
                            @forelse($parts as $part)
                                <li>
                                    <strong>{{ $part->label }}</strong>
                                    ({{ $part->input_type }}, {{ $part->required ? 'required' : 'optional' }})
                                    @if($part->validation_mode !== 'none')
                                        — {{ $part->validation_mode === 'exact' ? 'Must equal' : 'Regex' }}: {{ $part->validation_value }}
                                    @endif
                                </li>
                            @empty
                                <li><em>Free-form response</em></li>
                            @endforelse
                        </ul>
                    @endif
                </div>
            @endforeach
        </div>
    </div>

    <div id="attempt-container" style="display:none"></div>

    <script>
        let hbTimer = null, attemptId = null, expiresAt = null;
        const RESPONSE_TYPE_LABELS = {
            single_choice: 'Single choice',
            multiple_choice: 'Multiple choice',
            text: 'Text',
            textarea: 'Textarea'
        };

        async function startAttempt(e){
            e.preventDefault();
            const seed = document.getElementById('seed').value;
            const res = await fetch('/api/exams/{{ $exam->id }}/attempts', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                body: JSON.stringify({ seed: parseInt(seed) })
            });
            if(!res.ok){
                alert('Cannot start attempt');
                return false;
            }
            const data = await res.json();
            renderAttempt(data);
            return false;
        }

        function renderAttempt(data){
            const c = document.getElementById('attempt-container');
            c.style.display='';
            let html = `<div class="card"><div class="card-header"><b>Attempt</b> (expires: ${data.expires_at || 'N/A'})</div><div class="card-body">`;
            data.questions.forEach((q, idx) => {
                const typeLabel = RESPONSE_TYPE_LABELS[q.response_type] || q.response_type;
                const maxChoicesAttr = q.max_choices ? ` data-max-choices="${q.max_choices}"` : '';
                html += `<div class="mb-4 question-block" data-qid="${q.id}" data-response-type="${q.response_type}"${maxChoicesAttr}>`;
                html += `<div class="fw-bold">Q${idx+1}. ${q.text} <span class="text-muted">(w=${q.weight}, ${typeLabel})</span></div>`;

                if (['single_choice', 'multiple_choice'].includes(q.response_type)) {
                    const options = Array.isArray(q.options) ? q.options : [];
                    const maxChoices = q.response_type === 'multiple_choice' && q.max_choices ? parseInt(q.max_choices, 10) : null;
                    if (!options.length) {
                        html += `<div class="text-muted small mt-2">No options configured.</div>`;
                    }
                    options.forEach(opt => {
                        const inputType = q.response_type === 'single_choice' ? 'radio' : 'checkbox';
                        const name = `q_${q.id}${inputType === 'checkbox' ? '[]' : ''}`;
                        const inputId = `opt_${q.id}_${opt.id}`;
                        html += `
                            <div class="form-check mt-2">
                                <input class="form-check-input" data-option-id="${opt.id}" type="${inputType}" name="${name}" id="${inputId}" onchange="handleChoiceChange(${data.attempt_id}, ${q.id}, this)">
                                <label class="form-check-label" for="${inputId}">${opt.label}</label>
                            </div>`;
                    });
                    if (maxChoices) {
                        html += `<div class="form-text text-muted mt-2 max-choice-hint">Select up to ${maxChoices} options.</div>`;
                        html += `<div class="text-danger small d-none max-choice-warning">You've reached the maximum of ${maxChoices}.</div>`;
                    }
                } else {
                    const parts = Array.isArray(q.response_parts) && q.response_parts.length
                        ? q.response_parts
                        : [{ key: 'text', label: 'Response', input_type: q.response_type, required: false }];
                    parts.forEach(part => {
                        const inputId = `part_${q.id}_${part.key}`;
                        const isTextarea = (part.input_type || q.response_type) === 'textarea';
                        const requiredAttr = part.required ? 'required' : '';
                        const infoBits = [];
                        if (part.required) infoBits.push('Required');
                        if (part.validation_mode && part.validation_mode !== 'none') {
                            infoBits.push(`${part.validation_mode === 'exact' ? 'Must equal' : 'Regex'}: ${part.validation_value}`);
                        }
                        const hint = infoBits.length ? `<div class="form-text text-muted">${infoBits.join(' · ')}</div>` : '';
                        if (isTextarea) {
                            html += `
                                <div class="mt-3">
                                    <label class="form-label" for="${inputId}">${part.label}</label>
                                    <textarea class="form-control" data-part-key="${part.key}" id="${inputId}" rows="3" ${requiredAttr} onblur="saveAnswer(${data.attempt_id}, ${q.id})"></textarea>
                                    ${hint}
                                </div>`;
                        } else {
                            html += `
                                <div class="mt-3">
                                    <label class="form-label" for="${inputId}">${part.label}</label>
                                    <input class="form-control" data-part-key="${part.key}" id="${inputId}" type="text" ${requiredAttr} onblur="saveAnswer(${data.attempt_id}, ${q.id})">
                                    ${hint}
                                </div>`;
                        }
                    });
                }

                if (q.note_enabled) {
                    const noteId = `note_${q.id}`;
                    const noteReq = q.note_required ? 'required' : '';
                    const hint = q.note_hint ? q.note_hint : 'Add a note';
                    html += `
                        <div class="mt-3">
                            <label class="form-label" for="${noteId}">Note ${q.note_required ? '(required)' : '(optional)'}</label>
                            <textarea class="form-control question-note" id="${noteId}" rows="2" placeholder="${hint}" ${noteReq} onblur="saveAnswer(${data.attempt_id}, ${q.id})"></textarea>
                        </div>`;
                }

                html += `</div>`;
            });
            html += `<button class="btn btn-primary" onclick="submitAttempt(${data.attempt_id})">Submit</button>`;
            html += `</div></div>`;
            c.innerHTML = html;
            document.querySelectorAll('.question-block').forEach(block => enforceChoiceLimit(block));
            attemptId = data.attempt_id; expiresAt = data.expires_at;
            if (hbTimer) clearInterval(hbTimer);
            hbTimer = setInterval(heartbeat, 15000);
        }

        function enforceChoiceLimit(block) {
            if (!block) return;
            const max = parseInt(block.dataset.maxChoices || '', 10);
            const warning = block.querySelector('.max-choice-warning');
            const inputs = Array.from(block.querySelectorAll('input[data-option-id]'));
            if (!max || Number.isNaN(max)) {
                inputs.forEach(inp => inp.disabled = false);
                if (warning) warning.classList.add('d-none');
                return;
            }
            const selected = inputs.filter(inp => inp.checked);
            const limitReached = selected.length >= max;
            inputs.forEach(inp => {
                if (inp.checked) {
                    inp.disabled = false;
                } else {
                    inp.disabled = limitReached;
                }
            });
            if (warning) {
                warning.classList.toggle('d-none', !limitReached);
            }
        }

        function handleChoiceChange(attemptId, qid, input) {
            const block = input.closest('.question-block');
            const max = block ? parseInt(block.dataset.maxChoices || '', 10) : NaN;
            if (block && max && !Number.isNaN(max)) {
                const selected = Array.from(block.querySelectorAll('input[data-option-id]:checked'));
                if (selected.length > max) {
                    input.checked = false;
                    enforceChoiceLimit(block);
                    const warning = block.querySelector('.max-choice-warning');
                    if (warning) warning.classList.remove('d-none');
                    return;
                }
            }
            if (block) {
                enforceChoiceLimit(block);
            }
            saveAnswer(attemptId, qid);
        }

        function collectAnswerPayload(qid) {
            const block = document.querySelector(`.question-block[data-qid="${qid}"]`);
            if (!block) return null;
            const type = block.dataset.responseType;
            const payload = { question_id: qid };

            if (type === 'single_choice' || type === 'multiple_choice') {
                const selected = [];
                block.querySelectorAll('input[data-option-id]').forEach(input => {
                    if (input.checked) selected.push(parseInt(input.dataset.optionId, 10));
                });
                payload.option_ids = selected;
            } else {
                const parts = [];
                block.querySelectorAll('[data-part-key]').forEach(input => {
                    parts.push({
                        key: input.dataset.partKey,
                        text: input.value ?? ''
                    });
                });
                payload.parts = parts;
                if (parts.length === 1) {
                    payload.text = parts[0].text ?? '';
                }
            }

            const noteEl = block.querySelector('.question-note');
            if (noteEl) {
                payload.note = noteEl.value ?? '';
            }

            return payload;
        }

        async function saveAnswer(attemptId, qid){
            const payload = collectAnswerPayload(qid);
            if (!payload) return;
            try {
                const res = await fetch(`/api/attempts/${attemptId}/answers`, {
                    method: 'PATCH',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                    body: JSON.stringify({ answers: [payload] })
                });
                if (!res.ok) {
                    const data = await res.json().catch(() => ({}));
                    const errMsg = (data.errors && data.errors.option_ids && data.errors.option_ids[0])
                        || data.message
                        || 'Failed to save answer.';
                    throw new Error(errMsg);
                }
            } catch (err) {
                console.error(err);
                alert(err.message || 'Could not save answer. Please retry.');
            }
        }

        async function submitAttempt(attemptId){
            const res = await fetch(`/api/attempts/${attemptId}/submit`, { method:'POST', headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }});
            if(!res.ok){
                const data = await res.json().catch(() => ({}));
                const errMsg = (data.errors && data.errors.option_ids && data.errors.option_ids[0])
                    || data.message
                    || 'Submit failed';
                alert(errMsg);
                return;
            }
            const data = await res.json();
            alert(`Score: ${data.score} (${data.percent}%) — ${data.passed ? 'Passed' : 'Failed'}`);
        }

        async function heartbeat(){
            if (!attemptId) return;
            const res = await fetch(`/api/attempts/${attemptId}/heartbeat`);
            if(!res.ok) return;
            const data = await res.json();
            if (data.status!=='in_progress') { clearInterval(hbTimer); return; }
            if (data.expires_at && (new Date(data.server_now) >= new Date(data.expires_at))) {
                clearInterval(hbTimer);
                await submitAttempt(attemptId);
            }
        }
    </script>
@endsection
