@extends('dashboard.mt.main', ['page' => $page])

@section('content')
    @include('assessments::admin.assessments.partials.alerts')
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h3 class="card-title">Topics</h3>
            <a href="{{ route('dashboard.assessments.topics.create') }}" class="btn btn-primary">New Topic</a>
        </div>
        <div class="card-body p-0">
            <table class="table table-striped mb-0">
                <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Slug</th>
                    <th>Active</th>
                    <th>Order</th>
                    <th style="width: 180px"></th>
                </tr>
                </thead>
                <tbody>
                @foreach($items as $t)
                    <tr>
                        <td>{{ $t->id }}</td>
                        <td>{{ $t->name }}</td>
                        <td>{{ $t->slug }}</td>
                        <td>{{ $t->is_active ? 'Yes' : 'No' }}</td>
                        <td>{{ $t->position }}</td>
                        <td class="text-end">
                            <a href="{{ route('dashboard.assessments.topics.edit', $t) }}" class="btn btn-sm btn-light">Edit</a>
                            <form action="{{ route('dashboard.assessments.topics.destroy', $t) }}" method="post" style="display:inline-block">
                                @csrf @method('delete')
                                <button class="btn btn-sm btn-danger" onclick="return confirm('Delete topic?')">Delete</button>
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

