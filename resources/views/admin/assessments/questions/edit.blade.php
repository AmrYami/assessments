@extends('dashboard.mt.main', ['page' => $page])

@section('content')
    @include('assessments::admin.assessments.partials.alerts')
    <form method="post" action="{{ route('dashboard.assessments.questions.update', $question) }}" class="card" id="question-form">
        @csrf
        @method('put')
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <span class="badge bg-light text-dark">Family Q-{{ $question->origin_id ?: $question->id }} — v{{ $question->version }}</span>
                @if(config('assessments.diff_and_history'))
                    <a class="btn btn-sm btn-outline-secondary" href="{{ route('dashboard.assessments.questions.history', $question) }}">History</a>
                @endif
            </div>
            <div class="row mb-3">
                <div class="col-md-4">
                    <label class="form-label">Slug</label>
                    <input name="slug" class="form-control" value="{{ old('slug', $question->slug) }}" required>
                </div>
                <div class="col-md-8">
                    <label class="form-label">Question Text</label>
                    <textarea name="text" class="form-control" rows="3" required>{{ old('text', $question->text) }}</textarea>
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-3">
                    <label class="form-label">Response Type</label>
                    <select name="response_type" id="response-type" class="form-select" required>
                        @foreach(['single_choice' => 'Single choice', 'multiple_choice' => 'Multiple choice', 'text' => 'Text', 'textarea' => 'Textarea'] as $value => $label)
                            <option value="{{ $value }}" @selected(old('response_type', $question->response_type)===$value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Weight</label>
                    <input name="weight" type="number" min="1" value="{{ old('weight', $question->weight) }}" class="form-control" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Difficulty</label>
                    <select name="difficulty" class="form-select" required>
                        @foreach(['easy','medium','hard','very_hard'] as $d)
                            <option value="{{ $d }}" @selected(old('difficulty', $question->difficulty)===$d)>{{ ucfirst(str_replace('_',' ', $d)) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 form-check form-switch d-flex align-items-end">
                    <input class="form-check-input" type="checkbox" name="is_active" value="1" {{ old('is_active', $question->is_active) ? 'checked' : '' }}>
                    <label class="form-check-label ms-2">Active</label>
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label">Explanation (optional)</label>
                <textarea name="explanation" class="form-control" rows="2">{{ old('explanation', $question->explanation) }}</textarea>
                <div class="form-text">Shown to candidates after submission when the exam enables explanations.</div>
            </div>

            <div class="mb-3">
                <label class="form-label">Topics</label>
                <select name="topics[]" class="form-select" multiple>
                    @foreach($topics as $t)
                        <option value="{{ $t->id }}" {{ collect(old('topics', $question->topics->pluck('id')))->contains($t->id) ? 'selected' : '' }}>{{ $t->name }}</option>
                    @endforeach
                </select>
            </div>

            <hr>
            <h5 class="mb-3">Note</h5>
            <div class="row mb-3">
                <div class="col-md-3 form-check form-switch">
                    <input class="form-check-input" type="checkbox" name="note_enabled" value="1" {{ old('note_enabled', $question->note_enabled) ? 'checked' : '' }}>
                    <label class="form-check-label ms-2">Enable note</label>
                </div>
                <div class="col-md-3 form-check form-switch">
                    <input class="form-check-input" type="checkbox" name="note_required" value="1" {{ old('note_required', $question->note_required) ? 'checked' : '' }}>
                    <label class="form-check-label ms-2">Note required</label>
                </div>
                <div class="col-md-6">
                    <input class="form-control" name="note_hint" placeholder="Optional hint text" value="{{ old('note_hint', $question->note_hint) }}">
                </div>
            </div>

            <div id="choice-section" class="response-section">
                <hr>
                <div class="d-flex align-items-center mb-2">
                    <h5 class="mb-0">Answer Items</h5>
                    <span class="text-muted ms-2">Edit choices or overrides; mark correct ones.</span>
                </div>
                <div class="row g-3 align-items-end mb-2">
                    <div class="col-md-4">
                        <label class="form-label">Answer Set</label>
                        <select id="answer-set-select" class="form-select">
                            <option value="">Create new set</option>
                        </select>
                        <input type="hidden" name="answer_set_id" id="answer-set-id" value="{{ old('answer_set_id', $currentAnswerSetId) }}">
                    </div>
                    <div class="col-md-2">
                        <button type="button" class="btn btn-outline-secondary" id="refresh-answer-sets">Refresh</button>
                    </div>
                    <div class="col-md-3">
                        <button type="button" class="btn btn-link" id="clear-answer-set" style="display:none;">Clear selection</button>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-sm align-middle" id="choices-table">
                        <thead>
                        <tr>
                            <th style="width: 35%">Label</th>
                            <th style="width: 20%">Value</th>
                            <th style="width: 10%" class="text-center">Active</th>
                            <th style="width: 10%" class="text-center">Correct</th>
                            <th style="width: 15%">Override label</th>
                            <th style="width: 10%"></th>
                        </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
                <div id="max-choices-wrapper" class="mt-3 d-none">
                    <label class="form-label" for="max-choices-input">Max choices</label>
                    <input type="number" class="form-control" name="max_choices" id="max-choices-input" min="1" placeholder="Leave blank for unlimited" value="{{ old('max_choices', $question->max_choices) }}">
                    <div class="form-text">Candidates can select up to this number of options.</div>
                    <div class="invalid-feedback d-none" id="max-choices-error"></div>
                </div>
                <button type="button" class="btn btn-light" id="add-choice">Add item</button>
            </div>

            <div id="text-section" class="response-section" style="display:none">
                <hr>
                <div class="d-flex align-items-center mb-2">
                    <h5 class="mb-0">Response Parts</h5>
                    <span class="text-muted ms-2">Define the fields candidates must fill.</span>
                </div>
                <div class="table-responsive">
                    <table class="table table-sm align-middle" id="parts-table">
                        <thead>
                        <tr>
                            <th style="width: 20%">Key</th>
                            <th style="width: 25%">Label</th>
                            <th style="width: 15%">Input type</th>
                            <th style="width: 10%" class="text-center">Required</th>
                            <th style="width: 15%">Validation</th>
                            <th style="width: 15%">Value</th>
                            <th style="width: 10%"></th>
                        </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
                <button type="button" class="btn btn-light" id="add-part">Add response part</button>
            </div>
        </div>
        <div class="card-footer d-flex justify-content-end">
            <button class="btn btn-primary">Update</button>
        </div>
    </form>

    <template id="choice-row-template">
        <tr>
            <td>
                <input type="hidden" name="__NAME__[answer_set_item_id]" value="">
                <input type="text" class="form-control" name="__NAME__[label]" required>
            </td>
            <td>
                <input type="text" class="form-control" name="__NAME__[value]">
                <input type="hidden" name="__NAME__[value_override]" value="">
            </td>
            <td class="text-center"><input type="checkbox" class="form-check-input" name="__NAME__[is_active]" value="1" checked></td>
            <td class="text-center"><input type="checkbox" class="form-check-input" name="__NAME__[is_correct]" value="1"></td>
            <td><input type="text" class="form-control" name="__NAME__[label_override]"></td>
            <td class="text-end"><button type="button" class="btn btn-link text-danger p-0 remove-row">Remove</button></td>
        </tr>
    </template>

    <template id="part-row-template">
        <tr>
            <td><input type="text" class="form-control" name="__NAME__[key]" required></td>
            <td><input type="text" class="form-control" name="__NAME__[label]" required></td>
            <td>
                <select class="form-select" name="__NAME__[input_type]">
                    <option value="text">Text</option>
                    <option value="textarea">Textarea</option>
                </select>
            </td>
            <td class="text-center"><input type="checkbox" class="form-check-input" name="__NAME__[required]" value="1"></td>
            <td>
                <select class="form-select" name="__NAME__[validation_mode]">
                    <option value="none">None</option>
                    <option value="exact">Exact match</option>
                    <option value="regex">Regex</option>
                </select>
            </td>
            <td><input type="text" class="form-control" name="__NAME__[validation_value]"></td>
            <td class="text-end"><button type="button" class="btn btn-link text-danger p-0 remove-row">Remove</button></td>
        </tr>
    </template>

    @php
        $existingChoiceData = old('answer_links', $question->answerLinks->map(function ($link) {
            return [
                'answer_set_item_id' => $link->answer_set_item_id,
                'label' => optional($link->item)->label,
                'value' => optional($link->item)->value,
                'is_active' => (bool) $link->is_active,
                'is_correct' => (bool) $link->is_correct,
                'label_override' => $link->label_override,
                'value_override' => $link->value_override,
            ];
        })->values()->toArray());
        $existingParts = old('response_parts', $question->responseParts->map(function ($part) {
            return [
                'key' => $part->key,
                'label' => $part->label,
                'input_type' => $part->input_type,
                'required' => (bool) $part->required,
                'validation_mode' => $part->validation_mode,
                'validation_value' => $part->validation_value,
            ];
        })->values()->toArray());
    @endphp

    <script>
        const responseTypeField = document.getElementById('response-type');
        const choiceSection = document.getElementById('choice-section');
        const textSection = document.getElementById('text-section');
        const choiceTable = document.querySelector('#choices-table tbody');
        const partTable = document.querySelector('#parts-table tbody');
        const choiceTemplate = document.getElementById('choice-row-template').innerHTML.trim();
        const partTemplate = document.getElementById('part-row-template').innerHTML.trim();
        const maxChoicesWrap = document.getElementById('max-choices-wrapper');
        const maxChoicesInput = document.getElementById('max-choices-input');
        const maxChoicesError = document.getElementById('max-choices-error');
        const saveButton = document.querySelector('#question-form button[type="submit"]');
        const existingChoiceData = @json($existingChoiceData);
        const existingParts = @json($existingParts);

        function getActiveOptionCount() {
            return Array.from(choiceTable.querySelectorAll('tr')).reduce((count, row) => {
                const active = row.querySelector('input[name$="[is_active]"]');
                if (!active) return count;
                return count + (active.checked ? 1 : 0);
            }, 0);
        }

        function validateMaxChoices() {
            if (!maxChoicesWrap) return;
            const isMultiple = responseTypeField.value === 'multiple_choice';
            const raw = maxChoicesInput.value.trim();
            const value = raw ? parseInt(raw, 10) : NaN;
            const activeCount = getActiveOptionCount();
            let valid = true;
            let message = '';

            if (isMultiple && raw !== '') {
                if (Number.isNaN(value) || value < 1) {
                    valid = false;
                    message = 'Enter a value of at least 1.';
                } else if (value > activeCount) {
                    valid = false;
                    message = `Max choices cannot exceed active options (${activeCount}).`;
                }
            }

            if (!isMultiple) {
                maxChoicesInput.value = '';
            }

            if (valid) {
                maxChoicesError.classList.add('d-none');
                maxChoicesInput.classList.remove('is-invalid');
            } else {
                maxChoicesError.textContent = message;
                maxChoicesError.classList.remove('d-none');
                maxChoicesInput.classList.add('is-invalid');
            }

            if (saveButton) {
                saveButton.disabled = !valid;
            }
        }

        function updateMaxChoicesVisibility() {
            const isMultiple = responseTypeField.value === 'multiple_choice';
            maxChoicesWrap.classList.toggle('d-none', !isMultiple);
            if (!isMultiple) {
                maxChoicesInput.value = '';
            }
            validateMaxChoices();
        }

        function toggleSections() {
            const type = responseTypeField.value;
            const choiceTypes = ['single_choice', 'multiple_choice'];
            const isChoice = choiceTypes.includes(type);
            choiceSection.style.display = isChoice ? '' : 'none';
            textSection.style.display = isChoice ? 'none' : '';
            if (!isChoice && partTable.children.length === 0) {
                if (existingParts.length) {
                    existingParts.forEach((p) => addPartRow(p));
                } else {
                    addPartRow();
                }
            }
            if (isChoice && choiceTable.children.length === 0) {
                if (existingChoiceData.length) {
                    existingChoiceData.forEach((c) => addChoiceRow(c));
                } else {
                    addChoiceRow();
                    addChoiceRow();
                }
            }
            updateMaxChoicesVisibility();
        }

        function addChoiceRow(pref = {}) {
            const index = choiceTable.children.length;
            const rowHtml = choiceTemplate.replace(/__NAME__/g, `answer_links[${index}]`);
            const wrapper = document.createElement('tbody');
            wrapper.innerHTML = rowHtml;
            const row = wrapper.firstElementChild;
            const idField = row.querySelector(`[name="answer_links[${index}][answer_set_item_id]"]`);
            const labelField = row.querySelector(`[name="answer_links[${index}][label]"]`);
            const valueField = row.querySelector(`[name="answer_links[${index}][value]"]`);
            const valueOverrideField = row.querySelector(`[name="answer_links[${index}][value_override]"]`);
            const activeField = row.querySelector(`[name="answer_links[${index}][is_active]"]`);
            const correctField = row.querySelector(`[name="answer_links[${index}][is_correct]"]`);
            const labelOverrideField = row.querySelector(`[name="answer_links[${index}][label_override]"]`);

            idField.value = pref.answer_set_item_id || '';
            labelField.value = pref.label || '';
            valueField.value = pref.value || '';
            valueOverrideField.value = pref.value_override ?? valueField.value;
            if (pref.is_active === false) {
                activeField.checked = false;
            }
            if (pref.is_correct) {
                correctField.checked = true;
            }
            labelOverrideField.value = pref.label_override || '';

            valueField.addEventListener('input', () => {
                valueOverrideField.value = valueField.value;
            });
            if (activeField) {
                activeField.addEventListener('change', validateMaxChoices);
            }

            choiceTable.appendChild(row);
            bindRowEvents(row, 'choice');
            validateMaxChoices();
        }

        function addPartRow(pref = {}) {
            const index = partTable.children.length;
            const rowHtml = partTemplate.replace(/__NAME__/g, `response_parts[${index}]`);
            const wrapper = document.createElement('tbody');
            wrapper.innerHTML = rowHtml;
            const row = wrapper.firstElementChild;
            row.querySelector(`[name="response_parts[${index}][key]"]`).value = pref.key || '';
            row.querySelector(`[name="response_parts[${index}][label]"]`).value = pref.label || '';
            row.querySelector(`[name="response_parts[${index}][input_type]"]`).value = pref.input_type || 'text';
            if (pref.required) {
                row.querySelector(`[name="response_parts[${index}][required]"]`).checked = true;
            }
            row.querySelector(`[name="response_parts[${index}][validation_mode]"]`).value = pref.validation_mode || 'none';
            row.querySelector(`[name="response_parts[${index}][validation_value]"]`).value = pref.validation_value || '';
            partTable.appendChild(row);
            bindRowEvents(row, 'part');
        }

        function bindRowEvents(row, type) {
            const removeBtn = row.querySelector('.remove-row');
            removeBtn.addEventListener('click', () => {
                row.remove();
                renumberRows(type);
                if (type === 'choice') {
                    validateMaxChoices();
                }
            });
        }

        function renumberRows(type) {
            const container = type === 'choice' ? choiceTable : partTable;
            Array.from(container.children).forEach((row, idx) => {
                row.querySelectorAll('input, select').forEach((input) => {
                    input.name = input.name.replace(/^(answer_links|response_parts)\[\d+\]/, `$1[${idx}]`);
                });
            });
        }

        document.getElementById('add-choice').addEventListener('click', () => addChoiceRow());
        document.getElementById('add-part').addEventListener('click', () => addPartRow());
        responseTypeField.addEventListener('change', () => {
            choiceTable.innerHTML = '';
            partTable.innerHTML = '';
            toggleSections();
        });
        if (maxChoicesInput) {
            maxChoicesInput.addEventListener('input', validateMaxChoices);
        }

        toggleSections();
        validateMaxChoices();
    </script>
@endsection
