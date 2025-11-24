<?php

namespace Streaming\Assessments\Tests;

use Streaming\Assessments\AssessmentsServiceProvider;
use Streaming\Assessments\Tests\Fixtures\AllowMiddleware;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\Concerns\InteractsWithDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Orchestra\Testbench\TestCase as OrchestraTestCase;

abstract class TestCase extends OrchestraTestCase
{
    use InteractsWithDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->refreshDatabaseSchema();

        $this->app['router']->aliasMiddleware('permission', AllowMiddleware::class);
    }

    protected function getPackageProviders($app): array
    {
        return [
            AssessmentsServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('database.default', 'testbench');

        $mysql = $app['config']->get('database.connections.mysql');
        $database = ($mysql['database'] ?? 'laravel') . '_pkg_testbench';

        DB::connection('mysql')->statement("CREATE DATABASE IF NOT EXISTS `{$database}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

        $testbenchConnection = array_merge($mysql ?? [], [
            'database' => $database,
            'prefix' => '',
        ]);

        $app['config']->set('database.connections.testbench', $testbenchConnection);
        $app['config']->set('database.connections.mysql', $testbenchConnection);

        DB::purge('mysql');
        DB::purge('testbench');

        $app['config']->set('assessments.enabled', true);
        $app['config']->set('assessments.admin_only', false);
        $app['config']->set('assessments.middleware.candidate_api', ['auth:web']);
        $app['config']->set('assessments.models.category', Fixtures\Category::class);
        $app['config']->set('assessments.models.user', Fixtures\User::class);
        $app['config']->set('activitylog.enabled', false);

        $app['config']->set('auth.defaults.guard', 'web');
        $app['config']->set('auth.guards.web', [
            'driver' => 'session',
            'provider' => 'users',
        ]);
        $app['config']->set('auth.providers.users', [
            'driver' => 'eloquent',
            'model' => Fixtures\User::class,
        ]);
    }

    protected function refreshDatabaseSchema(): void
    {
        $schema = Schema::connection('testbench');

        $tables = collect(DB::connection('testbench')->select('SHOW TABLES'))
            ->map(fn($row) => array_values((array) $row)[0] ?? null)
            ->filter(fn($name) => is_string($name) && (str_starts_with($name, 'assessment_') || in_array($name, ['categories', 'users', 'migrations'])));

        foreach ($tables as $table) {
            $schema->dropIfExists($table);
        }

        $this->artisan('migrate', [
            '--database' => 'testbench',
            '--path' => dirname(__DIR__) . '/database/migrations',
            '--realpath' => true,
        ])->run();

        $schema->dropIfExists('categories');

        $schema->create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        $schema->dropIfExists('users');

        $schema->create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('email')->unique()->nullable();
            $table->string('password')->nullable();
            $table->unsignedBigInteger('hr_category_id')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });
    }
}
