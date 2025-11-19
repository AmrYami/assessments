<?php

namespace Fakeeh\Assessments\Http\Controllers\Admin;

use Fakeeh\Assessments\Support\Controller;
use Fakeeh\Assessments\Domain\Models\AnswerSet;
use Fakeeh\Assessments\Domain\Models\AnswerSetItem;
use Fakeeh\Assessments\Domain\Models\Question;
use Fakeeh\Assessments\Domain\Models\QuestionAnswer;
use Fakeeh\Assessments\Http\Requests\Admin\Api\{LinkAnswerSetItemsRequest, StoreAnswerSetRequest, UnlinkAnswerSetItemsRequest};
use Fakeeh\Assessments\Http\Resources\AnswerSetResource;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class AnswerSetApiController extends Controller
{
    public function index(Request $request)
    {
        abort_unless(config('assessments.enabled') && config('assessments.admin_only'), 404);

        $query = AnswerSet::query()->where('is_active', true)->orderBy('name');
        if ($term = $request->string('q')->toString()) {
            $like = '%' . $term . '%';
            $query->where(function ($q) use ($like) {
                $q->where('name', 'like', $like)->orWhere('slug', 'like', $like);
            });
        }
        if ($request->filled('only')) {
            $ids = Arr::wrap($request->input('only'));
            $query->whereIn('id', $ids);
        }
        if ($request->boolean('with_items', true)) {
            $query->with(['items' => function ($items) {
                $items->whereNull('deleted_at')->orderBy('position');
            }]);
        }

        $sets = $query->limit(50)->get();

        return AnswerSetResource::collection($sets);
    }

    public function store(StoreAnswerSetRequest $request)
    {
        abort_unless(config('assessments.enabled') && config('assessments.admin_only'), 404);
        $data = $request->validated();

        $set = AnswerSet::create([
            'name' => $data['name'],
            'slug' => $data['slug'],
            'description' => $data['description'] ?? null,
            'is_active' => true,
        ]);

        $position = 1;
        foreach ($data['items'] as $item) {
            $set->items()->create([
                'label' => $item['label'],
                'value' => Arr::get($item, 'value'),
                'position' => $position++,
                'is_active' => true,
            ]);
        }

        $set->load(['items' => fn($q) => $q->whereNull('deleted_at')->orderBy('position')]);

        return (new AnswerSetResource($set))
            ->response()
            ->setStatusCode(201);
    }

    public function link(LinkAnswerSetItemsRequest $request, Question $question)
    {
        abort_unless(config('assessments.enabled') && config('assessments.admin_only'), 404);
        $data = $request->validated();

        $existingMax = (int) QuestionAnswer::where('question_id', $question->id)->max('position');
        $position = $existingMax + 1;
        foreach ($data['items'] as $item) {
            QuestionAnswer::updateOrCreate(
                [
                    'question_id' => $question->id,
                    'answer_set_item_id' => (int) $item['id'],
                ],
                [
                    'position' => $position++,
                    'is_active' => (bool) Arr::get($item, 'is_active', true),
                    'is_correct' => (bool) Arr::get($item, 'is_correct', false),
                    'label_override' => Arr::get($item, 'label_override'),
                    'value_override' => Arr::get($item, 'value_override'),
                ]
            );
        }

        $this->refreshQuestionSchema($question);

        return response()->json(['linked' => true]);
    }

    public function unlink(UnlinkAnswerSetItemsRequest $request, Question $question)
    {
        abort_unless(config('assessments.enabled') && config('assessments.admin_only'), 404);
        $data = $request->validated();
        QuestionAnswer::where('question_id', $question->id)
            ->whereIn('answer_set_item_id', $data['item_ids'])
            ->delete();

        $this->refreshQuestionSchema($question);

        return response()->json(['unlinked' => true]);
    }

    protected function refreshQuestionSchema(Question $question): void
    {
        try {
            $hash = app(\Fakeeh\Assessments\Services\SchemaHashService::class)->computeForQuestion($question->fresh());
            $question->schema_hash = $hash;
            $question->save();
        } catch (\Throwable $e) {
            // ignore
        }
    }
}
