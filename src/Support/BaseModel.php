<?php

namespace Fakeeh\Assessments\Support;

use Illuminate\Database\Eloquent\Model;

if (\class_exists(\App\Models\BaseModel::class)) {
    /**
     * Proxy to the host application's base model so existing behaviors
     * (activity log, translations, custom connection) continue to work
     * while the package is integrated.
     */
    abstract class BaseModel extends \App\Models\BaseModel
    {
    }
} else {
    /**
     * Fallback base model when the host application does not expose
     * a dedicated base model class.
     */
    abstract class BaseModel extends Model
    {
    }
}
