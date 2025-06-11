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

    public function getPlacesWithinRadius(Request $request)
    {
        try {
            // Validate request parameters
            $validated = $request->validate([
                'latitude' => 'required|numeric|between:-90,90',
                'longitude' => 'required|numeric|between:-180,180',
                'radius' => 'required|numeric|min:0' // Radius in kilometers
            ]);

            // Extract validated data
            $latitude = $validated['latitude'];
            $longitude = $validated['longitude'];
            $radius = $validated['radius'];

            // Query places within the radius
            $places = Communitie::withinRadius($latitude, $longitude, $radius)->get();

            return response()->json([
                'success' => true,
                'data' => $places
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
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

    public function joinRequest($id)
    {
        try {
            $community = Communitie::findOrFail($id);
            $userId = Auth::user()->user_id;

            // Check if user is already a member
            if (in_array($userId, $community->anggota ?? [])) {
                return response()->json([
                    'message' => 'You are already a member of this community'
                ], 400);
            }

            // Check if community is at capacity
            if (count($community->anggota ?? []) >= $community->capacity) {
                return response()->json([
                    'message' => 'Community has reached its member capacity'
                ], 400);
            }

            // Add user to anggota array
            $currentAnggota = $community->anggota ?? [];
            $currentAnggota[] = $userId;
            $community->anggota = $currentAnggota;
            $community->save();

            return response()->json([
                'message' => 'Successfully joined the community',
                'community' => $community
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to process join request: ' . $e->getMessage()
            ], 500);
        }
    }

    public function joinResponse($id, $userId)
    {
        try {
            $community = Communitie::findOrFail($id);
            
            // Check if the current user is the community owner
            if (Auth::user()->user_id !== $community->owner_id) {
                return response()->json([
                    'message' => 'Only the community owner can respond to join requests'
                ], 403);
            }

            $request = request();
            $action = $request->input('action');

            if (!in_array($action, ['accept', 'reject'])) {
                return response()->json([
                    'message' => 'Invalid action. Must be either "accept" or "reject"'
                ], 400);
            }

            if ($action === 'accept') {
                // Add user to anggota array if not already a member
                if (!in_array($userId, $community->anggota ?? [])) {
                    $currentAnggota = $community->anggota ?? [];
                    $currentAnggota[] = $userId;
                    $community->anggota = $currentAnggota;
                    $community->save();
                }
            }

            return response()->json([
                'message' => 'Join request ' . $action . 'ed successfully',
                'community' => $community
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to process join response: ' . $e->getMessage()
            ], 500);
        }
    }
}