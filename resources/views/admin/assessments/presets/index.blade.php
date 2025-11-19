@extends('dashboard.mt.main', ['page' => $page])

@section('content')
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <div>Input Presets</div>
            <a href="{{ route('dashboard.assessments.presets.create') }}" class="btn btn-primary">New</a>
        </div>
        <div class="table-responsive">
            <table class="table table-sm">
                <thead><tr><th>ID</th><th>Slug</th><th>Label</th><th>Type</th><th>Active</th><th></th></tr></thead>
                <tbody>
                @foreach($items as $p)
                    <tr>
                        <td>{{ $p->id }}</td>
                        <td>{{ $p->slug }}</td>
                        <td>{{ $p->label }}</td>
                        <td>{{ $p->input_type }}</td>
                        <td>{!! $p->is_active ? '<span class="text-success">Yes</span>' : '<span class="text-muted">No</span>' !!}</td>
                        <td class="text-end">
                            <a href="{{ route('dashboard.assessments.presets.edit', $p) }}" class="btn btn-sm btn-light">Edit</a>
                            <form action="{{ route('dashboard.assessments.presets.destroy', $p) }}" method="post" style="display:inline-block">@csrf @method('delete')
                                <button class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete?')">Delete</button>
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

