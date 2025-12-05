<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $query = User::with(['role', 'organization']);

        // Filter based on user role
        if (!$user->isAdmin()) {
            $query->where('organization_id', $user->organization_id);
        }

        // Apply filters
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($request->has('role')) {
            $query->whereHas('role', function ($q) use ($request) {
                $q->where('name', $request->role);
            });
        }

        if ($request->has('organization_id')) {
            $query->where('organization_id', $request->organization_id);
        }

        if ($request->has('status')) {
            $isActive = $request->status === 'active';
            $query->where('is_active', $isActive);
        }

        return response()->json($query->orderBy('created_at', 'desc')->get());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'password' => 'required|string|min:6|confirmed',
            'role' => 'required|string|in:admin,dealer_owner,dealer_agent',
            'organization_id' => 'nullable|exists:organizations,id',
            'is_active' => 'boolean',
        ]);

        // Convert role name to role_id
        $role = \App\Models\Role::where('name', $validated['role'])->first();
        if (!$role) {
            return response()->json(['error' => 'Rôle invalide'], 422);
        }

        $validated['role_id'] = $role->id;
        unset($validated['role']);

        $validated['password'] = Hash::make($validated['password']);

        $user = User::create($validated);

        return response()->json($user->load(['role', 'organization']), 201);
    }

    public function show($id)
    {
        $user = User::with(['role', 'organization'])->findOrFail($id);

        return response()->json($user);
    }

    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => ['sometimes', 'email', Rule::unique('users')->ignore($id)],
            'role_id' => 'sometimes|exists:roles,id',
            'organization_id' => 'nullable|exists:organizations,id',
            'is_active' => 'boolean',
        ]);

        // Don't update password here - use separate resetPassword method
        if (isset($validated['password'])) {
            unset($validated['password']);
        }

        $user->update($validated);

        return response()->json($user->load(['role', 'organization']));
    }

    public function destroy($id)
    {
        $user = User::findOrFail($id);
        
        // Prevent deleting yourself
        if ($user->id === request()->user()->id) {
            return response()->json(['message' => 'You cannot delete your own account'], 403);
        }

        $user->delete();

        return response()->json(['message' => 'User deleted successfully']);
    }

    public function resetPassword(Request $request, $id)
    {
        $validated = $request->validate([
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = User::findOrFail($id);
        $user->password = Hash::make($validated['password']);
        $user->save();

        return response()->json(['message' => 'Password reset successfully']);
    }

    public function toggleStatus($id)
    {
        $user = User::findOrFail($id);
        
        // Prevent deactivating yourself
        if ($user->id === request()->user()->id) {
            return response()->json(['message' => 'You cannot deactivate your own account'], 403);
        }

        $user->is_active = !$user->is_active;
        $user->save();

        return response()->json($user->load(['role', 'organization']));
    }
}
