<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeminiService
{
    protected $apiKey;
    // Using 1.5 Flash: Fast, reliable, and standard for new free-tier accounts
    protected $baseUrl = 'https://generativelanguage.googleapis.com/v1/models/gemini-1.5-flash:generateContent';

    public function __construct()
    {
        $this->apiKey = env('GEMINI_API_KEY');
    }

    // =========================================================
    // 1. AI CHATBOT SYSTEM
    // =========================================================
    public function chat(string $message, string $context = '')
    {
        if (empty($this->apiKey)) {
            return "System Error: API Key is missing.";
        }

        $finalPrompt = "CONTEXT: {$context}\n\nQUESTION: {$message}";
        
        $attempts = [
            ['url' => 'https://generativelanguage.googleapis.com/v1beta/models/gemini-3-flash-preview:generateContent', 'name' => 'Gemini 3 Flash'],
            ['url' => 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent', 'name' => 'Gemini 2.5 Flash'],
            ['url' => 'https://generativelanguage.googleapis.com/v1/models/gemini-pro:generateContent', 'name' => 'Gemini Pro Stable'],
        ];

        foreach ($attempts as $attempt) {
            try {
                $response = Http::timeout(20)
                    ->withOptions([
                        'verify' => false, 
                        'curl' => [CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4]
                    ])
                    ->post("{$attempt['url']}?key={$this->apiKey}", [
                        "contents" => [["parts" => [["text" => $finalPrompt]]]]
                    ]);

                if ($response->successful()) {
                    $data = $response->json();
                    return $data['candidates'][0]['content']['parts'][0]['text'];
                }
                
                Log::warning("Model {$attempt['name']} failed with status: " . $response->status());
            } catch (\Exception $e) {
                Log::error("Connection error on {$attempt['name']}: " . $e->getMessage());
            }
        }

        return "AI Error: All model versions failed. Please check your network.";
    } // This brace was likely missing!

    // =========================================================
    // 2. BACKGROUND AI (For Student Dashboard)
    // =========================================================
    
    public function recommendCourses($student, $availableCourses, $enrolledHistory)
    {
        if (empty($this->apiKey) || $availableCourses->isEmpty()) return null;
        
        $historyList = $enrolledHistory->pluck('title')->join(', ');
        $courseList = $availableCourses->map(fn($c) => "ID:{$c->id} | {$c->title} ({$c->difficulty_level})")->join("\n");
        
        $prompt = "Act as an academic advisor. Student History: [{$historyList}] Available Courses: {$courseList} Task: Recommend exactly 3 courses. Return ONLY JSON array: [{\"id\": 1, \"reason\": \"Short reason\"}]";
        
        return $this->callGeminiForJson($prompt);
    }

    public function recommendStudyPlan($courseTitle, $currentGrade, $assignments, $lessons)
    {
        if (empty($this->apiKey)) return null;

        $assignmentList = collect($assignments)->map(fn($a) => "ID:{$a['id']} | Task: {$a['title']}")->join(", ");
        $lessonList = collect($lessons)->map(fn($l) => "ID:{$l['id']} | Material: {$l['title']}")->join(", ");

        $prompt = "
            Student is failing '{$courseTitle}' with a grade of {$currentGrade}%.
            Available Pending Assignments: [{$assignmentList}]
            Available Learning Materials: [{$lessonList}]
            Task: Pick exactly 2 items. Return ONLY raw JSON array: [{\"type\": \"assignment\" or \"lesson\", \"id\": 1, \"tip\": \"Short study tip\"}]
        ";

        return $this->callGeminiForJson($prompt);
    }

    private function callGeminiForJson($prompt)
    {
        try {
            $response = Http::timeout(30)
                ->withOptions([
                    'verify' => false, 
                    'curl' => [CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4]
                ])
                ->withHeaders(['Content-Type' => 'application/json'])
                ->post("{$this->baseUrl}?key={$this->apiKey}", [
                    'contents' => [['parts' => [['text' => $prompt]]]]
                ]);
            
            if ($response->failed()) return null;
            
            $text = $response->json()['candidates'][0]['content']['parts'][0]['text'] ?? '';
            $text = preg_replace('/^```json|```$/m', '', $text); 
            return json_decode($text, true);
        } catch (\Exception $e) {
            return null;
        }
    }
}