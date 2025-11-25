@extends('dashboard.mt.main', ['page' => $page])

@section('content')
    @include('assessments::admin.assessments.partials.alerts')
    <form method="post" action="{{ route('dashboard.assessments.questions.store') }}" class="card" id="question-form">
        @csrf
        <div class="card-body">
            <div class="row mb-3">
                <div class="col-md-4">
                    <label class="form-label">Slug</label>
                    <input name="slug" class="form-control" value="{{ old('slug') }}" required>
                </div>
                <div class="col-md-8">
                    <label class="form-label">Question Text</label>
                    <textarea name="text" class="form-control" rows="3" required>{{ old('text') }}</textarea>
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-3">
                    <label class="form-label">Response Type</label>
                    <select name="response_type" id="response-type" class="form-select" required>
                        <option value="single_choice">Single choice</option>
                        <option value="multiple_choice">Multiple choice</option>
                        <option value="text">Text</option>
                        <option value="textarea">Textarea</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Weight</label>
                    <input name="weight" type="number" min="1" value="{{ old('weight', 1) }}" class="form-control" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Difficulty</label>
                    <select name="difficulty" class="form-select" required>
                        @foreach(['easy','medium','hard','very_hard'] as $d)
                            <option value="{{ $d }}" @selected(old('difficulty')===$d)>{{ ucfirst(str_replace('_',' ', $d)) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 form-check form-switch d-flex align-items-end">
                    <input class="form-check-input" type="checkbox" name="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }}>
                    <label class="form-check-label ms-2">Active</label>
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label">Explanation (optional)</label>
                <textarea name="explanation" class="form-control" rows="2">{{ old('explanation') }}</textarea>
                <div class="form-text">Shown to candidates after submission when the exam enables explanations.</div>
            </div>

            <div class="mb-3">
                <label class="form-label">Topics</label>
                <select name="topics[]" class="form-select" multiple>
                    @foreach($topics as $t)
                        <option value="{{ $t->id }}" @selected(collect(old('topics', []))->contains($t->id))>{{ $t->name }}</option>
                    @endforeach
                </select>
            </div>

            <hr>
            <h5 class="mb-3">Note</h5>
            <div class="row mb-3">
                <div class="col-md-3 form-check form-switch">
                    <input class="form-check-input" type="checkbox" name="note_enabled" value="1" {{ old('note_enabled') ? 'checked' : '' }}>
                    <label class="form-check-label ms-2">Enable note</label>
                </div>
                <div class="col-md-3 form-check form-switch">
                    <input class="form-check-input" type="checkbox" name="note_required" value="1" {{ old('note_required') ? 'checked' : '' }}>
                    <label class="form-check-label ms-2">Note required</label>
                </div>
                <div class="col-md-6">
                    <input class="form-control" name="note_hint" placeholder="Optional hint text" value="{{ old('note_hint') }}">
                </div>
            </div>

            <div id="choice-section" class="response-section">
                <hr>
                <div class="d-flex align-items-center mb-2">
                    <h5 class="mb-0">Answer Items</h5>
                    <span class="text-muted ms-2">Fill in choices; mark correct ones when applicable.</span>
                </div>
                <div class="row g-3 align-items-end mb-3">
                    <div class="col-md-4">
                        <label class="form-label">Answer Source</label>
                        <select id="answer-source" class="form-select">
                            <option value="new">Create new set</option>
                            <option value="answer_set">Use existing Answer Set</option>
                            <option value="question">Reuse from existing question</option>
                        </select>
                        <input type="hidden" name="answer_source" id="answer-source-input" value="{{ old('answer_source', old('answer_set_id') ? 'answer_set' : 'new') }}">
                    </div>
                    <div class="col-md-4" id="answer-set-tools" style="display:none;">
                        <label class="form-label">Answer Set</label>
                        <select id="answer-set-select" class="form-select">
                            <option value="">Create new set</option>
                        </select>
                        <input type="hidden" name="answer_set_id" id="answer-set-id" value="{{ old('answer_set_id') }}">
                    </div>
                    <div class="col-md-2" id="answer-set-actions" style="display:none;">
                        <label class="form-label d-block">&nbsp;</label>
                        <button type="button" class="btn btn-outline-secondary w-100" id="refresh-answer-sets">Refresh</button>
                    </div>
                    <div class="col-md-3" id="answer-set-clear" style="display:none;">
                        <label class="form-label d-block">&nbsp;</label>
                        <button type="button" class="btn btn-link" id="clear-answer-set">Clear selection</button>
                    </div>
                </div>

                <div class="border rounded p-3 mb-3 d-none" id="reuse-question-tools">
                    <div class="row g-3 align-items-end">
                        <div class="col-md-7">
                            <label class="form-label">Search questions</label>
                            <div class="input-group">
                                <input type="text" class="form-control" id="question-search-term" placeholder="Type text or slug">
                                <button type="button" class="btn btn-outline-secondary" id="search-questions">Search</button>
                            </div>
                        </div>
                        <div class="col-md-5">
                            <label class="form-label">Results</label>
                            <select id="question-search-results" class="form-select" size="5"></select>
                        </div>
                    </div>
                    <div class="mt-3 d-flex gap-2">
                        <button type="button" class="btn btn-outline-secondary btn-sm" id="load-question-answers" disabled>Load answers</button>
                        <button type="button" class="btn btn-link btn-sm" id="clear-question-selection" style="display:none;">Clear</button>
                    </div>
                    <div class="mt-3 d-none" id="question-answer-preview">
                        <div class="small text-muted mb-2" id="question-selected-hint"></div>
                        <div class="list-group" id="question-answer-options"></div>
                        <div class="mt-3">
                            <button type="button" class="btn btn-primary btn-sm" id="apply-question-answers">Import selected answers</button>
                        </div>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-sm align-middle" id="choices-table">
                        <thead>
                        <tr>
                            <th style="width: 35%">Label</th>
                            <th style="width: 20%">Value (optional)</th>
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
                    <input type="number" class="form-control" name="max_choices" id="max-choices-input" min="1" placeholder="Leave blank for unlimited" value="{{ old('max_choices') }}">
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
            <button class="btn btn-primary">Save</button>
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

    <script>
        const responseTypeField = document.getElementById('response-type');
        const choiceSection = document.getElementById('choice-section');
        const textSection = document.getElementById('text-section');
        const choiceTable = document.querySelector('#choices-table tbody');
        const partTable = document.querySelector('#parts-table tbody');
        const choiceTemplate = document.getElementById('choice-row-template').innerHTML.trim();
        const partTemplate = document.getElementById('part-row-template').innerHTML.trim();

        const answerSourceSelect = document.getElementById('answer-source');
        const answerSourceInput = document.getElementById('answer-source-input');
        const answerSetTools = document.getElementById('answer-set-tools');
        const answerSetActions = document.getElementById('answer-set-actions');
        const answerSetClearWrap = document.getElementById('answer-set-clear');
        const answerSetSelect = document.getElementById('answer-set-select');
        const answerSetIdInput = document.getElementById('answer-set-id');
        const refreshSetsBtn = document.getElementById('refresh-answer-sets');
        const clearSetBtn = document.getElementById('clear-answer-set');

        const reuseQuestionTools = document.getElementById('reuse-question-tools');
        const questionSearchTerm = document.getElementById('question-search-term');
        const questionSearchBtn = document.getElementById('search-questions');
        const questionResults = document.getElementById('question-search-results');
        const loadQuestionAnswersBtn = document.getElementById('load-question-answers');
        const clearQuestionSelectionBtn = document.getElementById('clear-question-selection');
        const questionAnswerPreview = document.getElementById('question-answer-preview');
        const questionAnswerList = document.getElementById('question-answer-options');
        const applyQuestionAnswersBtn = document.getElementById('apply-question-answers');
        const questionSelectedHint = document.getElementById('question-selected-hint');
        const maxChoicesWrap = document.getElementById('max-choices-wrapper');
        const maxChoicesInput = document.getElementById('max-choices-input');
        const maxChoicesError = document.getElementById('max-choices-error');
        const saveButton = document.querySelector('#question-form button[type="submit"]');

        const answerSetsCache = new Map();
        const existingChoices = @json(old('answer_links', []));
        const defaultAnswerSource = @json(old('answer_source', old('answer_set_id') ? 'answer_set' : 'new'));

        let currentQuestionData = null;
        applyQuestionAnswersBtn.disabled = true;

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
                addPartRow();
            }
            if (isChoice && choiceTable.children.length === 0) {
                if (existingChoices.length) {
                    existingChoices.forEach(choice => addChoiceRow(choice));
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

            if (pref.answer_set_item_id || pref.id) {
                idField.value = pref.answer_set_item_id || pref.id;
                labelField.value = pref.label || '';
                labelField.readOnly = true;
                labelField.classList.add('bg-light');
                valueField.value = pref.value ?? '';
                valueField.readOnly = true;
                valueField.classList.add('bg-light');
            } else {
                labelField.value = pref.label || '';
                valueField.value = pref.value ?? '';
            }

            valueOverrideField.value = pref.value_override ?? valueField.value;
            if (pref.is_active === false) activeField.checked = false;
            if (pref.is_correct) correctField.checked = true;
            if (pref.label_override) labelOverrideField.value = pref.label_override;

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
            if (pref.key) row.querySelector(`[name="response_parts[${index}][key]"]`).value = pref.key;
            if (pref.label) row.querySelector(`[name="response_parts[${index}][label]"]`).value = pref.label;
            if (pref.input_type) row.querySelector(`[name="response_parts[${index}][input_type]"]`).value = pref.input_type;
            if (pref.required) row.querySelector(`[name="response_parts[${index}][required]"]`).checked = true;
            if (pref.validation_mode) row.querySelector(`[name="response_parts[${index}][validation_mode]"]`).value = pref.validation_mode;
            if (pref.validation_value) row.querySelector(`[name="response_parts[${index}][validation_value]"]`).value = pref.validation_value;
            partTable.appendChild(row);
            bindRowEvents(row, 'part');
        }

        function bindRowEvents(row, type) {
            const removeBtn = row.querySelector('.remove-row');
            removeBtn.addEventListener('click', () => {
                row.remove();
                renumberRows(type);
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

        function updateAnswerSetHelpers() {
            const showSet = answerSourceSelect.value === 'answer_set';
            const hasSelection = Boolean(answerSetSelect.value);
            answerSetTools.style.display = showSet ? '' : 'none';
            answerSetActions.style.display = showSet ? '' : 'none';
            answerSetClearWrap.style.display = showSet && hasSelection ? '' : 'none';
            clearSetBtn.style.display = hasSelection ? '' : 'none';
        }

        function syncAnswerSourceUI() {
            const source = answerSourceSelect.value;
            answerSourceInput.value = source;

            if (source === 'answer_set' && !answerSetsCache.size) {
                loadAnswerSets();
            }

            if (source === 'new') {
                answerSetSelect.value = '';
                answerSetIdInput.value = '';
            }

            updateAnswerSetHelpers();

            const showQuestion = source === 'question';
            reuseQuestionTools.classList.toggle('d-none', !showQuestion);
            if (!showQuestion) {
                resetQuestionReuseUI(false);
            }
        }

        function resetQuestionReuseUI(clearTerm = false) {
            if (clearTerm) {
                questionSearchTerm.value = '';
            }
            questionResults.innerHTML = '';
            loadQuestionAnswersBtn.disabled = true;
            clearQuestionSelectionBtn.style.display = 'none';
            questionAnswerPreview.classList.add('d-none');
            questionAnswerList.innerHTML = '';
            questionSelectedHint.textContent = '';
            applyQuestionAnswersBtn.disabled = true;
            currentQuestionData = null;
            validateMaxChoices();
        }

        async function loadAnswerSets() {
            try {
                const res = await fetch('/dashboard/assessments/api/answer-sets?with_items=1', {
                    headers: { 'Accept': 'application/json' }
                });
                if (!res.ok) return;
                const data = await res.json();
                answerSetsCache.clear();
                answerSetSelect.innerHTML = '<option value="">Create new set</option>';
                data.forEach(set => {
                    answerSetsCache.set(String(set.id), set);
                    const option = document.createElement('option');
                    option.value = set.id;
                    option.textContent = `${set.name} (${set.items.length})`;
                    answerSetSelect.appendChild(option);
                });
                if (answerSetIdInput.value) {
                    answerSetSelect.value = answerSetIdInput.value;
                }
                updateAnswerSetHelpers();
            } catch (e) {
                console.error(e);
            }
        }

        function populateFromSet(set) {
            choiceTable.innerHTML = '';
            set.items.forEach(item => addChoiceRow({
                answer_set_item_id: item.id,
                label: item.label,
                value: item.value,
                is_active: item.is_active,
            }));
            if (choiceTable.children.length === 0) {
                addChoiceRow();
            }
            validateMaxChoices();
            renumberRows('choice');
        }

        function renderQuestionAnswers(data) {
            currentQuestionData = data;
            questionAnswerList.innerHTML = '';
            if (!data.answers.length) {
                const empty = document.createElement('div');
                empty.className = 'text-muted small';
                empty.textContent = 'No answers available on the selected question.';
                questionAnswerList.appendChild(empty);
                applyQuestionAnswersBtn.disabled = true;
                return;
            }
            data.answers.forEach((ans, idx) => {
                const item = document.createElement('label');
                item.className = 'list-group-item d-flex align-items-center gap-2';
                const checkbox = document.createElement('input');
                checkbox.type = 'checkbox';
                checkbox.className = 'form-check-input mt-0';
                checkbox.dataset.index = idx;
                checkbox.checked = true;
                item.appendChild(checkbox);
                const content = document.createElement('div');
                const title = document.createElement('div');
                title.innerHTML = `<strong>${ans.label}</strong>${ans.is_correct ? ' <span class="badge bg-success-subtle text-success">Correct</span>' : ''}`;
                const meta = document.createElement('div');
                meta.className = 'small text-muted';
                meta.textContent = ans.value ? `Value: ${ans.value}` : '';
                content.appendChild(title);
                if (ans.value) {
                    content.appendChild(meta);
                }
                item.appendChild(content);
                questionAnswerList.appendChild(item);
            });
            applyQuestionAnswersBtn.disabled = false;
        }

        function populateFromQuestionAnswers(selectedAnswers, answerSet) {
            choiceTable.innerHTML = '';
            selectedAnswers.forEach(ans => addChoiceRow({
                answer_set_item_id: ans.answer_set_item_id,
                label: ans.label,
                value: ans.value,
                value_override: ans.value_override ?? ans.value,
                label_override: ans.label_override ?? '',
                is_active: ans.is_active,
                is_correct: ans.is_correct,
            }));
            if (choiceTable.children.length === 0) {
                addChoiceRow();
            }
            validateMaxChoices();
            renumberRows('choice');

            if (answerSet && answerSet.id) {
                answerSetIdInput.value = answerSet.id;
            }
        }

        async function searchQuestions() {
            const term = questionSearchTerm.value.trim();
            if (!term) {
                resetQuestionReuseUI(false);
                return;
            }
            loadQuestionAnswersBtn.disabled = true;
            clearQuestionSelectionBtn.style.display = 'none';
            questionAnswerPreview.classList.add('d-none');
            questionAnswerList.innerHTML = '';
            questionSelectedHint.textContent = '';

            try {
                const params = new URLSearchParams({
                    q: term,
                    per_page: '10',
                });
                const res = await fetch(`/dashboard/assessments/questions/search?${params.toString()}`, {
                    headers: { 'Accept': 'application/json' },
                });
                if (!res.ok) {
                    throw new Error('Failed to search questions');
                }
                const payload = await res.json();
                questionResults.innerHTML = '';
                if (!payload.data.length) {
                    const option = document.createElement('option');
                    option.disabled = true;
                    option.selected = true;
                    option.textContent = 'No results';
                    questionResults.appendChild(option);
                    return;
                }
                payload.data.forEach(item => {
                    const option = document.createElement('option');
                    option.value = item.id;
                    option.textContent = `${item.title} (ID: ${item.id})`;
                    questionResults.appendChild(option);
                });
                questionResults.selectedIndex = 0;
                loadQuestionAnswersBtn.disabled = false;
                clearQuestionSelectionBtn.style.display = '';
            } catch (error) {
                console.error(error);
                questionResults.innerHTML = '';
                const option = document.createElement('option');
                option.disabled = true;
                option.selected = true;
                option.textContent = 'Unable to load results';
                questionResults.appendChild(option);
            }
        }

        async function fetchQuestionAnswers(questionId) {
            try {
                const res = await fetch(`/dashboard/assessments/questions/${questionId}/answers`, {
                    headers: { 'Accept': 'application/json' },
                });
                if (!res.ok) {
                    throw new Error('Unable to fetch answers');
                }
                const data = await res.json();
                renderQuestionAnswers(data);
                questionAnswerPreview.classList.remove('d-none');
                const trimmed = (data.question.text || '').trim();
                const truncated = trimmed.length > 80 ? `${trimmed.substring(0, 77)}…` : trimmed;
                questionSelectedHint.textContent = `Answers from “${truncated || data.question.slug}” (Question #${data.question.id})`;
                applyQuestionAnswersBtn.disabled = !data.answers.length;
                answerSetIdInput.value = data.answer_set?.id ?? '';
            } catch (error) {
                console.error(error);
                questionAnswerPreview.classList.add('d-none');
                questionAnswerList.innerHTML = '';
                questionSelectedHint.textContent = 'Unable to load answers for the selected question.';
                applyQuestionAnswersBtn.disabled = true;
            }
        }

        answerSetSelect.addEventListener('change', () => {
            answerSetIdInput.value = answerSetSelect.value;
            if (answerSetSelect.value && answerSetsCache.has(answerSetSelect.value)) {
                populateFromSet(answerSetsCache.get(answerSetSelect.value));
            }
            updateAnswerSetHelpers();
        });

        refreshSetsBtn.addEventListener('click', () => {
            loadAnswerSets();
        });

        clearSetBtn.addEventListener('click', () => {
            answerSetSelect.value = '';
            answerSetIdInput.value = '';
            updateAnswerSetHelpers();
            validateMaxChoices();
        });

        answerSourceSelect.addEventListener('change', () => {
            syncAnswerSourceUI();
        });

        questionSearchBtn.addEventListener('click', searchQuestions);
        questionSearchTerm.addEventListener('keydown', (event) => {
            if (event.key === 'Enter') {
                event.preventDefault();
                searchQuestions();
            }
        });

        questionResults.addEventListener('change', () => {
            loadQuestionAnswersBtn.disabled = !questionResults.value;
        });
        questionResults.addEventListener('dblclick', () => {
            if (questionResults.value) {
                fetchQuestionAnswers(questionResults.value);
            }
        });

        loadQuestionAnswersBtn.addEventListener('click', () => {
            if (questionResults.value) {
                fetchQuestionAnswers(questionResults.value);
            }
        });

        clearQuestionSelectionBtn.addEventListener('click', () => {
            resetQuestionReuseUI(true);
        });

        applyQuestionAnswersBtn.addEventListener('click', () => {
            if (!currentQuestionData) return;
            const selected = Array.from(questionAnswerList.querySelectorAll('input[type="checkbox"]'))
                .filter(cb => cb.checked)
                .map(cb => currentQuestionData.answers[Number(cb.dataset.index)]);
            if (!selected.length) {
                return;
            }
            populateFromQuestionAnswers(selected, currentQuestionData.answer_set);
            answerSourceSelect.value = 'question';
            syncAnswerSourceUI();
        });

        document.getElementById('add-choice').addEventListener('click', () => addChoiceRow());
        document.getElementById('add-part').addEventListener('click', () => addPartRow());
        responseTypeField.addEventListener('change', toggleSections);
        if (maxChoicesInput) {
            maxChoicesInput.addEventListener('input', validateMaxChoices);
        }

        toggleSections();
        answerSourceSelect.value = defaultAnswerSource;
        syncAnswerSourceUI();
        loadAnswerSets();
        validateMaxChoices();
    </script>
@endsection
