<?php

namespace Yami\Assessments\Http\Controllers\Admin;

use Yami\Assessments\Support\Controller;
use Yami\Assessments\Domain\Models\{
    AnswerKey,
    AnswerSet,
    AnswerSetItem,
    Question,
    QuestionAnswer,
    QuestionOption,
    QuestionResponsePart,
    Topic
};
use Yami\Assessments\Http\Requests\Admin\{StoreQuestionRequest, UpdateQuestionRequest};
use Yami\Assessments\Support\ModelResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class QuestionController extends Controller
{
    public function index(Request $request)
    {
        $this->ensureAdminAccess();
        $page = 'Assessments — Questions';

        $query = Question::query()->orderByDesc('id');
        if ($request->filled('difficulty')) {
            $query->where('difficulty', $request->string('difficulty'));
        }
        if ($request->filled('active')) {
            $query->where('is_active', (bool) $request->integer('active'));
        }
        if ($request->filled('topic_id')) {
            $topicId = $request->integer('topic_id');
            $query->whereHas('topics', fn($q) => $q->where('assessment_topics.id', $topicId));
        }

        $items = $query->paginate(20)->appends($request->query());
        $topics = Topic::orderBy('name')->get();
        return view('admin.assessments.questions.index', compact('page', 'items', 'topics'));
    }

    public function create()
    {
        $this->ensureAdminAccess();
        $page = 'Assessments — New Question';
        $topics = Topic::orderBy('name')->get();
        $answerSets = AnswerSet::orderBy('name')->get();
        return view('admin.assessments.questions.create', compact('page', 'topics', 'answerSets'));
    }

    public function store(StoreQuestionRequest $request)
    {
        $this->ensureAdminAccess();
        $payload = $request->validatedPayload();

        $question = DB::transaction(function () use ($payload) {
            $question = Question::create([
                'slug' => $payload['slug'],
                'text' => $payload['text'],
                'response_type' => $payload['response_type'],
                'weight' => $payload['weight'],
                'difficulty' => $payload['difficulty'],
                'is_active' => (bool) ($payload['is_active'] ?? true),
                'note_enabled' => (bool) ($payload['note_enabled'] ?? false),
                'note_required' => (bool) ($payload['note_required'] ?? false),
                'note_hint' => $payload['note_hint'] ?? null,
                'max_choices' => $payload['max_choices'] ?? null,
                'explanation' => $payload['explanation'] ?? null,
            ]);

            $question->topics()->sync($payload['topics'] ?? []);
            $this->syncQuestionRelations($question, $payload);

            return $question;
        });

        $this->refreshSchemaHash($question);

        return redirect()
            ->route('dashboard.assessments.questions.index')
            ->with('success', 'Question created');
    }

    public function edit(Question $question)
    {
        $this->ensureAdminAccess();
        $page = 'Assessments — Edit Question';
        $topics = Topic::orderBy('name')->get();
        $question->load([
            'topics',
            'options' => fn($q) => $q->orderBy('position'),
            'answerKeys',
            'answerLinks' => fn($q) => $q->withTrashed()->orderBy('position'),
            'answerLinks.item',
            'responseParts' => fn($q) => $q->orderBy('position'),
        ]);
        $currentAnswerSetId = optional($question->answerLinks->first())->item->answer_set_id;

        return view('admin.assessments.questions.edit', compact('page', 'question', 'topics', 'currentAnswerSetId'));
    }

    public function update(UpdateQuestionRequest $request, Question $question)
    {
        $this->ensureAdminAccess();
        $payload = $request->validatedPayload($question);

        $question = DB::transaction(function () use ($payload, $question) {
            $question->update([
                'slug' => $payload['slug'],
                'text' => $payload['text'],
                'response_type' => $payload['response_type'],
                'weight' => $payload['weight'],
                'difficulty' => $payload['difficulty'],
                'is_active' => (bool) ($payload['is_active'] ?? true),
                'note_enabled' => (bool) ($payload['note_enabled'] ?? false),
                'note_required' => (bool) ($payload['note_required'] ?? false),
                'note_hint' => $payload['note_hint'] ?? null,
                'max_choices' => $payload['max_choices'] ?? null,
                'explanation' => $payload['explanation'] ?? null,
            ]);

            $question->topics()->sync($payload['topics'] ?? []);
            $this->syncQuestionRelations($question, $payload);

            return $question;
        });

        $this->refreshSchemaHash($question);

        return redirect()
            ->route('dashboard.assessments.questions.index')
            ->with('success', 'Question updated');
    }

    public function search(Request $request)
    {
        $this->ensureAdminAccess();
        if (!$request->wantsJson()) {
            abort(400);
        }

        $q = Question::query();
        if ($term = $request->string('q')->toString()) {
            $like = '%' . $term . '%';
            $q->where(function ($query) use ($like) {
                $query->where('text', 'like', $like)
                    ->orWhere('slug', 'like', $like);
            });
        }
        if ($cats = $request->input('category_ids')) {
            $ids = array_map('intval', is_array($cats) ? $cats : [$cats]);
            $q->whereHas('categories', fn($qq) => $qq->whereIn('categories.id', $ids));
        }
        if ($tops = $request->input('topic_ids')) {
            $ids = array_map('intval', is_array($tops) ? $tops : [$tops]);
            $q->whereHas('topics', fn($qq) => $qq->whereIn('assessment_topics.id', $ids));
        }
        if ($difficulties = $request->input('difficulty')) {
            $vals = is_array($difficulties) ? $difficulties : [$difficulties];
            $q->whereIn('difficulty', $vals);
        }
        if ($request->filled('status')) {
            $status = $request->string('status')->toString();
            if ($status === 'active') {
                $q->where('is_active', true);
            }
            if ($status === 'inactive') {
                $q->where('is_active', false);
            }
        }
        if ($aid = $request->integer('has_linked_answer_id')) {
            $q->whereExists(function ($sub) use ($aid) {
                $sub->select(DB::raw(1))
                    ->from('assessment_question_answer_links as qal')
                    ->whereColumn('qal.question_id', 'assessment_questions.id')
                    ->where('qal.answer_set_item_id', (int) $aid);
            });
        }

        $perPage = max(1, min(100, (int) $request->integer('per_page') ?: 20));
        $items = $q->orderByDesc('id')->paginate($perPage);

        $categoryModel = ModelResolver::category();
        $catMap = $categoryModel::whereIn(
            'id',
            DB::table('assessment_question_categories')
                ->whereIn('question_id', $items->pluck('id'))
                ->pluck('category_id')
        )->get(['id', 'name'])->keyBy('id');

        $topMap = Topic::whereIn(
            'id',
            DB::table('assessment_question_topics')
                ->whereIn('question_id', $items->pluck('id'))
                ->pluck('topic_id')
        )->get(['id', 'name'])->keyBy('id');

        $catsByQ = DB::table('assessment_question_categories')
            ->whereIn('question_id', $items->pluck('id'))
            ->get()
            ->groupBy('question_id');

        $topsByQ = DB::table('assessment_question_topics')
            ->whereIn('question_id', $items->pluck('id'))
            ->get()
            ->groupBy('question_id');

        $data = [];
        foreach ($items as $it) {
            $data[] = [
                'id' => $it->id,
                'title' => Str::limit($it->text, 80),
                'difficulty' => $it->difficulty,
                'categories' => ($catsByQ[$it->id] ?? collect())
                    ->map(fn($row) => [
                        'id' => $row->category_id,
                        'name' => optional($catMap->get($row->category_id))->name,
                    ])
                    ->values()
                    ->all(),
                'topics' => ($topsByQ[$it->id] ?? collect())
                    ->map(fn($row) => [
                        'id' => $row->topic_id,
                        'name' => optional($topMap->get($row->topic_id))->name,
                    ])
                    ->values()
                    ->all(),
                'status' => $it->is_active ? 'active' : 'inactive',
            ];
        }

        return response()->json([
            'data' => $data,
            'meta' => ['page' => $items->currentPage(), 'total' => $items->total()],
        ]);
    }

    public function answers(Question $question)
    {
        $this->ensureAdminAccess();
        abort_unless(in_array($question->response_type, ['single_choice', 'multiple_choice'], true), 422, 'Question does not use selectable answers.');

        $question->load([
            'answerLinks' => fn($q) => $q->orderBy('position')->with('item.set'),
            'options' => fn($q) => $q->orderBy('position'),
            'answerKeys.option',
        ]);

        $answerSet = $this->ensureReusableAnswerSet($question);

        $question->load([
            'answerLinks' => fn($q) => $q->orderBy('position')->with('item'),
            'options' => fn($q) => $q->orderBy('position'),
            'answerKeys.option',
        ]);

        $correctIds = $question->answerKeys
            ->map(function (AnswerKey $key) {
                return $key->answer_set_item_id ?: optional($key->item)->id ?: optional($key->option)->answer_set_item_id;
            })
            ->filter()
            ->unique()
            ->values();

        $answers = $question->answerLinks->map(function (QuestionAnswer $link) use ($question, $correctIds) {
            $item = $link->item;
            $option = $question->options->firstWhere('answer_set_item_id', optional($item)->id);

            return [
                'answer_set_item_id' => optional($item)->id,
                'label' => optional($item)->label ?? optional($option)->label ?? ($link->label_override ?? 'Answer'),
                'value' => optional($item)->value ?? optional($option)->key,
                'label_override' => $link->label_override,
                'value_override' => $link->value_override,
                'is_active' => (bool) $link->is_active,
                'is_correct' => (bool) $link->is_correct || $correctIds->contains(optional($item)->id),
                'position' => $link->position,
            ];
        })->values();

        return response()->json([
            'question' => [
                'id' => $question->id,
                'slug' => $question->slug,
                'text' => $question->text,
                'response_type' => $question->response_type,
            ],
            'answer_set' => $answerSet ? [
                'id' => $answerSet->id,
                'name' => $answerSet->name,
            ] : null,
            'answers' => $answers,
        ]);
    }

    public function destroy(Question $question)
    {
        $this->ensureAdminAccess();
        $question->delete();
        return redirect()
            ->route('dashboard.assessments.questions.index')
            ->with('success', 'Question deleted');
    }

    protected function syncQuestionRelations(Question $question, array $payload): void
    {
        if (in_array($question->response_type, ['single_choice', 'multiple_choice'], true)) {
            $this->syncChoiceAnswers($question, $payload);
            $this->clearResponseParts($question);
        } else {
            $this->syncResponseParts($question, $payload['response_parts'] ?? [], $question->response_type);
        }
    }

    protected function syncChoiceAnswers(Question $question, array $payload): void
    {
        $links = $payload['answer_links'] ?? [];
        $answerSet = $this->resolveAnswerSet($question, $payload['answer_set_id'] ?? null);

        $keepItemIds = [];
        $optionByItem = [];
        $correctByItem = [];

        foreach ($links as $idx => $raw) {
            $itemId = $raw['answer_set_item_id'] ?? null;
            $labelForNew = trim((string) ($raw['label'] ?? ''));

            if (!$itemId) {
                $item = $answerSet->items()->create([
                    'label' => $labelForNew,
                    'value' => $raw['value'] ?? null,
                    'position' => $idx + 1,
                    'is_active' => (bool) ($raw['is_active'] ?? true),
                ]);
                $itemId = $item->id;
            } else {
                $item = AnswerSetItem::findOrFail($itemId);
            }

            $position = $raw['position'] ?? ($idx + 1);
            $isActive = (bool) ($raw['is_active'] ?? true);
            $isCorrect = (bool) ($raw['is_correct'] ?? false);
            $labelOverride = Arr::get($raw, 'label_override');
            $valueOverride = Arr::get($raw, 'value_override');

            $link = QuestionAnswer::withTrashed()->firstOrNew([
                'question_id' => $question->id,
                'answer_set_item_id' => $itemId,
            ]);
            $link->position = $position;
            $link->is_active = $isActive;
            $link->is_correct = $isCorrect;
            $link->label_override = $labelOverride ?: null;
            $link->value_override = $valueOverride ?: null;
            $link->deleted_at = null;
            $link->save();

            $option = QuestionOption::firstOrNew([
                'question_id' => $question->id,
                'answer_set_item_id' => $itemId,
            ]);
            $option->label = $labelOverride !== null ? $labelOverride : $item->label;
            $option->position = $position;
            $option->is_active = $isActive;
            if (!$option->exists || !$option->key) {
                $option->key = $this->uniqueOptionKey($question, $option->label, $option->id);
            }
            $option->save();

            $keepItemIds[] = $itemId;
            $optionByItem[$itemId] = $option->id;
            $correctByItem[$itemId] = $isCorrect;
        }

        QuestionAnswer::where('question_id', $question->id)
            ->whereNotIn('answer_set_item_id', $keepItemIds)
            ->delete();

        QuestionOption::where('question_id', $question->id)
            ->whereNotIn('answer_set_item_id', $keepItemIds)
            ->delete();

        AnswerKey::where('question_id', $question->id)->delete();
        foreach ($correctByItem as $itemId => $isCorrect) {
            if ($isCorrect && isset($optionByItem[$itemId])) {
                AnswerKey::create([
                    'question_id' => $question->id,
                    'option_id' => $optionByItem[$itemId],
                    'answer_set_item_id' => $itemId,
                ]);
            }
        }
    }

    protected function syncResponseParts(Question $question, array $parts, string $responseType): void
    {
        $keepKeys = [];
        $position = 1;

        foreach ($parts as $part) {
            $model = QuestionResponsePart::withTrashed()->firstOrNew([
                'question_id' => $question->id,
                'key' => $part['key'],
            ]);
            $model->label = $part['label'];
            $model->input_type = $part['input_type'] ?? ($responseType === 'textarea' ? 'textarea' : 'text');
            $model->required = (bool) ($part['required'] ?? false);
            $model->validation_mode = $part['validation_mode'] ?? 'none';
            $model->validation_value = $part['validation_value'] ?? null;
            $model->weight_share = $part['weight_share'] ?? null;
            $model->position = $position++;
            $model->deleted_at = null;
            $model->save();

            $keepKeys[] = $part['key'];
        }

        QuestionResponsePart::where('question_id', $question->id)
            ->whereNotIn('key', $keepKeys)
            ->delete();

        $this->clearChoiceAnswers($question);
    }

    protected function resolveAnswerSet(Question $question, ?int $answerSetId = null): AnswerSet
    {
        if ($answerSetId) {
            return AnswerSet::findOrFail($answerSetId);
        }

        $slug = 'q-' . $question->id;
        return AnswerSet::firstOrCreate(
            ['slug' => $slug],
            [
                'name' => 'Question ' . $question->id . ' Set',
                'description' => 'Auto-generated from question authoring.',
                'is_active' => true,
            ]
        );
    }

    protected function clearChoiceAnswers(Question $question): void
    {
        QuestionAnswer::where('question_id', $question->id)->delete();
        QuestionOption::where('question_id', $question->id)->delete();
        AnswerKey::where('question_id', $question->id)->delete();
    }

    protected function clearResponseParts(Question $question): void
    {
        QuestionResponsePart::where('question_id', $question->id)->delete();
    }

    protected function refreshSchemaHash(Question $question): void
    {
        try {
            $question->load(['options', 'answerLinks', 'responseParts']);
            $hash = app(\Yami\Assessments\Services\SchemaHashService::class)->computeForQuestion($question);
            $question->schema_hash = $hash;
            $question->save();
        } catch (\Throwable $e) {
            // ignore hash failures to avoid blocking authoring
        }
    }

    protected function uniqueOptionKey(Question $question, string $label, ?int $ignoreId = null): string
    {
        $base = Str::slug(mb_substr($label, 0, 32)) ?: 'option';
        $key = $base;
        $i = 1;
        while (
            QuestionOption::where('question_id', $question->id)
                ->where('key', $key)
                ->when($ignoreId, fn($q) => $q->where('id', '!=', $ignoreId))
                ->exists()
        ) {
            $key = $base . '-' . $i++;
        }
        return $key;
    }

    protected function ensureAdminAccess(): void
    {
        abort_unless(config('assessments.enabled') && config('assessments.admin_only'), 404);
    }

    protected function ensureReusableAnswerSet(Question $question): ?AnswerSet
    {
        $links = $question->answerLinks;

        if ($links->isEmpty()) {
            return null;
        }

        $firstItem = optional($links->first())->item;
        if ($firstItem && $firstItem->set) {
            return $firstItem->set;
        }

        return DB::transaction(function () use ($question, $links) {
            $answerSet = AnswerSet::firstOrCreate(
                ['slug' => 'q-' . $question->id],
                [
                    'name' => 'Question ' . $question->id . ' Set',
                    'description' => 'Auto-generated from existing question options.',
                    'is_active' => true,
                ]
            );

            $options = $question->options->keyBy('id');

            foreach ($links as $link) {
                if ($link->answer_set_item_id && $link->item && $link->item->set) {
                    continue;
                }

                $option = $options->firstWhere('answer_set_item_id', $link->answer_set_item_id)
                    ?: $options->firstWhere('position', $link->position)
                    ?: ($link->answer_set_item_id ? null : $options->firstWhere('id', $link->option_id ?? null));

                $position = $link->position ?? optional($option)->position ?? ($links->search($link) + 1);
                $label = $link->label_override ?? optional($option)->label ?? 'Answer ' . $position;
                $value = $link->value_override ?? optional($option)->key;

                $item = $answerSet->items()->create([
                    'label' => $label,
                    'value' => $value,
                    'position' => $position,
                    'is_active' => (bool) $link->is_active,
                ]);

                $link->answer_set_item_id = $item->id;
                $link->save();

                if ($option) {
                    $option->answer_set_item_id = $item->id;
                    $option->save();
                }
            }

            $optionItemMap = $question->options->pluck('answer_set_item_id', 'id');
            AnswerKey::where('question_id', $question->id)->get()->each(function (AnswerKey $key) use ($optionItemMap) {
                if (!$key->answer_set_item_id && $optionItemMap->get($key->option_id)) {
                    $key->answer_set_item_id = $optionItemMap->get($key->option_id);
                    $key->save();
                }
            });

            return $answerSet;
        });
    }
}
