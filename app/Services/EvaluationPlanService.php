<?php

namespace App\Services;

use App\Models\EvaluationItem;
use App\Models\EvaluationPlan;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EvaluationPlanService
{
    public function queryForUser(Request $request): Builder
    {
        $query = EvaluationPlan::with(['subject', 'grade', 'section', 'term', 'items']);

        $user = Auth::user();

        if ($user->hasRole('teacher')) {
            $subjectIds = $user->subjectAssignments()->pluck('subject_id');
            $query->whereIn('subject_id', $subjectIds);
        }

        if ($user->hasRole('student')) {
            $query->whereHas('section.enrollments', function ($q) use ($user) {
                $q->where('student_id', $user->id);
            });
        }

        if ($user->hasRole('guardian')) {
            $studentIds = $user->students()->pluck('users.id');
            $query->whereHas('section.enrollments', function ($q) use ($studentIds) {
                $q->whereIn('student_id', $studentIds);
            });
        }

        if ($request->has('academic_period_id')) {
            $query->where('academic_period_id', $request->academic_period_id);
        }

        if ($request->has('term_id')) {
            $query->where('term_id', $request->term_id);
        }

        if ($request->has('subject_id')) {
            $query->where('subject_id', $request->subject_id);
        }

        if ($request->has('grade_id')) {
            $query->where('grade_id', $request->grade_id);
        }

        if ($request->has('section_id')) {
            $query->where('section_id', $request->section_id);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        return $query;
    }

    public function create(Request $request): EvaluationPlan
    {
        $validated = $request->validate([
            'academic_period_id' => 'required|exists:academic_periods,id',
            'term_id' => 'required|exists:terms,id',
            'subject_id' => 'required|exists:subjects,id',
            'grade_id' => 'required|exists:grades,id',
            'section_id' => 'nullable|exists:sections,id',
            'items' => 'required|array|min:1',
            'items.*.name' => 'required|string|max:100',
            'items.*.type' => 'required|in:exam,quiz,project,homework,participation,other',
            'items.*.evaluation_mode' => 'required|in:qualitative,quantitative',
            'items.*.weight' => 'required|numeric|min:0|max:100',
            'items.*.max_score' => 'nullable|numeric|min:0',
            'items.*.order' => 'nullable|integer|min:0',
            'items.*.evaluation_date' => 'nullable|date',
        ]);

        $this->validateWeights($validated['items']);

        $plan = EvaluationPlan::create([
            'academic_period_id' => $validated['academic_period_id'],
            'term_id' => $validated['term_id'],
            'subject_id' => $validated['subject_id'],
            'grade_id' => $validated['grade_id'],
            'section_id' => $validated['section_id'] ?? null,
            'status' => 'draft',
        ]);

        $this->saveItems($plan, $validated['items']);

        $plan->load(['subject', 'grade', 'section', 'term', 'items']);

        return $plan;
    }

    public function update(EvaluationPlan $plan, Request $request): EvaluationPlan
    {
        $validated = $request->validate([
            'items' => 'required|array|min:1',
            'items.*.name' => 'required|string|max:100',
            'items.*.type' => 'required|in:exam,quiz,project,homework,participation,other',
            'items.*.evaluation_mode' => 'required|in:qualitative,quantitative',
            'items.*.weight' => 'required|numeric|min:0|max:100',
            'items.*.max_score' => 'nullable|numeric|min:0',
            'items.*.order' => 'nullable|integer|min:0',
            'items.*.evaluation_date' => 'nullable|date',
        ]);

        $this->validateWeights($validated['items']);

        $plan->items()->delete();
        $this->saveItems($plan, $validated['items']);

        if ($plan->status === 'rejected') {
            $plan->status = 'draft';
            $plan->approved_at = null;
            $plan->approved_by = null;
            $plan->notes = null;
            $plan->save();
        }

        $plan->load(['subject', 'grade', 'section', 'term', 'items']);

        return $plan;
    }

    public function destroy(EvaluationPlan $plan): void
    {
        $plan->items()->delete();
        $plan->delete();
    }

    public function submit(EvaluationPlan $plan): EvaluationPlan
    {
        $plan->update([
            'status' => 'submitted',
            'submitted_at' => now(),
        ]);

        $plan->load(['subject', 'grade', 'section', 'term', 'items']);

        return $plan;
    }

    public function approve(EvaluationPlan $plan, ?string $notes = null): EvaluationPlan
    {
        $plan->update([
            'status' => 'approved',
            'approved_by' => Auth::id(),
            'approved_at' => now(),
            'notes' => $notes,
        ]);

        $plan->load(['subject', 'grade', 'section', 'term', 'items']);

        return $plan;
    }

    public function reject(EvaluationPlan $plan, string $notes): EvaluationPlan
    {
        $plan->update([
            'status' => 'rejected',
            'approved_by' => Auth::id(),
            'approved_at' => now(),
            'notes' => $notes,
        ]);

        $plan->load(['subject', 'grade', 'section', 'term', 'items']);

        return $plan;
    }

    public function recalculate(EvaluationPlan $plan): int
    {
        $affected = 0;

        $plan->load('items.studentScores');

        foreach ($plan->items as $item) {
            foreach ($item->studentScores as $studentScore) {
                StudentScoreCalculator::recalculate(
                    $studentScore->student_id,
                    $studentScore->subject_assignment_id,
                    $plan->term_id
                );
                $affected++;
            }
        }

        return $affected;
    }

    private function validateWeights(array $items): void
    {
        $totalWeight = collect($items)->sum('weight');

        if (abs($totalWeight - 100) > 0.01) {
            throw new \InvalidArgumentException('La suma de los pesos de los ítems debe ser exactamente 100%');
        }
    }

    private function saveItems(EvaluationPlan $plan, array $items): void
    {
        foreach ($items as $index => $item) {
            EvaluationItem::create([
                'evaluation_plan_id' => $plan->id,
                'name' => $item['name'],
                'type' => $item['type'],
                'evaluation_mode' => $item['evaluation_mode'],
                'weight' => $item['weight'],
                'max_score' => $item['max_score'] ?? null,
                'order' => $item['order'] ?? $index,
                'evaluation_date' => $item['evaluation_date'] ?? null,
            ]);
        }
    }
}
