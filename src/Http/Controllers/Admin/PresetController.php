<?php

namespace Amryami\Assessments\Http\Controllers\Admin;

use Amryami\Assessments\Support\Controller;
use Amryami\Assessments\Domain\Models\InputPreset;
use Amryami\Assessments\Http\Requests\Admin\{StorePresetRequest, UpdatePresetRequest};
use Illuminate\Http\Request;

class PresetController extends Controller
{
    public function index(Request $request)
    {
        abort_unless(config('assessments.enabled') && config('assessments.admin_only') && config('assessments.preset_library'), 404);
        $q = InputPreset::query();
        if ($request->wantsJson()) {
            if ($request->filled('q')) {
                $term = '%'.$request->string('q')->toString().'%';
                $q->where(function($qq) use ($term){ $qq->where('label','like',$term)->orWhere('slug','like',$term); });
            }
            if ($request->filled('input_type')) $q->where('input_type', $request->string('input_type'));
            if ($request->filled('active')) $q->where('is_active', (bool)$request->integer('active'));
            $per = max(1, min(100, (int)$request->integer('per_page') ?: 20));
            $items = $q->orderBy('label')->paginate($per, ['id','slug','label','input_type','spec_json','updated_at']);
            return response()->json(['data'=>$items->items(), 'meta'=>['page'=>$items->currentPage(), 'total'=>$items->total()]]);
        }
        $page = 'Input Presets';
        $items = $q->orderByDesc('id')->paginate(20)->appends($request->query());
        return view('assessments::admin.assessments.presets.index', compact('page','items'));
    }

    public function create()
    {
        abort_unless(config('assessments.enabled') && config('assessments.admin_only') && config('assessments.preset_library'), 404);
        $page = 'New Input Preset';
        return view('assessments::admin.assessments.presets.create', compact('page'));
    }

    public function store(StorePresetRequest $request)
    {
        $data = $request->validated();
        InputPreset::create($data);
        return redirect()->route('dashboard.assessments.presets.index')->with('success','Preset created');
    }

    public function edit(InputPreset $preset)
    {
        abort_unless(config('assessments.enabled') && config('assessments.admin_only') && config('assessments.preset_library'), 404);
        $page = 'Edit Input Preset';
        return view('assessments::admin.assessments.presets.edit', compact('page','preset'));
    }

    public function update(UpdatePresetRequest $request, InputPreset $preset)
    {
        $data = $request->validated();
        $preset->update($data);
        return redirect()->route('dashboard.assessments.presets.index')->with('success','Preset updated');
    }

    public function destroy(InputPreset $preset)
    {
        $preset->delete();
        return back()->with('success','Preset deleted');
    }
}
