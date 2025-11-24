<?php

namespace Amryami\Assessments\Tests\Fixtures;

class AllowMiddleware
{
    public function handle($request, $next)
    {
        return $next($request);
    }
}
