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
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated'
                ], 401);
            }

            // Get balance from user table
            $balance = $user->balance ?? 0;
            
            return response()->json([
                'success' => true,
                'user_balance' => $balance
            ]);

        } catch (\Exception $e) {
            \Log::error('Get User Balance Error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error loading balance',
                'user_balance' => 0
            ], 500);
        }
    }
}
