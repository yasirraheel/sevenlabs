<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PushNotificationsController;
use App\Http\Controllers\Api\TtsController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});

Route::post('device/register', [PushNotificationsController::class, 'registerDevice']);
Route::get('device/delete', [PushNotificationsController::class, 'deleteDevice']);

// TTS API Routes
Route::prefix('tts')->group(function () {
    // Generate TTS
    Route::post('generate', [TtsController::class, 'generate']);
    
    // Task Management
    Route::get('task/{taskId}', [TtsController::class, 'getTask']);
    Route::get('tasks', [TtsController::class, 'getTasks']);
    Route::delete('task/{taskId}', [TtsController::class, 'deleteTask']);
    Route::post('task/{taskId}/subtitle', [TtsController::class, 'exportSubtitle']);
    
    // Callback endpoint (no auth required)
    Route::post('callback', [TtsController::class, 'callback']);
    
    // Voice and Model info
    Route::get('voices', [TtsController::class, 'getVoices']);
    Route::get('voices/local', [TtsController::class, 'getLocalVoices']);
    Route::get('models', [TtsController::class, 'getModels']);
});
