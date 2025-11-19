@extends('dashboard.mt.main', ['page' => $page])

@section('content')
    <form method="post" action="{{ route('dashboard.assessments.answer_sets.update', $answerSet) }}" class="card" id="answer-set-form">@csrf @method('put')
        <div class="card-body">
            <div class="row mb-3">
                <div class="col-md-4">
                    <label class="form-label">Name</label>
                    <input type="text" name="name" class="form-control" value="{{ old('name', $answerSet->name) }}" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Slug</label>
                    <input type="text" name="slug" class="form-control" value="{{ old('slug', $answerSet->slug) }}" required>
                </div>
                <div class="col-md-4 d-flex align-items-center">
                    <div class="form-check mt-4">
                        <input class="form-check-input" type="checkbox" name="is_active" value="1" id="is_active" {{ old('is_active', $answerSet->is_active) ? 'checked' : '' }}>
                        <label class="form-check-label" for="is_active">Active</label>
                    </div>
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label">Description</label>
                <textarea name="description" class="form-control" rows="2">{{ old('description', $answerSet->description) }}</textarea>
            </div>

            <hr>
            <h5 class="mb-2">Items</h5>
            <div class="table-responsive">
                <table class="table table-sm align-middle" id="items-table">
                    <thead>
                    <tr>
                        <th style="width: 35%">Label</th>
                        <th style="width: 30%">Value</th>
                        <th style="width: 10%" class="text-center">Active</th>
                        <th style="width: 15%" class="text-center">Status</th>
                        <th style="width: 10%"></th>
                    </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
            <button type="button" class="btn btn-light" id="add-item">Add item</button>
        </div>
        <div class="card-footer text-end">
            <button class="btn btn-primary">Save changes</button>
        </div>
    </form>

    <template id="item-row-template">
        <tr>
            <td>
                <input type="hidden" name="__NAME__[id]" value="">
                <input type="text" class="form-control" name="__NAME__[label]" required>
            </td>
            <td><input type="text" class="form-control" name="__NAME__[value]"></td>
            <td class="text-center"><input class="form-check-input" type="checkbox" name="__NAME__[is_active]" value="1" checked></td>
            <td class="text-center">
                <span class="badge bg-success">Active</span>
            </td>
            <td class="text-end"><button type="button" class="btn btn-link text-danger p-0 remove-item">Remove</button></td>
        </tr>
    </template>

    <script>
        const tableBody = document.querySelector('#items-table tbody');
        const template = document.getElementById('item-row-template').innerHTML.trim();
        const addBtn = document.getElementById('add-item');
        // Use JS fallback to avoid Blade parsing issues with complex nested args in @json
        const baseItems = @json($answerSet->items->map(function($item){
            return [
                'id' => $item->id,
                'label' => $item->label,
                'value' => $item->value,
                'is_active' => (bool) $item->is_active,
                'trashed' => $item->trashed(),
            ];
        })->values());
        const existing = @json(old('items')) || baseItems;

        function addItemRow(pref = {}) {
            const index = tableBody.children.length;
            const rowHtml = template.replace(/__NAME__/g, `items[${index}]`);
            const wrapper = document.createElement('tbody');
            wrapper.innerHTML = rowHtml;
            const row = wrapper.firstElementChild;
            row.querySelector(`[name="items[${index}][id]"]`).value = pref.id || '';
            row.querySelector(`[name="items[${index}][label]"]`).value = pref.label || '';
            row.querySelector(`[name="items[${index}][value]"]`).value = pref.value || '';
            const activeField = row.querySelector(`[name="items[${index}][is_active]"]`);
            if (pref.is_active === false) {
                activeField.checked = false;
            }
            const statusCell = row.children[3];
            const updateStatus = () => {
                if (pref.trashed) {
                    statusCell.innerHTML = '<span class="badge bg-secondary">Archived</span>';
                } else if (activeField.checked) {
                    statusCell.innerHTML = '<span class="badge bg-success">Active</span>';
                } else {
                    statusCell.innerHTML = '<span class="badge bg-warning text-dark">Inactive</span>';
                }
            };
            updateStatus();
            activeField.addEventListener('change', updateStatus);
            row.querySelector('.remove-item').addEventListener('click', () => {
                row.remove();
                renumberRows();
            });
            tableBody.appendChild(row);
        }

        function renumberRows() {
            Array.from(tableBody.children).forEach((row, idx) => {
                row.querySelectorAll('input').forEach((input) => {
                    input.name = input.name.replace(/items\[\d+\]/, `items[${idx}]`);
                });
            });
        }

        addBtn.addEventListener('click', () => addItemRow());
        existing.forEach(item => addItemRow(item));
        if (tableBody.children.length === 0) {
            addItemRow();
        }
    </script>
@endsection
