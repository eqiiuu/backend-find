<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Communitie;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;

class Komunitas extends Controller
{
    // Menampilkan semua komunitas
    public function index()
    {
        return Communitie::with('owner')->get();
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

        if ($request->hasFile('gambar')) {
            // Store in images/communities folder
            $imagePath = $request->file('gambar')->store('images/communities', 'public');
            \Log::info('Stored community image at: ' . $imagePath);
        }

        $community = Communitie::create([
            'owner_id' => Auth::user()->user_id,
            'name' => $request->name,
            'gambar' => $imagePath,
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
            'description' => $request->description,
            'anggota' => $request->anggota ?? [],
            'capacity' => $request->capacity
        ]);

        // Load the owner relation
        $community->load('owner');

        // Log the created community data for debugging
        \Log::info('Created community:', [
            'id' => $community->community_id,
            'name' => $community->name,
            'image_path' => $community->gambar,
            'image_url' => $community->gambar_url,
            'owner' => $community->owner
        ]);

        return response()->json([
            'message' => 'BERHASIL REGISTRASI!',
            'community' => $community
        ], 201);
    }

    // Menampilkan detail komunitas tertentu
    public function show($id)
    {
        return Communitie::with('owner')->findOrFail($id);
    }

    // Mengupdate komunitas tertentu
    public function update(Request $request, $id)
    {
        $community = Communitie::findOrFail($id);
        $community->update($request->all());
        $community->load('owner');
        return response()->json($community);
    }

    // Menghapus komunitas
    public function destroy($id)
    {
        Communitie::destroy($id);
        return response()->json(['message' => 'Community deleted']);
    }
}