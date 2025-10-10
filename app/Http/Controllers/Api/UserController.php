<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function getUserBalance()
    {
        try {
            $user = auth()->user();

            return response()->json([
                'success' => true,
                'user_balance' => $user->balance ?? 0,
                'debug_user_id' => $user ? $user->id : null
            ]);

        } catch (\Exception $e) {
            \Log::error('Get User Balance Error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error loading balance: ' . $e->getMessage()
            ], 500);
        }
    }
}
