<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;

class Pengguna extends Controller
{
    public function index()
    {
        return User::all();
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required',
            'username' => 'required|string|unique:users',
            'display_name' => 'required',
            'posts' => 'nullable',
            'no_telp' => 'required',
            'email' => 'required|email|unique:users',
            'desc' => 'nullable',
            'is_premium' => 'boolean',
        ]);

        $user = User::create($validated);
        return response()->json($user, 201);
    }

    public function show($id)
    {
        return User::findOrFail($id);
    }

    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);
        $user->update($request->all());
        return response()->json($user);
    }

    public function destroy($id)
    {
        User::destroy($id);
        return response()->json(['message' => 'User deleted']);
    }
}
