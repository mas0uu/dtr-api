<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $validated = $request->validate([
            'student_no' => 'required|string|max:50|unique:users,student_no',
            'student_name' => 'required|string|max:255',
            'school' => 'nullable|string|max:255',
            'required_hours' => 'required|integer|min:1',

            'company' => 'nullable|string|max:255',
            'department' => 'nullable|string|max:255',
            'supervisor_name' => 'nullable|string|max:255',
            'supervisor_position' => 'nullable|string|max:255',

            'password' => 'required|string|min:6|confirmed', // needs password_confirmation
        ]);

        $user = User::create([
            'student_no' => $validated['student_no'],
            'student_name' => $validated['student_name'],
            'school' => $validated['school'] ?? null,
            'required_hours' => $validated['required_hours'],

            'company' => $validated['company'] ?? null,
            'department' => $validated['department'] ?? null,
            'supervisor_name' => $validated['supervisor_name'] ?? null,
            'supervisor_position' => $validated['supervisor_position'] ?? null,

            'password' => Hash::make($validated['password']),
        ]);

        $token = $user->createToken('dtr-token')->plainTextToken;

        return response()->json([
            'message' => 'Registered successfully',
            'token' => $token,
            'user' => $this->userPayload($user),
        ], 201);
    }

    public function login(Request $request)
    {
        $validated = $request->validate([
            'student_no' => 'required|string',
            'password' => 'required|string',
        ]);

        $user = User::where('student_no', $validated['student_no'])->first();

        if (!$user || !Hash::check($validated['password'], $user->password)) {
            throw ValidationException::withMessages([
                'student_no' => ['Invalid student number or password.'],
            ]);
        }

        // Optional: revoke old tokens so 1 device session only
        $user->tokens()->delete();

        $token = $user->createToken('dtr-token')->plainTextToken;

        return response()->json([
            'message' => 'Logged in successfully',
            'token' => $token,
            'user' => $this->userPayload($user),
        ]);
    }

    public function me(Request $request)
    {
        return response()->json([
            'user' => $this->userPayload($request->user()),
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logged out successfully',
        ]);
    }

    private function userPayload(User $user): array
    {
        return [
            'id' => $user->id,
            'student_no' => $user->student_no,
            'student_name' => $user->student_name,
            'school' => $user->school,
            'required_hours' => $user->required_hours,
            'company' => $user->company,
            'department' => $user->department,
            'supervisor_name' => $user->supervisor_name,
            'supervisor_position' => $user->supervisor_position,
        ];
    }
}