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
            return response()->json([
                'success' => false,
                'message' => 'SevenLabs API key is not configured. Please contact administrator.'
            ], 500);
        }

        try {
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

            // Add callback URL if provided
            if ($request->call_back_url) {
                $requestData['call_back_url'] = $request->call_back_url;
            }

            // Make API call to GenAI Pro
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . Helper::getSevenLabsApiKey(),
                'Content-Type' => 'application/json',
            ])->timeout(60)->post($this->apiBaseUrl . '/labs/task', $requestData);

            if ($response->successful()) {
                $responseData = $response->json();

                // Handle different response formats
                if (isset($responseData['audio_url'])) {
                    // Direct audio URL provided
                    return response()->json([
                        'success' => true,
                        'audio_url' => $responseData['audio_url'],
                        'task_id' => $responseData['task_id'] ?? null,
                        'message' => 'Speech generated successfully'
                    ]);
                } elseif (isset($responseData['task_id'])) {
                    // Task created, need to poll for completion
                    return $this->handleAsyncTask($responseData['task_id']);
                } else {
                    return response()->json([
                        'success' => false,
                        'message' => 'Unexpected response format from API'
                    ], 500);
                }
            } else {
                $errorMessage = 'API request failed';
                if ($response->json() && isset($response->json()['message'])) {
                    $errorMessage = $response->json()['message'];
                } elseif ($response->json() && isset($response->json()['error'])) {
                    $errorMessage = $response->json()['error'];
                }

                return response()->json([
                    'success' => false,
                    'message' => $errorMessage,
                    'status_code' => $response->status()
                ], $response->status());
            }

        } catch (\Exception $e) {
            \Log::error('TTS Generation Error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while generating speech: ' . $e->getMessage()
            ], 500);
        }
    }

    private function handleAsyncTask($taskId)
    {
        // For async tasks, we'll implement polling
        // This is a simplified version - in production, you might want to use queues
        $maxAttempts = 30; // 30 seconds max wait
        $attempt = 0;

        while ($attempt < $maxAttempts) {
            try {
                $response = Http::withHeaders([
                    'Authorization' => 'Bearer ' . Helper::getSevenLabsApiKey(),
                    'Content-Type' => 'application/json',
                ])->get($this->apiBaseUrl . '/labs/task/' . $taskId);

                if ($response->successful()) {
                    $taskData = $response->json();

                    if (isset($taskData['status'])) {
                        if ($taskData['status'] === 'completed' && isset($taskData['audio_url'])) {
                            return response()->json([
                                'success' => true,
                                'audio_url' => $taskData['audio_url'],
                                'task_id' => $taskId,
                                'message' => 'Speech generated successfully'
                            ]);
                        } elseif ($taskData['status'] === 'failed') {
                            return response()->json([
                                'success' => false,
                                'message' => 'Task failed: ' . ($taskData['error'] ?? 'Unknown error')
                            ], 500);
                        }
                    }
                }

                sleep(1); // Wait 1 second before next attempt
                $attempt++;

            } catch (\Exception $e) {
                \Log::error('TTS Task Polling Error: ' . $e->getMessage());
                break;
            }
        }

        return response()->json([
            'success' => false,
            'message' => 'Task timeout - please try again later'
        ], 408);
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
}
