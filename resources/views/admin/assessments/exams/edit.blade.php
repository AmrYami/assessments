@extends('dashboard.mt.main', ['page' => $page])

@section('content')
    @include('admin.assessments.partials.alerts')
    <form method="post" action="{{ route('dashboard.assessments.exams.update', $exam) }}" class="card">
        @csrf @method('put')
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="badge bg-light text-dark">Family: E-{{ $exam->parent_id ?: $exam->id }} — v{{ (int)($exam->version_int ?? 1) }}</span>
                @if(config('assessments.diff_and_history'))
                    <a class="btn btn-sm btn-outline-secondary" href="{{ route('dashboard.assessments.exams.history', $exam) }}">View History</a>
                @endif
            </div>
            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label">Title</label>
                    <input name="title" class="form-control" value="{{ old('title', $exam->title) }}" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Slug</label>
                    <input name="slug" class="form-control" value="{{ old('slug', $exam->slug) }}" required>
                    <div class="form-text">Status: <span class="badge bg-{{ $exam->status==='published' ? 'success' : ($exam->status==='archived' ? 'secondary' : 'warning') }}">{{ ucfirst($exam->status ?? ($exam->is_published ? 'published' : 'draft')) }}</span></div>
                </div>
            </div>
            <div class="row mb-3">
                <div class="col-md-3">
                    <label class="form-label">Mode</label>
                    <select name="assembly_mode" id="assembly_mode" class="form-select">
                        @foreach(['manual','by_count','by_score'] as $m)
                            <option value="{{ $m }}" @selected(old('assembly_mode', $exam->assembly_mode)===$m)>{{ ucwords(str_replace('_',' ', $m)) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3" id="count_group" style="display:none">
                    <label class="form-label">Question Count</label>
                    <input name="question_count" type="number" class="form-control" value="{{ old('question_count', $exam->question_count) }}" min="1">
                </div>
                <div class="col-md-3" id="score_group" style="display:none">
                    <label class="form-label">Target Total Score</label>
                    <input name="target_total_score" id="target_total_score" type="number" class="form-control" value="{{ old('target_total_score', $exam->target_total_score) }}" min="1">
                </div>
                <div class="col-md-3 form-check form-switch d-flex align-items-end">
                    <input class="form-check-input" type="checkbox" name="is_published" value="1" {{ $exam->is_published ? 'checked' : '' }}>
                    <label class="form-check-label ms-2">Published</label>
                </div>
            </div>

            <!-- Difficulty Score Split (visible when Mode = by_score) -->
            <div class="card mb-3" id="split_card" style="display:none">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <strong>Difficulty Split</strong> <span class="text-muted" id="split_label_hint">(Score)</span>
                    <button type="button" class="btn btn-sm btn-outline-secondary" id="btn_coverage_check">Coverage / Check</button>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label" id="lbl_split_hard">Hard Score</label>
                            <input type="number" min="0" value="{{ old('difficulty_split.hard', $exam->difficulty_split_json['splits']['hard'] ?? 0) }}" class="form-control split-input" name="difficulty_split[hard]" id="split_hard">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label" id="lbl_split_medium">Medium Score</label>
                            <input type="number" min="0" value="{{ old('difficulty_split.medium', $exam->difficulty_split_json['splits']['medium'] ?? 0) }}" class="form-control split-input" name="difficulty_split[medium]" id="split_medium">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label" id="lbl_split_easy">Easy Score</label>
                            <input type="number" min="0" value="{{ old('difficulty_split.easy', $exam->difficulty_split_json['splits']['easy'] ?? 0) }}" class="form-control split-input" name="difficulty_split[easy]" id="split_easy">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Very Hard (optional)</label>
                            <input type="number" min="0" value="{{ old('difficulty_split.very_hard', $exam->difficulty_split_json['splits']['very_hard'] ?? 0) }}" class="form-control split-input" name="difficulty_split[very_hard]" id="split_very_hard">
                        </div>
                    </div>
                    <div class="mt-2" id="split_sum_indicator">
                        Split total = <span id="split_sum">0</span> / Target = <span id="split_target">0</span>
                    </div>
                    <div class="text-danger small mt-1" id="split_error" style="display:none">Target must equal the sum of difficulty splits.</div>
                    <div class="mt-3" id="coverage_panel" style="display:none"></div>
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label">Topics (optional)</label>
                    <select name="topics[]" class="form-select" multiple>
                        @foreach($topics as $t)
                            <option value="{{ $t->id }}" {{ $exam->topics->contains('id',$t->id) ? 'selected' : '' }}>{{ $t->name }}</option>
                        @endforeach
                    </select>
                    <div class="form-text" id="pool_scope_hint">{{ $exam->topics->count() ? ('Pool: Category ∩ {'. $exam->topics->pluck('name')->implode(', ') .'}') : 'No topics selected — using Category pool.' }}</div>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Category (visibility)</label>
                    <select name="category_id" class="form-select" {{ ($exam->status ?? 'draft') !== 'draft' ? 'disabled' : '' }}>
                        <option value="">All</option>
                        @foreach($categories as $cat)
                            <option value="{{ $cat->id }}" @selected(old('category_id', $exam->category_id)===$cat->id)>{{ $cat->name }}</option>
                        @endforeach
                    </select>
                    @if(($exam->status ?? 'draft') !== 'draft')
                        <input type="hidden" name="category_id" value="{{ $exam->category_id }}">
                        <div class="form-text">Published — Category locked</div>
                    @endif
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label">Time Limit (seconds)</label>
                    <input name="time_limit_seconds" type="number" class="form-control" value="{{ old('time_limit_seconds', $exam->time_limit_seconds) }}">
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-3">
                    <label class="form-label">Pass Type</label>
                    <select name="pass_type" class="form-select">
                        @foreach(['percent','score'] as $pt)
                            <option value="{{ $pt }}" @selected(old('pass_type', $exam->pass_type)===$pt)>{{ ucfirst($pt) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Pass Value</label>
                    <input name="pass_value" type="number" class="form-control" value="{{ old('pass_value', $exam->pass_value) }}" min="0">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Max Attempts</label>
                    <input name="max_attempts" type="number" class="form-control" value="{{ old('max_attempts', $exam->max_attempts) }}" min="1">
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-3 form-check form-switch">
                    <input class="form-check-input" type="checkbox" name="shuffle_questions" value="1" {{ $exam->shuffle_questions ? 'checked' : '' }}>
                    <label class="form-check-label">Shuffle Questions</label>
                </div>
                <div class="col-md-3 form-check form-switch">
                    <input class="form-check-input" type="checkbox" name="shuffle_options" value="1" {{ $exam->shuffle_options ? 'checked' : '' }}>
                    <label class="form-check-label">Shuffle Options</label>
                </div>
                <div class="col-md-3 form-check form-switch">
                    <input class="form-check-input" type="checkbox" name="show_explanations" value="1" {{ old('show_explanations', $exam->show_explanations) ? 'checked' : '' }}>
                    <label class="form-check-label">Show Explanations After Submit</label>
                    <div class="form-text">Displays each question's explanation on the candidate result screen.</div>
                </div>
            </div>

            <div id="manual_block">
                <h5>Manual Questions</h5>
                <select name="manual_questions[]" class="form-select" multiple size="10">
                    @foreach($questions as $q)
                        <option value="{{ $q->id }}" @selected($exam->questions->pluck('id')->contains($q->id))>[{{ $q->id }}] (w={{ $q->weight }}) {{ \Illuminate\Support\Str::limit($q->text, 80) }}</option>
                    @endforeach
                </select>
                <p class="text-muted mt-2">Order will follow selection sequence; you can adjust by re-saving.</p>
            </div>
        </div>
        <div class="card-footer d-flex justify-content-between">
            <div>
                <a href="{{ route('dashboard.assessments.exams.preview', $exam) }}" class="btn btn-secondary" id="btn_preview">Preview</a>
                <form action="{{ route('dashboard.assessments.exams.publish', $exam) }}" method="post" style="display:inline-block">@csrf
                    <button class="btn btn-success" id="btn_publish">Publish</button>
                </form>
            </div>
            <button class="btn btn-primary">Update</button>
        </div>
    </form>

    <div class="card mt-3">
        <div class="card-header"><strong>Scopes / Propagation</strong></div>
        <div class="card-body">
            <div class="row g-3 align-items-end">
                <div class="col-md-3 form-check">
                    <input class="form-check-input" type="checkbox" id="pp_all_categories" checked>
                    <label class="form-check-label" for="pp_all_categories">Apply to All Categories</label>
                </div>
                <div class="col-md-3 form-check">
                    <input class="form-check-input" type="checkbox" id="pp_all_topics" checked>
                    <label class="form-check-label" for="pp_all_topics">Apply to All Topics</label>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Mode</label>
                    <select id="pp_mode" class="form-select">
                        <option value="bump_placement" selected>Bump placement version</option>
                        <option value="clone_and_remap">Clone & remap</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Effective at (optional)</label>
                    <input type="datetime-local" id="pp_effective_at" class="form-control">
                </div>
                <div class="col-md-2">
                    <button class="btn btn-outline-secondary" onclick="previewExamImpact()">Preview Impact</button>
                </div>
                <div class="col-md-1">
                    <button class="btn btn-outline-primary" onclick="propagateExam()">Propagate</button>
                </div>
            </div>
            <div id="pp_result" class="small text-muted mt-2"></div>
            <div id="pp_preview_panel" class="mt-3" style="display:none"></div>
        </div>
    </div>
    <script>
        let coverageOk = false;
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
            const publishBtn = document.getElementById('btn_publish');
            if (publishBtn) publishBtn.disabled = !ok || !coverageOk;
        }
        ['target_total_score','question_count','split_hard','split_medium','split_easy','split_very_hard','assembly_mode'].forEach(id=>{
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

        // Coverage / Check
        const covBtn = document.getElementById('btn_coverage_check');
        if (covBtn) covBtn.addEventListener('click', async function(){
            const panel = document.getElementById('coverage_panel');
            panel.style.display=''; panel.innerHTML = 'Checking…';
            try{
                const res = await fetch('{{ route('dashboard.assessments.exams.coverage', $exam) }}');
                const data = await res.json();
                coverageOk = !!data.achievable;
                const hints = (data.hints||[]).map(h=>`<li>${h}</li>`).join('');
                let statusHtml = coverageOk ? '<div class="text-success">Achievable — OK to publish.</div>' : '<div class="text-danger">Not Achievable</div>';
                if (data.tolerance_used) {
                    const actual = data.actual_score ?? 0;
                    const target = data.target_score ?? 0;
                    statusHtml += `<div class="text-warning">Tolerance applied: assembled score ${actual}/${target}.</div>`;
                }
                panel.innerHTML = statusHtml + (hints ? `<ul>${hints}</ul>` : '');
            }catch(e){
                panel.innerHTML = '<div class="text-danger">Coverage check failed.</div>';
                coverageOk = false;
            }
            updateSplitIndicator();
        });

        const isPropagationStrict = {{ config('assessments.propagation_strict') ? 'true' : 'false' }};
        async function propagateExam(){
            const apply_to = {};
            apply_to.categories = document.getElementById('pp_all_categories').checked ? 'all' : [];
            apply_to.topics = document.getElementById('pp_all_topics').checked ? 'all' : [];
            const mode = document.getElementById('pp_mode').value;
            let confirm_global = false;
            if (isPropagationStrict && apply_to.categories==='all' && apply_to.topics==='all') {
                confirm_global = confirm('This will apply globally to all categories and topics. Proceed?');
                if (!confirm_global) return;
            }
            const effective_at = document.getElementById('pp_effective_at').value || null;
            const res = await fetch('{{ route('dashboard.assessments.exams.propagate', $exam) }}', {
                method:'POST', headers:{'Content-Type':'application/json','X-CSRF-TOKEN': '{{ csrf_token() }}'},
                body: JSON.stringify({ apply_to, mode, confirm_global, effective_at })
            });
            const el = document.getElementById('pp_result');
            if (!res.ok){ el.textContent = 'Propagation failed'; el.className='small text-danger'; return; }
            const data = await res.json();
            el.textContent = `Propagation completed (updated: ${data.updated}, mode: ${data.mode})` + (data.note ? ` — ${data.note}` : '');
            el.className='small text-success';
        }
        async function previewExamImpact(){
            const apply_to = {};
            apply_to.categories = document.getElementById('pp_all_categories').checked ? 'all' : [];
            apply_to.topics = document.getElementById('pp_all_topics').checked ? 'all' : [];
            const mode = document.getElementById('pp_mode').value;
            const effective_at = document.getElementById('pp_effective_at').value || null;
            const panel = document.getElementById('pp_preview_panel');
            panel.style.display=''; panel.innerHTML = 'Loading…';
            const res = await fetch('{{ route('dashboard.assessments.exams.propagate.preview', $exam) }}', {
                method: 'POST', headers: { 'Content-Type':'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                body: JSON.stringify({ apply_to, mode, effective_at })
            });
            if (!res.ok){ panel.innerHTML = '<div class="text-danger">Preview failed</div>'; return; }
            const data = await res.json();
            let html = `<div class=\"card\"><div class=\"card-header\">Impact Preview</div><div class=\"card-body\">`;
            html += `<div>Targets — Categories: ${data.apply_to.categories.length || 'All'}, Topics: ${data.apply_to.topics.length || 'All'}</div>`;
            if (data.by_score){
                html += `<div class=\"mt-2\"><b>By-score:</b> target=${data.by_score.target} → ${data.by_score.achievable ? '<span class=\"text-success\">achievable</span>' : '<span class=\"text-danger\">not achievable</span>'}`;
                if (Array.isArray(data.by_score.hints) && data.by_score.hints.length){ html += `<ul>${data.by_score.hints.map(h=>`<li>${h}</li>`).join('')}</ul>`; }
                html += `</div>`;
            }
            html += `</div></div>`;
            panel.innerHTML = html;
        }
    </script>
@endsection
