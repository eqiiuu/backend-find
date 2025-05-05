<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Community;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;

class Komunitas extends Controller
{
    // Menampilkan semua komunitas
    public function index()
    {
        return Community::all();
    }

    public function createCommunity(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'gambar' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:5048',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'description' => 'required',
            'anggota' => 'nullable',
            'capacity' => 'required|integer|min:1',
        ]);

        $imagePath = null;

        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('images/community', 'public');
        }

        Communitie::create([
            'owner_id' => Auth::user()->user_id,
            'name' => $request->name,
            'gambar' => $imagePath,
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
            'description' => $request->description,
            'anggota' => $request->anggota,
            'capacity' => $request->capacity
        ]);
        return response()->json(['message'=>'BERHASIL REGISTRASI!'], 201);
    }

    // Menampilkan detail komunitas tertentu
    public function show($id)
    {
        return Community::findOrFail($id);
    }

    // Mengupdate komunitas tertentu
    public function update(Request $request, $id)
    {
        $community = Community::findOrFail($id);
        $community->update($request->all());
        return response()->json($community);
    }

    // Menghapus komunitas
    public function destroy($id)
    {
        Community::destroy($id);
        return response()->json(['message' => 'Community deleted']);
    }
}
