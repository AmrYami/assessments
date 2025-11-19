<?php

namespace Fakeeh\Assessments\Http\Controllers\Admin;

use Fakeeh\Assessments\Support\Controller;
use Fakeeh\Assessments\Domain\Models\Topic;
use Fakeeh\Assessments\Http\Requests\Admin\{StoreTopicRequest, UpdateTopicRequest};

class TopicController extends Controller
{
    public function index()
    {
        abort_unless(config('assessments.enabled') && config('assessments.admin_only'), 404);
        $page = 'Assessments — Topics';
        $items = Topic::orderBy('position')->paginate(20);
        return view('admin.assessments.topics.index', compact('page', 'items'));
    }

    public function create()
    {
        abort_unless(config('assessments.enabled') && config('assessments.admin_only'), 404);
        $page = 'Assessments — New Topic';
        return view('admin.assessments.topics.create', compact('page'));
    }

    public function store(StoreTopicRequest $request)
    {
        $data = $request->validated();
        $data['is_active'] = (bool)($data['is_active'] ?? true);
        $data['position'] = $data['position'] ?? 0;
        Topic::create($data);
        return redirect()->route('dashboard.assessments.topics.index')->with('success', 'Topic created');
    }

    public function edit(Topic $topic)
    {
        abort_unless(config('assessments.enabled') && config('assessments.admin_only'), 404);
        $page = 'Assessments — Edit Topic';
        return view('admin.assessments.topics.edit', compact('page','topic'));
    }

    public function update(UpdateTopicRequest $request, Topic $topic)
    {
        $data = $request->validated();
        $topic->update($data);
        return redirect()->route('dashboard.assessments.topics.index')->with('success', 'Topic updated');
    }

    public function destroy(Topic $topic)
    {
        $topic->delete();
        return redirect()->route('dashboard.assessments.topics.index')->with('success', 'Topic deleted');
    }
}
