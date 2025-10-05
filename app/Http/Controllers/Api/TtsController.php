<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use App\Helper;

class TtsController extends Controller
{
    private $apiBaseUrl = 'https://genaipro.vn/api/v1';

    public function generate(Request $request)
    {
        // Validate the request
        $request->validate([
            'input' => 'required|string|max:5000',
            'voice_id' => 'required|string',
            'model_id' => 'required|string|in:eleven_multilingual_v2,eleven_turbo_v2_5,eleven_flash_v2_5,eleven_v3',
            'style' => 'nullable|numeric|between:0,1',
            'speed' => 'nullable|numeric|between:0.7,1.2',
            'use_speaker_boost' => 'nullable|boolean',
            'similarity' => 'nullable|numeric|between:0,1',
            'stability' => 'nullable|numeric|between:0,1',
            'call_back_url' => 'nullable|url',
        ]);

        // Check if API key is configured
        if (!Helper::hasSevenLabsApiKey()) {
            return redirect()->back()->with('error_message', 
                'SevenLabs API key is not configured. Please contact administrator.'
            );
        }

        try {
            // Get current user
            $user = auth()->user();
            if (!$user) {
                return redirect()->back()->with('error_message', 
                    'User not authenticated. Please log in and try again.'
                );
            }

            // Calculate estimated credits based on character count
            $textLength = strlen($request->input);
            $estimatedCredits = $this->calculateEstimatedCredits($textLength);
            
            // Check user has sufficient credits for estimated usage
            if ($user->credits < $estimatedCredits) {
                return redirect()->back()->with('error_message', 
                    "Insufficient credits. You need at least {$estimatedCredits} credits for this text ({$textLength} characters). You have {$user->credits} credits."
                );
            }

            // Pre-deduct estimated credits from user
            $user->credits -= $estimatedCredits;
            $user->save();
            
            \Log::info("TTS Generation - Pre-deducted estimated credits", [
                'user_id' => $user->id,
                'text_length' => $textLength,
                'estimated_credits' => $estimatedCredits,
                'remaining_credits' => $user->credits
            ]);

            // Prepare the request data
            $requestData = [
                'input' => $request->input,
                'voice_id' => $request->voice_id,
                'model_id' => $request->model_id,
                'style' => $request->input('style', 0.0),
                'speed' => $request->input('speed', 1.0),
                'use_speaker_boost' => $request->input('use_speaker_boost', false),
                'similarity' => $request->input('similarity', 0.75),
                'stability' => $request->input('stability', 0.5),
            ];

            // Add callback URL if provided, otherwise use default
            if ($request->call_back_url) {
                $requestData['call_back_url'] = $request->call_back_url;
            } else {
                // Generate callback URL using current host
                $requestData['call_back_url'] = url('/api/tts/callback');
            }

            // Make API call to GenAI Pro with extended timeout and retry
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . Helper::getSevenLabsApiKey(),
                'Content-Type' => 'application/json',
            ])->timeout(120)->connectTimeout(30)->retry(2, 1000)->post($this->apiBaseUrl . '/labs/task', $requestData);

            if ($response->successful()) {
                $responseData = $response->json();

                // Handle the response format: {"task_id": "task-uuid"}
                if (isset($responseData['task_id'])) {
                    // Store task info for credit confirmation later
                    $this->storeTaskInfo($responseData['task_id'], $user->id, $estimatedCredits, $textLength);

                    // Task created successfully, redirect with success message
                    return redirect()->back()->with('success_message', 
                        "TTS generation started successfully! Estimated credits: {$estimatedCredits}. Remaining credits: {$user->credits}. Task ID: {$responseData['task_id']}"
                    );
                } else {
                    return redirect()->back()->with('error_message', 
                        'Unexpected response format from API. Please try again.'
                    );
                }
            } else {
                $errorMessage = 'API request failed';
                if ($response->json() && isset($response->json()['message'])) {
                    $errorMessage = $response->json()['message'];
                } elseif ($response->json() && isset($response->json()['error'])) {
                    $errorMessage = $response->json()['error'];
                }

                return redirect()->back()->with('error_message', $errorMessage);
            }

        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            \Log::error('TTS Generation Connection Error: ' . $e->getMessage());

            return redirect()->back()->with('error_message', 
                'Connection timeout. The API is taking longer than expected. Please try again in a few moments.'
            );
        } catch (\Illuminate\Http\Client\RequestException $e) {
            \Log::error('TTS Generation Request Error: ' . $e->getMessage());

            return redirect()->back()->with('error_message', 
                'Request failed. Please check your input and try again.'
            );
        } catch (\Exception $e) {
            \Log::error('TTS Generation Error: ' . $e->getMessage());

            // Check if it's a timeout error
            if (strpos($e->getMessage(), 'timeout') !== false || strpos($e->getMessage(), 'Connection timed out') !== false) {
                return redirect()->back()->with('error_message', 
                    'Task timeout - please try again later. The server is currently busy processing requests.'
                );
            }

            return redirect()->back()->with('error_message', 
                'An error occurred while generating speech: ' . $e->getMessage()
            );
        }
    }

    public function getTask($taskId)
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . Helper::getSevenLabsApiKey(),
                'Content-Type' => 'application/json',
            ])->get($this->apiBaseUrl . '/labs/task/' . $taskId);

            if ($response->successful()) {
                $taskData = $response->json();

                return response()->json([
                    'success' => true,
                    'task' => $taskData
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to fetch task details',
                    'status_code' => $response->status()
                ], $response->status());
            }

        } catch (\Exception $e) {
            \Log::error('TTS Get Task Error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error fetching task: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getTasks(Request $request)
    {
        try {
            $page = $request->input('page', 1);
            $limit = $request->input('limit', 20);

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . Helper::getSevenLabsApiKey(),
                'Content-Type' => 'application/json',
            ])->get($this->apiBaseUrl . '/labs/task', [
                'page' => $page,
                'limit' => $limit
            ]);

            if ($response->successful()) {
                $data = $response->json();

                return response()->json([
                    'success' => true,
                    'data' => $data
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to fetch tasks',
                    'status_code' => $response->status()
                ], $response->status());
            }

        } catch (\Exception $e) {
            \Log::error('TTS Get Tasks Error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error fetching tasks: ' . $e->getMessage()
            ], 500);
        }
    }

    public function deleteTask($taskId)
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . Helper::getSevenLabsApiKey(),
                'Content-Type' => 'application/json',
            ])->delete($this->apiBaseUrl . '/labs/task/' . $taskId);

            if ($response->successful()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Task deleted successfully'
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to delete task',
                    'status_code' => $response->status()
                ], $response->status());
            }

        } catch (\Exception $e) {
            \Log::error('TTS Delete Task Error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error deleting task: ' . $e->getMessage()
            ], 500);
        }
    }

    public function exportSubtitle($taskId, Request $request)
    {
        try {
            $requestData = [
                'max_characters_per_line' => $request->input('max_characters_per_line', 40),
                'max_lines_per_cue' => $request->input('max_lines_per_cue', 2),
                'max_seconds_per_cue' => $request->input('max_seconds_per_cue', 5)
            ];

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . Helper::getSevenLabsApiKey(),
                'Content-Type' => 'application/json',
            ])->post($this->apiBaseUrl . '/labs/task/subtitle/' . $taskId, $requestData);

            if ($response->successful()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Subtitle export initiated successfully'
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to export subtitle',
                    'status_code' => $response->status()
                ], $response->status());
            }

        } catch (\Exception $e) {
            \Log::error('TTS Export Subtitle Error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error exporting subtitle: ' . $e->getMessage()
            ], 500);
        }
    }

    public function callback(Request $request)
    {
        try {
            // Log the callback for debugging
            \Log::info('TTS Callback received:', $request->all());

            // Validate callback payload
            $request->validate([
                'id' => 'required|string',
                'input' => 'required|string',
                'result' => 'required|url',
                'subtitle' => 'nullable|url',
                'error' => 'nullable|string'
            ]);

            $taskId = $request->input('id');
            $result = $request->input('result');
            $subtitle = $request->input('subtitle');
            $error = $request->input('error');

            if ($error) {
                \Log::error('TTS Task failed: ' . $error, ['task_id' => $taskId]);

                return response()->json([
                    'success' => false,
                    'message' => 'Task failed: ' . $error
                ], 400);
            }

            // Store the result (you might want to save this to database)
            // For now, we'll just log it
            \Log::info('TTS Task completed successfully', [
                'task_id' => $taskId,
                'result_url' => $result,
                'subtitle_url' => $subtitle
            ]);

            // Get system credits to calculate actual usage
            $systemCreditsAfter = $this->getSystemCredits();
            $taskInfo = \Cache::get("tts_task_{$taskId}");
            
            if ($taskInfo && $systemCreditsAfter !== null) {
                // Calculate actual credits used by comparing system credits
                $systemCreditsBefore = $this->getSystemCreditsBeforeTask($taskId);
                $actualCreditsUsed = 0;
                
                if ($systemCreditsBefore !== null) {
                    $actualCreditsUsed = max(0, $systemCreditsBefore - $systemCreditsAfter);
                }
                
                // Confirm credit usage and refund if necessary
                $this->confirmCreditUsage($taskId, $actualCreditsUsed);
            }

            // You can store this in database, send notifications, etc.
            // Example: Store in cache for immediate access
            \Cache::put('tts_result_' . $taskId, [
                'task_id' => $taskId,
                'result' => $result,
                'subtitle' => $subtitle,
                'completed_at' => now()
            ], 3600); // Cache for 1 hour

            return response()->json([
                'success' => true,
                'message' => 'Callback processed successfully'
            ]);

        } catch (\Exception $e) {
            \Log::error('TTS Callback Error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error processing callback: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getVoices()
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . Helper::getSevenLabsApiKey(),
                'Content-Type' => 'application/json',
            ])->get($this->apiBaseUrl . '/labs/voices');

            if ($response->successful()) {
                return response()->json([
                    'success' => true,
                    'voices' => $response->json()
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to fetch voices'
                ], $response->status());
            }

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching voices: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getLocalVoices()
    {
        try {
            $jsonPath = public_path('voices/page_1.json');

            if (!file_exists($jsonPath)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Voices file not found'
                ], 404);
            }

            $jsonContent = file_get_contents($jsonPath);
            $voicesData = json_decode($jsonContent, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid JSON format in voices file'
                ], 500);
            }

            return response()->json([
                'success' => true,
                'data' => $voicesData
            ]);

        } catch (\Exception $e) {
            \Log::error('TTS Get Local Voices Error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error loading voices: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getModels()
    {
        return response()->json([
            'success' => true,
            'models' => [
                [
                    'id' => 'eleven_multilingual_v2',
                    'name' => 'Eleven Multilingual v2',
                    'description' => 'Supports multiple languages'
                ],
                [
                    'id' => 'eleven_turbo_v2_5',
                    'name' => 'Eleven Turbo v2.5',
                    'description' => 'Fast generation with good quality'
                ],
                [
                    'id' => 'eleven_flash_v2_5',
                    'name' => 'Eleven Flash v2.5',
                    'description' => 'Ultra-fast generation'
                ],
                [
                    'id' => 'eleven_v3',
                    'name' => 'Eleven v3',
                    'description' => 'Latest model with best quality'
                ]
            ]
        ]);
    }

    public function getMe()
    {
        try {
            $apiKey = Helper::getSevenLabsApiKey();

            if (!$apiKey) {
                return response()->json([
                    'success' => false,
                    'message' => 'SevenLabs API key not configured'
                ], 500);
            }

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type' => 'application/json'
            ])->get($this->apiBaseUrl . '/me');

            if ($response->successful()) {
                $userData = $response->json();

                // Calculate total credits from all credit entries
                $totalCredits = 0;
                if (isset($userData['credits']) && is_array($userData['credits'])) {
                    foreach ($userData['credits'] as $credit) {
                        if (isset($credit['amount'])) {
                            $totalCredits += $credit['amount'];
                        }
                    }
                }

                return response()->json([
                    'success' => true,
                    'data' => [
                        'id' => $userData['id'] ?? null,
                        'username' => $userData['username'] ?? null,
                        'balance' => $userData['balance'] ?? 0,
                        'total_credits' => $totalCredits,
                        'credits' => $userData['credits'] ?? []
                    ]
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to fetch user data: ' . $response->body()
                ], $response->status());
            }

        } catch (\Exception $e) {
            \Log::error('TTS Get Me Error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error fetching user data: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getUserCredits()
    {
        try {
            $user = auth()->user();

            // Debug logging
            \Log::info('getUserCredits called', [
                'user_id' => $user ? $user->id : 'null',
                'user_balance' => $user ? $user->balance : 'null',
                'user_credits' => $user ? $user->credits : 'null'
            ]);

            return response()->json([
                'success' => true,
                'user_balance' => $user->balance ?? 0,
                'user_credits' => $user->credits ?? 0,
                'debug_user_id' => $user ? $user->id : null
            ]);

        } catch (\Exception $e) {
            \Log::error('Get User Credits Error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error loading credits: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get system credits from GenAI Pro API
     */
    private function getSystemCredits()
    {
        try {
            $apiKey = Helper::getSevenLabsApiKey();
            if (!$apiKey) {
                return null;
            }

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type' => 'application/json'
            ])->timeout(10)->get($this->apiBaseUrl . '/me');

            if ($response->successful()) {
                $userData = $response->json();

                // Calculate total credits from all credit entries
                $totalCredits = 0;
                if (isset($userData['credits']) && is_array($userData['credits'])) {
                    foreach ($userData['credits'] as $credit) {
                        if (isset($credit['amount'])) {
                            $totalCredits += $credit['amount'];
                        }
                    }
                }

                return $totalCredits;
            }

            return null;
        } catch (\Exception $e) {
            \Log::error('Get System Credits Error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Calculate estimated credits based on character count
     * Rate: 1 credit per character
     */
    private function calculateEstimatedCredits($textLength)
    {
        $creditsPerCharacter = 1; // 1 credit per character
        $minimumCredits = 1; // Minimum 1 credit per request
        
        $estimatedCredits = max($minimumCredits, $textLength * $creditsPerCharacter);
        
        \Log::info("Credit calculation", [
            'text_length' => $textLength,
            'credits_per_character' => $creditsPerCharacter,
            'estimated_credits' => $estimatedCredits
        ]);
        
        return $estimatedCredits;
    }

    /**
     * Store task information for credit confirmation
     */
    private function storeTaskInfo($taskId, $userId, $estimatedCredits, $textLength)
    {
        // Get system credits before task for comparison
        $systemCreditsBefore = $this->getSystemCredits();
        
        // Store in cache for later confirmation
        $taskInfo = [
            'task_id' => $taskId,
            'user_id' => $userId,
            'estimated_credits' => $estimatedCredits,
            'text_length' => $textLength,
            'system_credits_before' => $systemCreditsBefore,
            'created_at' => now(),
            'status' => 'pending_confirmation'
        ];
        
        \Cache::put("tts_task_{$taskId}", $taskInfo, 3600); // Store for 1 hour
        
        \Log::info("Task info stored for credit confirmation", $taskInfo);
    }

    /**
     * Confirm actual credit usage and refund if necessary
     */
    private function confirmCreditUsage($taskId, $actualCreditsUsed)
    {
        $taskInfo = \Cache::get("tts_task_{$taskId}");
        
        if (!$taskInfo) {
            \Log::warning("Task info not found for credit confirmation", ['task_id' => $taskId]);
            return;
        }
        
        $user = \App\Models\User::find($taskInfo['user_id']);
        if (!$user) {
            \Log::warning("User not found for credit confirmation", ['user_id' => $taskInfo['user_id']]);
            return;
        }
        
        $estimatedCredits = $taskInfo['estimated_credits'];
        $difference = $estimatedCredits - $actualCreditsUsed;
        
        if ($difference > 0) {
            // Refund excess credits
            $user->credits += $difference;
            $user->save();
            
            \Log::info("Credits refunded to user", [
                'user_id' => $user->id,
                'task_id' => $taskId,
                'estimated_credits' => $estimatedCredits,
                'actual_credits' => $actualCreditsUsed,
                'refunded_credits' => $difference,
                'final_credits' => $user->credits
            ]);
        } elseif ($difference < 0) {
            // Additional credits needed (shouldn't happen with pre-deduction)
            \Log::warning("Additional credits needed", [
                'user_id' => $user->id,
                'task_id' => $taskId,
                'estimated_credits' => $estimatedCredits,
                'actual_credits' => $actualCreditsUsed,
                'additional_needed' => abs($difference)
            ]);
        }
        
        // Remove task info from cache
        \Cache::forget("tts_task_{$taskId}");
    }

    /**
     * Get system credits before task (stored in task info)
     */
    private function getSystemCreditsBeforeTask($taskId)
    {
        $taskInfo = \Cache::get("tts_task_{$taskId}");
        return $taskInfo['system_credits_before'] ?? null;
    }
}
