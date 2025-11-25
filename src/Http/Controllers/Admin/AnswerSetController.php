<?php

namespace Amryami\Assessments\Http\Controllers\Admin;

use Amryami\Assessments\Support\Controller;
use Amryami\Assessments\Domain\Models\AnswerSet;
use Amryami\Assessments\Domain\Models\AnswerSetItem;
use Amryami\Assessments\Http\Requests\Admin\{StoreAnswerSetRequest, UpdateAnswerSetRequest};
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class AnswerSetController extends Controller
{
    public function index(Request $request)
    {
        abort_unless(config('assessments.enabled') && config('assessments.admin_only'), 404);
        $page = 'Answer Sets';

        $query = AnswerSet::query()->withCount(['items' => fn($q) => $q->whereNull('deleted_at')]);
        if ($request->boolean('trashed')) {
            $query->withTrashed();
        }
        if ($term = $request->string('q')->toString()) {
            $like = '%' . $term . '%';
            $query->where(function ($q) use ($like) {
                $q->where('name', 'like', $like)->orWhere('slug', 'like', $like);
            });
        }
        if ($request->filled('status')) {
            if ($request->string('status') === 'active') {
                $query->where('is_active', true);
            } elseif ($request->string('status') === 'inactive') {
                $query->where('is_active', false);
            }
        }

        $items = $query->orderByDesc('id')->paginate(20)->appends($request->query());

        return view('assessments::admin.assessments.answer_sets.index', compact('page', 'items'));
    }

    public function create()
    {
        abort_unless(config('assessments.enabled') && config('assessments.admin_only'), 404);
        $page = 'New Answer Set';
        return view('assessments::admin.assessments.answer_sets.create', compact('page'));
    }

    public function store(StoreAnswerSetRequest $request)
    {
        abort_unless(config('assessments.enabled') && config('assessments.admin_only'), 404);
        $data = $request->validated();

        $set = AnswerSet::create([
            'name' => $data['name'],
            'slug' => $data['slug'],
            'description' => $data['description'] ?? null,
            'is_active' => (bool) ($data['is_active'] ?? true),
        ]);

        $this->syncItems($set, $data['items']);

        return redirect()->route('dashboard.assessments.answer_sets.index')->with('success', 'Answer set created');
    }

    public function edit(AnswerSet $answerSet)
    {
        abort_unless(config('assessments.enabled') && config('assessments.admin_only'), 404);
        $page = 'Edit Answer Set';
        $answerSet->load(['items' => fn($q) => $q->withTrashed()->orderBy('position')]);
        return view('assessments::admin.assessments.answer_sets.edit', compact('page', 'answerSet'));
    }

    public function update(UpdateAnswerSetRequest $request, AnswerSet $answerSet)
    {
        abort_unless(config('assessments.enabled') && config('assessments.admin_only'), 404);
        $data = $request->validated();

        $answerSet->update([
            'name' => $data['name'],
            'slug' => $data['slug'],
            'description' => $data['description'] ?? null,
            'is_active' => (bool) ($data['is_active'] ?? true),
        ]);

        $this->syncItems($answerSet, $data['items']);

        return redirect()->route('dashboard.assessments.answer_sets.index')->with('success', 'Answer set updated');
    }

    public function destroy(AnswerSet $answerSet)
    {
        abort_unless(config('assessments.enabled') && config('assessments.admin_only'), 404);
        $answerSet->delete();
        $answerSet->items()->delete();
        return back()->with('success', 'Answer set archived');
    }

    public function restore(AnswerSet $answerSet)
    {
        abort_unless(config('assessments.enabled') && config('assessments.admin_only'), 404);
        $answerSet->restore();
        $answerSet->items()->withTrashed()->restore();
        return back()->with('success', 'Answer set restored');
    }

    protected function syncItems(AnswerSet $set, array $items): void
    {
        $keepIds = [];
        $position = 1;

        foreach ($items as $item) {
            $payload = [
                'label' => $item['label'],
                'value' => Arr::get($item, 'value'),
                'position' => $position++,
                'is_active' => (bool) Arr::get($item, 'is_active', true),
            ];

            if (!empty($item['id'])) {
                /** @var AnswerSetItem|null $existing */
                $existing = $set->items()->withTrashed()->find($item['id']);
                if ($existing) {
                    $existing->update($payload);
                    $existing->restore();
                    $keepIds[] = $existing->id;
                    continue;
                }
            }

            $created = $set->items()->create($payload);
            $keepIds[] = $created->id;
        }

        if (!empty($keepIds)) {
            $set->items()
                ->withTrashed()
                ->whereNotIn('id', $keepIds)
                ->delete();
        } else {
            // Should not happen due to validation, but guard anyway
            $set->items()->delete();
        }
    }
}
