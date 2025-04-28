<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Community;

class Komunitas extends Controller
{
    // Menampilkan semua komunitas
    public function index()
    {
        return Community::all();
    }

    // Menyimpan data komunitas baru
    public function store(Request $request)
    {
        $validated = $request->validate([
            'community_id' => 'required',
            'owner_id' => 'required',
            'gambar' => 'nullable',
            'koordinat' => 'required',
            'description' => 'required',
            'anggota' => 'nullable',
            'capacity' => 'required',
        ]);
        Community::create([
            'community_id'=>$request->community_id,
            'owner_id'=>$request->owner_id,
            'gambar'=>$request->gambar,
            'koordinat'=>$request->koordinat,
            'description'=>$request->description,
            'anggota'=>$request->json_encode($request->anggota),
            'capacity'=>$request->capacity
        ]);
        dd($community); // Cek hasil simpan
        return response()->json(['message'=>'berhasil registrasi'], 201);
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
