<?php

namespace App\Providers;

use App\Models\AcademicPeriod;
use App\Models\EducationLevel;
use App\Models\Enrollment;
use App\Models\EvaluationPlan;
use App\Models\Grade;
use App\Models\Schedule;
use App\Models\Section;
use App\Models\StudentEvaluationScore;
use App\Models\StudentGuardian;
use App\Models\StudentScore;
use App\Models\Subject;
use App\Models\SubjectArea;
use App\Models\SubjectAssignment;
use App\Models\Task;
use App\Models\TaskSubmission;
use App\Models\Term;
use App\Policies\AcademicPeriodPolicy;
use App\Policies\EducationLevelPolicy;
use App\Policies\EnrollmentPolicy;
use App\Policies\EvaluationPlanPolicy;
use App\Policies\GradePolicy;
use App\Policies\SchedulePolicy;
use App\Policies\SectionPolicy;
use App\Policies\StudentEvaluationScorePolicy;
use App\Policies\StudentGuardianPolicy;
use App\Policies\StudentScorePolicy;
use App\Policies\SubjectAreaPolicy;
use App\Policies\SubjectAssignmentPolicy;
use App\Policies\SubjectPolicy;
use App\Policies\TaskPolicy;
use App\Policies\TaskSubmissionPolicy;
use App\Policies\TermPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        AcademicPeriod::class => AcademicPeriodPolicy::class,
        EducationLevel::class => EducationLevelPolicy::class,
        Enrollment::class => EnrollmentPolicy::class,
        EvaluationPlan::class => EvaluationPlanPolicy::class,
        Grade::class => GradePolicy::class,
        Schedule::class => SchedulePolicy::class,
        Section::class => SectionPolicy::class,
        StudentEvaluationScore::class => StudentEvaluationScorePolicy::class,
        StudentGuardian::class => StudentGuardianPolicy::class,
        StudentScore::class => StudentScorePolicy::class,
        Subject::class => SubjectPolicy::class,
        SubjectArea::class => SubjectAreaPolicy::class,
        SubjectAssignment::class => SubjectAssignmentPolicy::class,
        Task::class => TaskPolicy::class,
        TaskSubmission::class => TaskSubmissionPolicy::class,
        Term::class => TermPolicy::class,
    ];

    public function boot(): void
    {
        $this->registerPolicies();
    }
}
