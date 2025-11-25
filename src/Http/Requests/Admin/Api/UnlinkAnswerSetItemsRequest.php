<?php

namespace Yami\Assessments\Http\Requests\Admin\Api;

use Yami\Assessments\Support\FormRequest;

class UnlinkAnswerSetItemsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'item_ids' => 'required|array|min:1',
            'item_ids.*' => 'integer',
        ];
    }
}
