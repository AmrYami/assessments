<?php

namespace Amryami\Assessments\Http\Requests\Admin;

use Illuminate\Validation\Rule;

class UpdatePresetRequest extends StorePresetRequest
{
    public function rules(): array
    {
        $rules = parent::rules();

        unset($rules['slug']);

        return $rules;
    }
}
