<?php

namespace Yami\Assessments\Support;

use Illuminate\Routing\Controller as IlluminateController;

if (class_exists(\App\Http\Controllers\BaseController::class)) {
    /**
     * Proxy to the host base controller so existing middleware aliases,
     * helpers, and traits continue to apply when the package controllers
     * are resolved inside the main application.
     */
    abstract class Controller extends \App\Http\Controllers\BaseController
    {
    }
} else {
    /**
     * Fallback to Laravel's base controller when the host project does not
     * expose a custom base controller class.
     */
    abstract class Controller extends IlluminateController
    {
    }
}
