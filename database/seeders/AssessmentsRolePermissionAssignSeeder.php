<?php

namespace Fakeeh\Assessments\Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class AssessmentsRolePermissionAssignSeeder extends Seeder
{
    public function run(): void
    {
        $guardName = config('auth.defaults.guard', 'web');

        $userRole = Role::where('name', 'User')->where('guard_name', $guardName)->first();
        $hrRole   = Role::where('name', 'HR')->where('guard_name', $guardName)->first();

        if (!$hrRole) {
            $this->command->warn("Role 'HR' (guard: {$guardName}) not found. Create it first, then re-run this seeder.");
        }
        if (!$userRole) {
            $this->command->warn("Role 'User' (guard: {$guardName}) not found. Create it first, then re-run this seeder.");
        }

        // Admin prefixes for assessments
        $adminPrefixes = [
            'exams.topics',
            'exams.questions',
            'exams.exams',
            'exams.reports',
            'exams.answers',
            'exams.answersets',
            'exams.propagate',
            'exams.presets',
            'exams.reviews',
        ];

        $adminPerms = Permission::query()
            ->where('guard_name', $guardName)
            ->where(function ($q) use ($adminPrefixes) {
                foreach ($adminPrefixes as $p) {
                    $q->orWhere('name', 'like', $p . '.%');
                }
            })
            ->pluck('name')
            ->all();

        if ($hrRole && $adminPerms) {
            $hrRole->givePermissionTo($adminPerms);
            $this->command->info("Assigned ".count($adminPerms)." Assessments permissions to role 'HR'.");
        }

        // Candidate permissions for User role
        if ($userRole) {
            $userPerms = ['exams.attempts.start','exams.attempts.view_result','exams.attempts.answer','exams.attempts.submit'];
            // ensure permissions exist
            $existing = Permission::where('guard_name', $guardName)->pluck('name')->all();
            $userPerms = array_values(array_intersect($userPerms, $existing));
            if ($userPerms) {
                $userRole->givePermissionTo($userPerms);
                $this->command->info("Assigned ".count($userPerms)." Assessments candidate permissions to role 'User'.");
            }
        }
    }
}
