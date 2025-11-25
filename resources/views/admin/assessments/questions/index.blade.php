@extends('dashboard.mt.main', ['page' => $page])

@section('content')
    @include('assessments::admin.assessments.partials.alerts')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3 class="mb-0">Questions</h3>
        <a href="{{ route('dashboard.assessments.questions.create') }}" class="btn btn-primary">New Question</a>
    </div>

    <form method="get" class="card mb-4">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Topic</label>
                    <select name="topic_id" class="form-select">
                        <option value="">All</option>
                        @foreach($topics as $t)
                            <option value="{{ $t->id }}" @selected(request('topic_id')==$t->id)>{{ $t->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Difficulty</label>
                    <select name="difficulty" class="form-select">
                        <option value="">All</option>
                        @foreach(['easy','medium','hard','very_hard'] as $d)
                            <option value="{{ $d }}" @selected(request('difficulty')==$d)>{{ ucfirst(str_replace('_',' ', $d)) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Active</label>
                    <select name="active" class="form-select">
                        <option value="">All</option>
                        <option value="1" @selected(request('active')==='1')>Active</option>
                        <option value="0" @selected(request('active')==='0')>Inactive</option>
                    </select>
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <button class="btn btn-light">Filter</button>
                </div>
            </div>
        </div>
    </form>

    <div class="card">
        <div class="card-body p-0">
            <table class="table table-striped mb-0">
                <thead>
                <tr>
                    <th>ID</th>
                    <th>Slug</th>
                    <th>Weight</th>
                    <th>Mode</th>
                    <th>Difficulty</th>
                    <th>Active</th>
                    <th style="width: 180px"></th>
                </tr>
                </thead>
                <tbody>
                @foreach($items as $q)
                    <tr>
                        <td>{{ $q->id }}</td>
                        <td>{{ $q->slug }}</td>
                        <td>{{ $q->weight }}</td>
                        <td>{{ $q->selection_mode }}</td>
                        <td>{{ $q->difficulty }}</td>
                        <td>{{ $q->is_active ? 'Yes' : 'No' }}</td>
                        <td class="text-end">
                            <a href="{{ route('dashboard.assessments.questions.edit', $q) }}" class="btn btn-sm btn-light">Edit</a>
                            <form action="{{ route('dashboard.assessments.questions.destroy', $q) }}" method="post" style="display:inline-block">
                                @csrf @method('delete')
                                <button class="btn btn-sm btn-danger" onclick="return confirm('Delete question?')">Delete</button>
                            </form>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        <div class="card-footer">{{ $items->links() }}</div>
    </div>
@endsection

