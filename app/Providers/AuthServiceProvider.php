<?php

namespace App\Providers;

use App\Models\AcademicPeriod;
use App\Models\DirectMessage;
use App\Models\DisciplinaryRecord;
use App\Models\EducationLevel;
use App\Models\Enrollment;
use App\Models\EvaluationPlan;
use App\Models\Grade;
use App\Models\IncidentType;
use App\Models\Justification;
use App\Models\RecoveryRegistration;
use App\Models\Schedule;
use App\Models\Section;
use App\Models\StudentApplication;
use App\Models\StudentDocument;
use App\Models\StudentEvaluationScore;
use App\Models\StudentGuardian;
use App\Models\StudentPromotion;
use App\Models\StudentScore;
use App\Models\Subject;
use App\Models\SubjectArea;
use App\Models\SubjectAssignment;
use App\Models\Task;
use App\Models\TaskSubmission;
use App\Models\Term;
use App\Policies\AcademicPeriodPolicy;
use App\Policies\DirectMessagePolicy;
use App\Policies\DisciplinaryRecordPolicy;
use App\Policies\EducationLevelPolicy;
use App\Policies\EnrollmentPolicy;
use App\Policies\EvaluationPlanPolicy;
use App\Policies\GradePolicy;
use App\Policies\IncidentTypePolicy;
use App\Policies\JustificationPolicy;
use App\Policies\RecoveryRegistrationPolicy;
use App\Policies\SchedulePolicy;
use App\Policies\SectionPolicy;
use App\Policies\StudentApplicationPolicy;
use App\Policies\StudentDocumentPolicy;
use App\Policies\StudentEvaluationScorePolicy;
use App\Policies\StudentGuardianPolicy;
use App\Policies\StudentProfilePolicy;
use App\Policies\StudentPromotionPolicy;
use App\Policies\StudentScorePolicy;
use App\Policies\SubjectAreaPolicy;
use App\Policies\SubjectAssignmentPolicy;
use App\Policies\SubjectPolicy;
use App\Policies\TaskPolicy;
use App\Policies\TaskSubmissionPolicy;
use App\Policies\TermPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        AcademicPeriod::class => AcademicPeriodPolicy::class,
        DirectMessage::class => DirectMessagePolicy::class,
        DisciplinaryRecord::class => DisciplinaryRecordPolicy::class,
        EducationLevel::class => EducationLevelPolicy::class,
        IncidentType::class => IncidentTypePolicy::class,
        Justification::class => JustificationPolicy::class,
        StudentApplication::class => StudentApplicationPolicy::class,
        StudentDocument::class => StudentDocumentPolicy::class,
        Enrollment::class => EnrollmentPolicy::class,
        EvaluationPlan::class => EvaluationPlanPolicy::class,
        Grade::class => GradePolicy::class,
        RecoveryRegistration::class => RecoveryRegistrationPolicy::class,
        Schedule::class => SchedulePolicy::class,
        Section::class => SectionPolicy::class,
        StudentEvaluationScore::class => StudentEvaluationScorePolicy::class,
        StudentGuardian::class => StudentGuardianPolicy::class,
        StudentPromotion::class => StudentPromotionPolicy::class,
        StudentScore::class => StudentScorePolicy::class,
        Subject::class => SubjectPolicy::class,
        SubjectArea::class => SubjectAreaPolicy::class,
        SubjectAssignment::class => SubjectAssignmentPolicy::class,
        Task::class => TaskPolicy::class,
        TaskSubmission::class => TaskSubmissionPolicy::class,
        Term::class => TermPolicy::class,
        User::class => UserPolicy::class,
    ];

    public function boot(): void
    {
        $this->registerPolicies();

        Gate::define('view-profile', [StudentProfilePolicy::class, 'view']);
        Gate::define('update-profile', [StudentProfilePolicy::class, 'update']);
    }
}
