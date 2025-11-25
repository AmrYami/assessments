<?php

namespace Yami\Assessments\Support;

use Illuminate\Foundation\Http\FormRequest as IlluminateFormRequest;

if (class_exists(\App\Http\Requests\BaseRequest::class)) {
    /**
     * Proxy to the host base request so shared validation hooks continue to fire
     * when package controllers resolve form requests.
     */
    abstract class FormRequest extends \App\Http\Requests\BaseRequest
    {
    }
} elseif (class_exists(\App\Http\Requests\FormRequest::class)) {
    /**
     * Some applications alias their base request as FormRequest. Honour it when present.
     */
    abstract class FormRequest extends \App\Http\Requests\FormRequest
    {
    }
} else {
    /**
     * Default back to Laravel's stock form request.
     */
    abstract class FormRequest extends IlluminateFormRequest
    {
    }
}
