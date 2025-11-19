@extends('dashboard.mt.main', ['page' => $page])

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <form method="get" class="d-flex gap-2 align-items-center">
            <input type="search" name="q" value="{{ request('q') }}" class="form-control" placeholder="Search name or slug" style="max-width:240px">
            <select name="status" class="form-select" style="max-width:160px">
                <option value="">Status</option>
                <option value="active" @selected(request('status')==='active')>Active</option>
                <option value="inactive" @selected(request('status')==='inactive')>Inactive</option>
            </select>
            <div class="form-check">
                <input class="form-check-input" type="checkbox" name="trashed" value="1" id="trashed" @checked(request()->boolean('trashed'))>
                <label class="form-check-label" for="trashed">Show archived</label>
            </div>
            <button class="btn btn-outline-secondary">Filter</button>
        </form>
        <a class="btn btn-primary" href="{{ route('dashboard.assessments.answer_sets.create') }}">New Answer Set</a>
    </div>

    <div class="card">
        <div class="card-body p-0">
            <table class="table table-striped mb-0">
                <thead>
                <tr>
                    <th>Name</th>
                    <th>Slug</th>
                    <th>Items</th>
                    <th>Status</th>
                    <th style="width: 160px"></th>
                </tr>
                </thead>
                <tbody>
                @forelse($items as $set)
                    <tr>
                        <td>{{ $set->name }}</td>
                        <td>{{ $set->slug }}</td>
                        <td>{{ $set->items_count }}</td>
                        <td>
                            @if($set->trashed())
                                <span class="badge bg-secondary">Archived</span>
                            @elseif($set->is_active)
                                <span class="badge bg-success">Active</span>
                            @else
                                <span class="badge bg-warning text-dark">Inactive</span>
                            @endif
                        </td>
                        <td class="text-end">
                            @if($set->trashed())
                                <form method="post" action="{{ route('dashboard.assessments.answer_sets.restore', $set->id) }}" class="d-inline">@csrf
                                    <button class="btn btn-sm btn-outline-success">Restore</button>
                                </form>
                            @else
                                <a class="btn btn-sm btn-outline-primary" href="{{ route('dashboard.assessments.answer_sets.edit', $set) }}">Edit</a>
                                <form method="post" action="{{ route('dashboard.assessments.answer_sets.destroy', $set) }}" class="d-inline" onsubmit="return confirm('Archive this answer set?');">
                                    @csrf @method('delete')
                                    <button class="btn btn-sm btn-outline-danger">Archive</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center text-muted py-4">No answer sets found.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer">{{ $items->links() }}</div>
    </div>
@endsection
