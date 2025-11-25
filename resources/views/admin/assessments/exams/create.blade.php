@extends('dashboard.mt.main', ['page' => $page])

@section('content')
    @include('admin.assessments.partials.alerts')
    <form method="post" action="{{ route('dashboard.assessments.exams.store') }}" class="card">
        @csrf
        <div class="card-body">
            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label">Title</label>
                    <input name="title" class="form-control" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Slug</label>
                    <input name="slug" class="form-control" required>
                </div>
            </div>
            <div class="row mb-3">
                <div class="col-md-3">
                    <label class="form-label">Mode</label>
                    <select name="assembly_mode" id="assembly_mode" class="form-select">
                        <option value="manual">Manual</option>
                        <option value="by_count">By Count</option>
                        <option value="by_score">By Total Score</option>
                    </select>
                </div>
                <div class="col-md-3" id="count_group" style="display:none">
                    <label class="form-label">Question Count</label>
                    <input name="question_count" type="number" class="form-control" value="10" min="1">
                </div>
                <div class="col-md-3" id="score_group" style="display:none">
                    <label class="form-label">Target Total Score</label>
                    <input name="target_total_score" id="target_total_score" type="number" class="form-control" value="10" min="1">
                </div>
                <div class="col-md-3 form-check form-switch d-flex align-items-end">
                    <input class="form-check-input" type="checkbox" name="is_published" value="1">
                    <label class="form-check-label ms-2">Published</label>
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <strong>Activation Link (optional)</strong>
                    <small class="text-muted">One-time activation for candidates</small>
                </div>
                <div class="card-body">
                    @php
                        $activationPrefix = trim(config('assessments.activation.prefix', 'assessments/activate'), '/');
                        $defaultPath = $activationPrefix ? $activationPrefix . '/' : '';
                    @endphp
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Activation Path</label>
                            <div class="input-group">
                                <span class="input-group-text">/{{ $defaultPath }}</span>
                                <input name="activation_path" class="form-control" placeholder="your-slug" value="{{ old('activation_path') }}">
                            </div>
                            <div class="form-text">Defaults to prefix + slug; you can leave blank to auto-fill.</div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Activation Expires At (optional)</label>
                            <input type="datetime-local" name="activation_expires_at" class="form-control" value="{{ old('activation_expires_at') }}">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Difficulty Split (visible when Mode = by_score or by_count) -->
            <div class="card mb-3" id="split_card" style="display:none">
                <div class="card-header"><strong>Difficulty Split</strong> <span class="text-muted" id="split_label_hint">(Score)</span></div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label" id="lbl_split_hard">Hard Score</label>
                            <input type="number" min="0" value="0" class="form-control split-input" name="difficulty_split[hard]" id="split_hard">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label" id="lbl_split_medium">Medium Score</label>
                            <input type="number" min="0" value="0" class="form-control split-input" name="difficulty_split[medium]" id="split_medium">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label" id="lbl_split_easy">Easy Score</label>
                            <input type="number" min="0" value="0" class="form-control split-input" name="difficulty_split[easy]" id="split_easy">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Very Hard (optional)</label>
                            <input type="number" min="0" value="0" class="form-control split-input" name="difficulty_split[very_hard]" id="split_very_hard">
                        </div>
                    </div>
                    <div class="mt-2" id="split_sum_indicator">
                        Split total = <span id="split_sum">0</span> / Target = <span id="split_target">0</span>
                    </div>
                    <div class="text-danger small mt-1" id="split_error" style="display:none">Target must equal the sum of difficulty splits.</div>
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label">Topics (optional)</label>
                    <select name="topics[]" class="form-select" multiple>
                @foreach($topics as $t)
                    <option value="{{ $t->id }}">{{ $t->name }}</option>
                @endforeach
                </select>
                <div class="form-text" id="pool_scope_hint">No topics selected — using Category pool.</div>
            </div>
            <div class="col-md-6">
                <label class="form-label">Category (visibility)</label>
                    <select name="category_id" class="form-select">
                        <option value="">All</option>
                        @foreach($categories as $cat)
                            <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label">Time Limit (seconds) — stored only</label>
                    <input name="time_limit_seconds" type="number" class="form-control" placeholder="e.g. 1800">
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-3">
                    <label class="form-label">Pass Type</label>
                    <select name="pass_type" class="form-select">
                        <option value="percent">Percent</option>
                        <option value="score">Score</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Pass Value</label>
                    <input name="pass_value" type="number" class="form-control" value="70" min="0">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Max Attempts</label>
                    <input name="max_attempts" type="number" class="form-control" value="1" min="1">
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-3 form-check form-switch">
                    <input class="form-check-input" type="checkbox" name="shuffle_questions" value="1">
                    <label class="form-check-label">Shuffle Questions</label>
                </div>
                <div class="col-md-3 form-check form-switch">
                    <input class="form-check-input" type="checkbox" name="shuffle_options" value="1">
                    <label class="form-check-label">Shuffle Options</label>
                </div>
                <div class="col-md-3 form-check form-switch">
                    <input class="form-check-input" type="checkbox" name="show_explanations" value="1" {{ old('show_explanations') ? 'checked' : '' }}>
                    <label class="form-check-label">Show Explanations After Submit</label>
                    <div class="form-text">Displays each question's explanation on the candidate result screen.</div>
                </div>
            </div>

            <div id="manual_block">
                <h5>Manual Questions</h5>
                <p class="text-muted">Pick explicit questions for this exam. Use multi-select below; order will follow selection sequence (you can re-save with desired order).</p>
                <select name="manual_questions[]" class="form-select" multiple size="10">
                    @foreach($questions as $q)
                        <option value="{{ $q->id }}">[{{ $q->id }}] (w={{ $q->weight }}) {{ \Illuminate\Support\Str::limit($q->text, 80) }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="card-footer d-flex justify-content-end">
            <button class="btn btn-primary">Save</button>
        </div>
    </form>
    <script>
        function toggleMode(){
            const mode = document.getElementById('assembly_mode').value;
            document.getElementById('manual_block').style.display = (mode === 'manual') ? '' : 'none';
            document.getElementById('count_group').style.display = (mode === 'by_count') ? '' : 'none';
            document.getElementById('score_group').style.display = (mode === 'by_score') ? '' : 'none';
            document.getElementById('split_card').style.display = (mode === 'by_score' || mode==='by_count') ? '' : 'none';
            const hint = document.getElementById('split_label_hint');
            document.getElementById('lbl_split_hard').textContent = 'Hard ' + (mode==='by_count' ? 'Count' : 'Score');
            document.getElementById('lbl_split_medium').textContent = 'Medium ' + (mode==='by_count' ? 'Count' : 'Score');
            document.getElementById('lbl_split_easy').textContent = 'Easy ' + (mode==='by_count' ? 'Count' : 'Score');
            hint.textContent = '(' + (mode==='by_count' ? 'Count' : 'Score') + ')';
            updateSplitIndicator();
        }
        document.getElementById('assembly_mode').addEventListener('change', toggleMode);
        toggleMode();

        function updateSplitIndicator(){
            const mode = document.getElementById('assembly_mode').value;
            const tgt = mode==='by_score' ? parseInt(document.getElementById('target_total_score')?.value || '0') : parseInt(document.querySelector('input[name="question_count"]')?.value || '0');
            const vals = ['split_hard','split_medium','split_easy','split_very_hard']
                .map(id=>parseInt(document.getElementById(id)?.value||'0'));
            const sum = vals.reduce((a,b)=>a+b,0);
            document.getElementById('split_sum').textContent = sum;
            document.getElementById('split_target').textContent = tgt;
            const ok = (mode === 'manual') || (sum === tgt && tgt>0);
            document.getElementById('split_sum_indicator').style.color = ok ? 'green' : 'red';
            document.getElementById('split_error').style.display = ok ? 'none' : '';
        }
        ['target_total_score','question_count','split_hard','split_medium','split_easy','split_very_hard'].forEach(id=>{
            const el = document.getElementById(id);
            if(el) el.addEventListener('input', updateSplitIndicator);
        });

        // Topics pool hint
        const topicsSelect = document.querySelector('select[name="topics[]"]');
        if (topicsSelect) topicsSelect.addEventListener('change', function(){
            const selected = Array.from(this.selectedOptions).map(o=>o.textContent.trim());
            const hint = document.getElementById('pool_scope_hint');
            hint.textContent = selected.length ? ('Pool: Category ∩ {'+ selected.join(', ') +'}') : 'No topics selected — using Category pool.';
        });
    </script>
@endsection
