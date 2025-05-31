<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Communitie;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class Komunitas extends Controller
{
    // Menampilkan semua komunitas
    public function index()
    {
        try {
            $user = Auth::user();
            if (!$user) {
                \Log::error('No authenticated user found');
                return response()->json(['error' => 'Unauthorized'], 401);
            }

            \Log::info('Auth check - Current user:', [
                'user_id' => $user->user_id,
                'name' => $user->name,
                'email' => $user->email
            ]);

            // Get communities with owner relationship
            $communities = Communitie::with('owner')->get();

            // Validate each community's data
            $validatedCommunities = $communities->map(function($community) use ($user) {
                // Ensure owner_id is properly set
                if (empty($community->owner_id)) {
                    \Log::warning("Community found with no owner_id:", [
                        'community_id' => $community->community_id,
                        'name' => $community->name
                    ]);
                }

                // Log ownership check
                \Log::info("Community ownership check:", [
                    'community_id' => $community->community_id,
                    'name' => $community->name,
                    'owner_id' => $community->owner_id,
                    'current_user_id' => $user->user_id,
                    'is_owner' => $community->owner_id === $user->user_id
                ]);

                // Make sure all necessary fields are included
                return [
                    'community_id' => $community->community_id,
                    'name' => $community->name,
                    'description' => $community->description,
                    'owner_id' => $community->owner_id,
                    'owner' => $community->owner ? [
                        'user_id' => $community->owner->user_id,
                        'name' => $community->owner->name
                    ] : null,
                    'gambar' => $community->gambar,
                    'capacity' => $community->capacity,
                    'anggota' => $community->anggota
                ];
            });

            // Log summary
            \Log::info('Communities summary:', [
                'total_communities' => $communities->count(),
                'user_owned_communities' => $communities->where('owner_id', $user->user_id)->count(),
                'user_id' => $user->user_id
            ]);

            return response()->json($validatedCommunities);
        } catch (\Exception $e) {
            \Log::error('Error in communities index:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['error' => 'Internal server error'], 500);
        }
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