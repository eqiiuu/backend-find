<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Communitie;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\DB;

class Komunitas extends Controller
{
    // Menampilkan semua komunitas
    public function index()
    {
        return Communitie::all();
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
            'owner_name' => 'required|string',
            'create_chat_group' => 'nullable|boolean'
        ]);

        // Get user_id from name
        $user = \App\Models\User::where('name', $request->owner_name)->firstOrFail();

        // Start a database transaction
        DB::beginTransaction();

        try {
            $imagePath = null;

            if ($request->hasFile('gambar')) {
                // Store in images/communities folder
                $imagePath = $request->file('gambar')->store('images/communities', 'public');
                \Log::info('Stored community image at: ' . $imagePath);
            }

            // Decode anggota if it's a JSON string
            $anggota = $request->anggota;
            if (is_string($anggota)) {
                try {
                    $anggota = json_decode($anggota, true);
                    if (json_last_error() !== JSON_ERROR_NONE) {
                        throw new \Exception('Invalid JSON in anggota field');
                    }
                } catch (\Exception $e) {
                    \Log::error('Error decoding anggota JSON:', [
                        'error' => $e->getMessage(),
                        'anggota' => $anggota
                    ]);
                    $anggota = [];
                }
            }

            $community = Communitie::create([
                'owner_id' => $user->user_id,
                'name' => $request->name,
                'gambar' => $imagePath,
                'latitude' => $request->latitude,
                'longitude' => $request->longitude,
                'description' => $request->description,
                'anggota' => $anggota ?? [],
                'capacity' => $request->capacity
            ]);

            // Load the owner relation
            $community->load('owner');

            // Refresh the community data from the database
            $community->refresh();

            // Create chat group if toggle is checked
            if ($request->boolean('create_chat_group')) {
                \Log::info('Creating chat group for community:', [
                    'community_id' => $community->community_id
                ]);

                $chatGroup = new \App\Models\ChatGroup();
                $chatGroup->chat_group_id = 'chat_' . Str::random(8);
                $chatGroup->name = $community->name . ' Chat';
                $chatGroup->capacity = $community->capacity;
                $chatGroup->is_private = false;
                $chatGroup->community_id = $community->community_id;
                
                \Log::info('Chat group before save:', [
                    'chat_group' => $chatGroup->toArray()
                ]);
                
                $chatGroup->save();

                // Add all community members to the chat group
                $members = array_merge([$community->owner_id], $community->anggota ?? []);
                $chatGroup->users()->attach($members);
            }

            // If we got here, commit the transaction
            DB::commit();

            // Log the created community data for debugging
            \Log::info('Created community:', [
                'id' => $community->community_id,
                'name' => $community->name,
                'image_path' => $community->gambar,
                'image_url' => $community->gambar_url,
                'owner' => $community->owner,
                'anggota' => $community->anggota
            ]);

            return response()->json([
                'message' => 'BERHASIL REGISTRASI!',
                'community' => $community
            ], 201);

        } catch (\Exception $e) {
            // If anything fails, rollback the transaction
            DB::rollBack();
            \Log::error('Error creating community:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }


    // Menampilkan detail komunitas tertentu
    public function show($id)
    {
        return Communitie::findOrFail($id);
    }

    // Mengupdate komunitas tertentu
    public function update(Request $request, $id)
    {
        $community = Communitie::findOrFail($id);
        $community->update($request->all());
        return response()->json($community);
    }

    // Menghapus komunitas
    public function destroy($id)
    {
        Communitie::destroy($id);
        return response()->json(['message' => 'KOMUNITAS BERHASIL DIHAPUS']);
    }
}
