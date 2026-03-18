<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\{Request, JsonResponse};
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(
            User::select('id','username','full_name','role','active','created_at')
                ->orderBy('id')->get()
        );
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'username'  => 'required|string|unique:sys_users,username|max:80',
            'password'  => 'required|string|min:6',
            'full_name' => 'required|string|max:150',
            'role'      => 'in:admin,user',
        ]);
        $data['password'] = Hash::make($data['password']);
        $user = User::create($data);
        return response()->json($user->only(['id','username','full_name','role','active']), 201);
    }

    public function show(string $id): JsonResponse
    {
        return response()->json(User::findOrFail($id)->only(['id','username','full_name','role','active']));
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $user = User::findOrFail($id);
        $data = $request->validate([
            'full_name' => 'sometimes|string|max:150',
            'role'      => 'sometimes|in:admin,user',
            'active'    => 'sometimes|boolean',
            'password'  => 'sometimes|string|min:6',
        ]);
        if (isset($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        }
        $user->update($data);
        return response()->json($user->only(['id','username','full_name','role','active']));
    }

    public function destroy(Request $request, string $id): JsonResponse
    {
        if ($request->user()->id == $id) {
            return response()->json(['message' => 'Cannot delete yourself.'], 400);
        }
        User::findOrFail($id)->delete();
        return response()->json(['message' => 'Deleted.']);
    }
}
