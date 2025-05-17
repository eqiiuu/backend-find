<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Communitie;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use App\Models\Product; 
use App\Models\Post;
use App\Models\comments;
use App\Models\Admins;
use Illuminate\Support\Facades\Auth;
class authController extends Controller
{
    public function register(Request $request)
    {
        $request->validate([
            'username'=>'required',
            'name'=>'nullable',
            'email'=>'required',
            'password'=>'required',
            'nomor_telepon' => 'required'
        ]);

        $name = $request->name ?? $request->username;

        User::create([
            'username'=>$request->username,
            'name'=>$name,
            'email'=>$request->email,
            'password'=>Hash::make($request->password),
            'nomor_telepon'=>$request->nomor_telepon
        ]);
        return response()->json(['message'=>'berhasil registrasi'],201);
    }

    public function login(Request $request)
    {
        $request->validate([ 
            'email'=>'required',
            'password'=>'required'
        ]);
        $user=User::where('email', $request->email)->first();
        if(!$user || !Hash::check($request->password,$user->password)){
            return response()->json(['message'=>'UNAUTHORIZED'],401);
        }
        $token=$user->createToken('Auth-token')->plainTextToken;
        return response()->json([
            'user'=>$user,
            'token'=>$token
        ],200);
    }

    public function adminLogin(Request $request)
    {
        try {
        $form = [
            'username' => $request->username,
            'password' => $request->password
        ];

            $credentials = $request->validate([
                'username' => 'required',
                'password' => 'required'
            ]);

            \Log::info('Login attempt', [
                'username' => $credentials['username'],
                'has_password' => !empty($credentials['password'])
            ]);

            // Check if admin exists
            $admin = \App\Models\Admins::where('username', $credentials['username'])->first();
            if (!$admin) {
                \Log::warning('Admin not found', ['username' => $credentials['username']]);
                return redirect()->route('admin.login')->with('error', 'Invalid credentials');
            }

            \Log::info('Admin found', ['admin_id' => $admin->user_id]);

            // Attempt authentication
            if (Auth::guard('admin')->attempt($credentials)) {
                \Log::info('Login successful', ['admin_id' => $admin->user_id]);
                
                $request->session()->regenerate();
                return redirect()->route('admin.dashboard')->with('success', 'Login successful');
            }

            \Log::warning('Login failed - invalid password', ['admin_id' => $admin->user_id]);
            return redirect()->route('admin.login')->with('error', 'Invalid credentials');
        } catch (\Exception $e) {
            \Log::error('Login error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return redirect()->route('admin.login')->with('error', 'An error occurred during login');
        }
    }
    
    public function showLoginForm()
    {
        if (Auth::guard('admin')->check()) {
            return redirect()->route('admin.dashboard');
        }
        return view('login');
    }

    public function delete(Request $request){

        $request->validate([
            'id'=>'required|integer'
        ]);

        $del = User::findOrFail($request->id);

        $del->delete();
        return response()->json(['message'=>'BERHASIL MENGHAPUS AKUN'],201);
    }    
    public function update(Request $request)
    {
        try {
            \Log::info('Update profile request received', ['request' => $request->all()]);
            
            // Validate request
            $request->validate([
                'name' => 'nullable|string|max:255',
                'email' => 'nullable|email|unique:users,email,' . auth()->user()->user_id . ',user_id',
                'password' => 'nullable|string|min:6',
                'nomor_telepon' => 'nullable|string|unique:users,nomor_telepon,' . auth()->user()->user_id . ',user_id',
                'lokasi' => 'nullable|string|max:255',
                'tentang' => 'nullable|string',
                'photo' => 'nullable|file|mimes:jpeg,png,jpg,gif|max:5120',
                'background' => 'nullable|file|mimes:jpeg,png,jpg,gif|max:5120',
                'delete_photo' => 'nullable|string|in:true,false',
                'delete_background' => 'nullable|string|in:true,false',
                'use_profile_photo' => 'nullable|string|in:true,false'
            ]);

            $user = Auth::user();
            if (!$user) {
                throw new \Exception('User not authenticated');
            }

            \Log::info('User found', ['user_id' => $user->user_id]);

            // Update basic fields if they exist in the request
            $fieldsToUpdate = ['name', 'email', 'nomor_telepon', 'lokasi', 'tentang'];
            foreach ($fieldsToUpdate as $field) {
                if ($request->has($field) && !is_null($request->input($field))) {
                    $user->$field = $request->input($field);
                }
            }
            
            // Handle password separately
            if ($request->filled('password')) {
                $user->password = Hash::make($request->password);
            }

            // Handle photo deletion
            if ($request->has('delete_photo') && $request->input('delete_photo') === 'true') {
                \Log::info('Deleting profile photo for user', ['user_id' => $user->user_id]);
                
                // Delete old photo if exists
                if ($user->photo) {
                    try {
                        $oldPath = str_replace('/storage/', '', $user->photo);
                        if (Storage::disk('public')->exists($oldPath)) {
                            Storage::disk('public')->delete($oldPath);
                            \Log::info('Profile photo deleted', ['path' => $oldPath]);
                        } else {
                            // Try with absolute path if relative path fails
                            $absolutePath = storage_path('app/public/' . $oldPath);
                            if (file_exists($absolutePath)) {
                                unlink($absolutePath);
                                \Log::info('Profile photo deleted using absolute path', ['path' => $absolutePath]);
                            } else {
                                \Log::warning('Profile photo not found', ['relative_path' => $oldPath, 'absolute_path' => $absolutePath]);
                            }
                        }
                    } catch (\Exception $e) {
                        \Log::warning('Failed to delete profile photo', [
                            'error' => $e->getMessage(),
                            'path' => $oldPath ?? null
                        ]);
                    }
                }
                
                // Check if background is using the same photo and clear it
                if ($user->background === $user->photo) {
                    \Log::info('Clearing background as it uses the same photo');
                    $user->background = null;
                }
                
                // Set photo to null
                $user->photo = null;
                \Log::info('Profile photo set to null');
            }
            // Handle photo upload
            else if ($request->hasFile('photo')) {
                try {
                    $photo = $request->file('photo');
                    if (!$photo->isValid()) {
                        throw new \Exception('Invalid photo file uploaded');
                    }

                    \Log::info('Handling photo upload', [
                        'original_name' => $photo->getClientOriginalName(),
                        'mime_type' => $photo->getMimeType(),
                        'size' => $photo->getSize()
                    ]);

                    // Delete old photo if exists
                    if ($user->photo) {
                        try {
                            $oldPath = str_replace('/storage/', '', $user->photo);
                            if (Storage::disk('public')->exists($oldPath)) {
                                Storage::disk('public')->delete($oldPath);
                                \Log::info('Old photo deleted', ['path' => $oldPath]);
                            } else {
                                // Coba hapus dengan path absolut jika path relatif tidak berhasil
                                $absolutePath = storage_path('app/public/' . $oldPath);
                                if (file_exists($absolutePath)) {
                                    unlink($absolutePath);
                                    \Log::info('Old photo deleted using absolute path', ['path' => $absolutePath]);
                                } else {
                                    \Log::warning('Old photo not found', ['relative_path' => $oldPath, 'absolute_path' => $absolutePath]);
                                }
                            }
                        } catch (\Exception $e) {
                            \Log::warning('Failed to delete old photo', [
                                'error' => $e->getMessage(),
                                'path' => $oldPath ?? null
                            ]);
                        }
                    }

                    // Store new photo
                    $fileName = 'profile_' . time() . '.' . $photo->getClientOriginalExtension();
                    \Log::info('Attempting to store photo', [
                        'filename' => $fileName,
                        'storage_path' => storage_path('app/public/profile_photos'),
                        'disk' => 'public'
                    ]);
                    $photoPath = $photo->storeAs('profile_photos', $fileName, 'public');
                    if (!$photoPath) {
                        throw new \Exception('Failed to store photo file');
                    }
                    $user->photo = '/storage/' . $photoPath;
                    \Log::info('Photo stored successfully', [
                        'path' => $photoPath, 
                        'full_path' => storage_path('app/public/'.$photoPath),
                        'url_path' => '/storage/' . $photoPath
                    ]);
                } catch (\Exception $e) {
                    \Log::error('Error handling photo upload', [
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);
                    throw new \Exception('Error uploading photo: ' . $e->getMessage());
                }
            }

            // Handle background deletion
            if ($request->has('delete_background') && $request->input('delete_background') === 'true') {
                \Log::info('Deleting profile background for user', ['user_id' => $user->user_id]);
                
                // Delete old background if exists
                if ($user->background) {
                    try {
                        $oldPath = str_replace('/storage/', '', $user->background);
                        if (Storage::disk('public')->exists($oldPath)) {
                            Storage::disk('public')->delete($oldPath);
                            \Log::info('Profile background deleted', ['path' => $oldPath]);
                        } else {
                            // Try with absolute path if relative path fails
                            $absolutePath = storage_path('app/public/' . $oldPath);
                            if (file_exists($absolutePath)) {
                                unlink($absolutePath);
                                \Log::info('Profile background deleted using absolute path', ['path' => $absolutePath]);
                            } else {
                                \Log::warning('Profile background not found', ['relative_path' => $oldPath, 'absolute_path' => $absolutePath]);
                            }
                        }
                    } catch (\Exception $e) {
                        \Log::warning('Failed to delete profile background', [
                            'error' => $e->getMessage(),
                            'path' => $oldPath ?? null
                        ]);
                    }
                }
                
                // Set background to null or use profile photo
                if ($request->has('use_profile_photo') && $request->input('use_profile_photo') === 'true' && $user->photo) {
                    $user->background = $user->photo;
                    \Log::info('Profile background set to user profile photo');
                } else {
                    $user->background = null;
                    \Log::info('Profile background set to null');
                }
            }
            // Handle background upload
            else if ($request->hasFile('background')) {
                try {
                    $background = $request->file('background');
                    if (!$background->isValid()) {
                        throw new \Exception('Invalid background file uploaded');
                    }

                    \Log::info('Handling background upload', [
                        'original_name' => $background->getClientOriginalName(),
                        'mime_type' => $background->getMimeType(),
                        'size' => $background->getSize()
                    ]);

                    // Delete old background if exists (only if it's different from profile photo)
                    if ($user->background && $user->background !== $user->photo) {
                        try {
                            $oldPath = str_replace('/storage/', '', $user->background);
                            if (Storage::disk('public')->exists($oldPath)) {
                                Storage::disk('public')->delete($oldPath);
                                \Log::info('Old background deleted', ['path' => $oldPath]);
                            } else {
                                // Try with absolute path if relative path fails
                                $absolutePath = storage_path('app/public/' . $oldPath);
                                if (file_exists($absolutePath)) {
                                    unlink($absolutePath);
                                    \Log::info('Old background deleted using absolute path', ['path' => $absolutePath]);
                                } else {
                                    \Log::warning('Old background not found', ['relative_path' => $oldPath, 'absolute_path' => $absolutePath]);
                                }
                            }
                        } catch (\Exception $e) {
                            \Log::warning('Failed to delete old background', [
                                'error' => $e->getMessage(),
                                'path' => $oldPath ?? null
                            ]);
                        }
                    }

                    // Store new background
                    $fileName = 'background_' . time() . '.' . $background->getClientOriginalExtension();
                    $backgroundPath = $background->storeAs('profile_backgrounds', $fileName, 'public');
                    if (!$backgroundPath) {
                        throw new \Exception('Failed to store background file');
                    }
                    $user->background = '/storage/' . $backgroundPath;
                    \Log::info('Background stored successfully', ['path' => $backgroundPath]);
                } catch (\Exception $e) {
                    \Log::error('Error handling background upload', [
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);
                    throw new \Exception('Error uploading background: ' . $e->getMessage());
                }
            }

            if (!$user->save()) {
                throw new \Exception('Failed to save user data');
            }

            \Log::info('Profile updated successfully', ['user_id' => $user->user_id]);

            return response()->json([
                'message' => 'DATA BERHASIL DIPERBARUI!',
                'user' => $user
            ], 200);
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            \Log::error('Validation error during profile update', [
                'errors' => $e->errors()
            ]);
            return response()->json([
                'message' => 'Validation error',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Profile update error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'message' => 'Gagal memperbarui profil',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function user()
    {
        $user = Auth::user();
        return response()->json([
            'user_id' => $user->user_id,
            'name' => $user->name,
            'email' => $user->email,
            'username' => $user->username,
            'nomor_telepon' => $user->nomor_telepon,
            'photo' => $user->photo,
            'background' => $user->background,
            'lokasi' => $user->lokasi,
            'tentang' => $user->tentang
        ]);
    }
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json('ANDA TELAH BERHASIL LOGOUT! SAMPAI JUMPA KEMBALI :)');
    }


    // Fungsi untuk percobaan saja
    public function tampilkan($id)
    {
        $user = User::findOrFail($id);
        return response()->json([
            'name' => $user->name,
            'email' => $user->email,
            'nomor_telepon' => $user->nomor_telepon,
            'photo' => $user->photo,
            'background' => $user->background,
            'lokasi' => $user->lokasi,
            'tentang' => $user->tentang
        ]);
    }

      // Fungsi untuk percobaan saja
    public function image(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'title' => 'required|string|max:255',
            'description' => 'required',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:5120', 
        ]);

        $imagePath = null;

        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('images', 'public');
        }

        Product::create([
            'name' => $request->name,
            'title' => $request->title,
            'description' => $request->description,
            'image' => $imagePath,
        ]);

        return response()->json(['message' => 'BERHASIL MENAMBAHKAN GAMBAR!']);
    }

}