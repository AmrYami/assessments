<?php

namespace Amryami\Assessments\Support;

class ModelResolver
{
    public static function category(): string
    {
        $candidates = array_filter([
            config('assessments.models.category'),
            class_exists(\App\HR\Models\Category::class) ? \App\HR\Models\Category::class : null,
            class_exists(\App\Models\Category::class) ? \App\Models\Category::class : null,
        ]);

        foreach ($candidates as $model) {
            if ($model && class_exists($model)) {
                return $model;
            }
        }

        throw new \RuntimeException('Configure assessments.models.category to point to your Category model.');
    }

    public static function user(): string
    {
        $configured = config('assessments.models.user');
        if ($configured && class_exists($configured)) {
            return $configured;
        }

        $guard = config('auth.defaults.guard', 'web');
        $provider = config("auth.guards.{$guard}.provider");
        $providerModel = $provider ? config("auth.providers.{$provider}.model") : null;
        $candidates = array_filter([
            $providerModel,
            class_exists(\App\Models\User::class) ? \App\Models\User::class : null,
        ]);

        foreach ($candidates as $model) {
            if ($model && class_exists($model)) {
                return $model;
            }
        }

        throw new \RuntimeException('Configure assessments.models.user to point to your authenticatable User model.');
    }
}
