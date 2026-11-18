<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Course;
use App\Models\Recommendation;
use App\Services\GeminiService;
use Illuminate\Support\Facades\Log;

class GenerateRecommendations extends Command
{
    protected $signature = 'lms:generate-recommendations';
    protected $description = 'Scan grades and use AI to generate context-aware study plans for students';

    public function handle(GeminiService $gemini)
    {
        $courses = Course::where('is_published', true)->with('students.submissions', 'assignments')->get();

        foreach ($courses as $course) {
            foreach ($course->students as $student) {
                
                // 1. ANTI-SPAM FOR DEMO
                $hasRecent = Recommendation::where('user_id', $student->id)
                    ->where('created_at', '>=', now()->subHours(24))
                    ->exists();

                if ($hasRecent) {
                    continue; 
                }

                $totalPoints = 0;
                $earnedPoints = 0;
                $gradesLog = [];
                
                foreach ($course->assignments as $assignment) {
                    $sub = $student->submissions->where('assignment_id', $assignment->id)->first();
                    if ($sub && $sub->grade !== null) {
                        $totalPoints += $assignment->points;
                        $earnedPoints += $sub->grade;
                        $gradesLog[] = "{$assignment->title}: {$sub->grade}/{$assignment->points}";
                    }
                }

                if ($totalPoints === 0) continue;

                $average = ($earnedPoints / $totalPoints) * 100;

                // --- 2. CONTEXT-AWARE TRIGGERS ---
                $needsAdvice = false;
                $category = '';

                if ($average < 75) {
                    $needsAdvice = true;
                    $category = 'At-Risk Intervention';
                } elseif ($average >= 90) {
                    $needsAdvice = true;
                    $category = 'Excellence Challenge';
                }

                if ($needsAdvice) {
                    $pendingAssignments = $course->assignments->where('due_date', '>', now())
                        ->whereNotIn('id', $student->submissions->pluck('assignment_id'));

                    $missingCount = $course->assignments->where('due_date', '<', now())
                        ->whereNotIn('id', $student->submissions->pluck('assignment_id'))
                        ->count();

                    $remainingPoints = $pendingAssignments->sum('points');
                    $totalCoursePoints = $course->assignments->sum('points');

                    try {
                        $prediction = $gemini->predictStudentOutcome(
                            $student->name,
                            $course->title,
                            round($average, 1),
                            $missingCount,
                            $remainingPoints,
                            $totalCoursePoints
                        );

                        if ($prediction && isset($prediction['projected_grade_range'])) {
                            $riskLabel = $prediction['risk_level'] ?? ($average < 75 ? 'High' : 'Low');

                            Recommendation::create([
                                'user_id' => $student->id,
                                'category' => "Predictive Forecast ({$riskLabel} Risk)",
                                'recommendation_text' => "{$course->title}: Projected Final Grade is {$prediction['projected_grade_range']}. {$prediction['actionable_target']}",
                                'reasoning' => $prediction['key_factor'] ?? "Based on a current average of " . round($average, 1) . "% and {$remainingPoints} remaining points."
                            ]);
                        }
                    } catch (\Exception $e) {
                        Log::error('Gemini API Error during predictive insight: ' . $e->getMessage());

                        $projectedLow = max(0, round($average - 3));
                        $projectedHigh = min(100, round($average + 4));

                        Recommendation::create([
                            'user_id' => $student->id,
                            'category' => $average < 75 ? 'Predictive Forecast (High Risk)' : 'Predictive Forecast (Low Risk)',
                            'recommendation_text' => "{$course->title}: Projected Final Grade is {$projectedLow}% - {$projectedHigh}%. Prioritize remaining tasks ({$remainingPoints} pts) to secure a 75%+ final mark.",
                            'reasoning' => "Trajectory calculated from current average (" . round($average, 1) . "%) and {$missingCount} missing task(s)."
                        ]);
                    }
                }
            }
        }
        $this->info('Context-Aware AI Recommendations generated safely.');
    }
}