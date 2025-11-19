<?php

namespace Fakeeh\Assessments\Tests\Fixtures;

class AllowMiddleware
{
    public function handle($request, $next)
    {
        return $next($request);
    }
}
