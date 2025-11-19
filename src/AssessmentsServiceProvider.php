<?php

namespace Fakeeh\Assessments;

use Fakeeh\Assessments\Console\Commands\{BackfillSchemaHash, FinalizeExpiredAttempts, ExportExamReports, RebuildAnswerUsage, SendAttemptReminders};
use Fakeeh\Assessments\Contracts\ReviewServiceInterface;
use Fakeeh\Assessments\Domain\Models\{AnswerSet, Attempt, Exam, InputPreset, Question, Topic};
use Fakeeh\Assessments\Services\AttemptReviewService;
use Fakeeh\Assessments\Services\SchemaHashService;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class AssessmentsServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('assessments')
            ->hasConfigFile()
            ->hasViews('assessments')
            ->hasRoutes([
                'assessments_web',
                'assessments_api',
                'assessments_candidate_api',
                'assessments_candidate_web',
            ])
            ->hasMigrations()
            ->hasCommands([
                BackfillSchemaHash::class,
                FinalizeExpiredAttempts::class,
                ExportExamReports::class,
                RebuildAnswerUsage::class,
                SendAttemptReminders::class,
            ]);
    }

    public function packageBooted(): void
    {
        // Ensure long key support by default (older MySQL)
        Schema::defaultStringLength(191);

        if ($this->app->runningInConsole()) {
            $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
        }

        // Merge default config values if host has not published them yet.
        $this->mergeConfigFrom(__DIR__ . '/../config/assessments.php', 'assessments');

        $this->registerSchemaHashObservers();
        $this->registerRouteModelBindings();
    }

    public function registeringPackage(): void
    {
        // Optionally allow host apps to override config namespace.
        Config::set('assessments.package_loaded', true);

        $this->app->singleton(ReviewServiceInterface::class, AttemptReviewService::class);
    }

    protected function registerSchemaHashObservers(): void
    {
        static $registered = false;
        if ($registered) {
            return;
        }
        $registered = true;

        Question::saving(function (Question $question) {
            try {
                $question->schema_hash = app(SchemaHashService::class)->computeForQuestion($question);
            } catch (\Throwable $e) {
                // silently ignore hashing failures; persistence continues
            }
        });

        Exam::saving(function (Exam $exam) {
            try {
                $exam->schema_hash = app(SchemaHashService::class)->computeForExam($exam);
            } catch (\Throwable $e) {
                // silently ignore hashing failures; persistence continues
            }
        });
    }

    protected function registerRouteModelBindings(): void
    {
        $bindings = [
            'exam' => Exam::class,
            'attempt' => Attempt::class,
            'topic' => Topic::class,
            'question' => Question::class,
            'answer_set' => AnswerSet::class,
            'answerSet' => AnswerSet::class,
            'preset' => InputPreset::class,
        ];

        foreach ($bindings as $parameter => $class) {
            Route::bind($parameter, function ($value) use ($class) {
                $query = $class::query();
                if (in_array(SoftDeletes::class, class_uses_recursive($class))) {
                    $query = $query->withTrashed();
                }

                /** @var \Illuminate\Database\Eloquent\Model|null $model */
                $model = $query->whereKey($value)->first();
                if ($model) {
                    return $model;
                }

                throw (new \Illuminate\Database\Eloquent\ModelNotFoundException())->setModel($class, [$value]);
            });
        }
    }
}
