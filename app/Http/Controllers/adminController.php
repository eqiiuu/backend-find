<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Admins;
use App\Models\User;
use App\Models\Post;
use App\Models\Communitie;
use App\Models\Messages;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use App\Models\ChatGroup;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

class adminController extends Controller
{
    public function showRegister()
    {
        return view('register');
    }

    public function registerPost(Request $request)
    {
        $request->validate([
            'username' => 'required|string|min:3|max:50|unique:admins,username',
            'email' => 'required|email|unique:admins,email',
            'password' => 'required|string|min:8|confirmed',
            'name' => 'nullable|string|max:100'
        ]);

        $admin = new Admins();
        $admin->username = $request->username;
        $admin->email = $request->email;
        $admin->name = $request->name;
        $admin->password = Hash::make($request->password);
        $admin->is_super_admin = false; // Only first admin can be super admin
        $admin->is_active = true;
        $admin->save();

        return redirect()->route('login')->with('success', 'Admin berhasil terdaftar');
    }

    public function showLogin()
    {
        return view('login');
    }
    
    public function loginPost(Request $request)
    {
        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        // Try to find the admin by username or email
        $admin = Admins::where('username', $request->username)
            ->orWhere('email', $request->username)
            ->first();

        // Check if admin exists, is active, and password matches
        if ($admin && $admin->isActive() && Hash::check($request->password, $admin->password)) {
            Auth::guard('admin')->login($admin, $request->filled('remember'));
            $request->session()->regenerate();
            
            // Update last login information
            $admin->updateLastLogin($request);
            
            return redirect()->intended(route('admin.dashboard'));
        }

        return redirect()->route('login')
            ->withInput($request->only('username'))
            ->withErrors(['username' => 'Username atau password salah']);
    }

    public function logout(Request $request)
    {
        Auth::guard('admin')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login');
    }

    public function showDashboard()
    {
        $stats = [
            'total_users' => User::count(),
            'total_posts' => Post::count(),
            'total_communities' => Communitie::count(),
            'total_chats' => ChatGroup::count(),
            'recent_posts' => Post::select('post_id', 'title', 'user_id', 'community_id', 'post_date')
                ->latest()
                ->take(5)
                ->get(),
            'recent_communities' => Communitie::select('community_id', 'owner_id', 'description', 'capacity')
                ->latest()
                ->take(5)
                ->get(),
            'recent_chats' => ChatGroup::with('users')
                ->latest()
                ->take(5)
                ->get()
        ];

        return view('dashboard', compact('stats'));
    }

    public function showUsers()
    {
        $users = User::latest()->paginate(10);
        return view('admin.users.index', compact('users'));
    }

    public function editUser($id)
    {
        $user = User::findOrFail($id);
        return view('admin.users.edit', compact('user'));
    }

    public function updateUser(Request $request, $id)
    {
        $user = User::findOrFail($id);
        
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $id . ',user_id',
            'password' => 'nullable|string|min:6|confirmed',
            'nomor_telepon' => 'nullable|string|max:20'
        ]);

        // Only update fields that have changed
        $changes = array_filter($request->only(['name', 'email', 'nomor_telepon']), function($value, $key) use ($user) {
            return $value !== $user->$key;
        }, ARRAY_FILTER_USE_BOTH);

        // Handle password update if provided
        if ($request->filled('password')) {
            $changes['password'] = Hash::make($request->password);
        }

        if (!empty($changes)) {
            $user->update($changes);
            return redirect()->route('admin.users.index')->with('success', 'User updated successfully');
        }

        return redirect()->route('admin.users.index')->with('info', 'No changes were made');
    }

    public function deleteUser($id)
    {
        $user = User::findOrFail($id);
        $user->delete();
        return redirect()->route('admin.users.index')->with('success', 'User deleted successfully');
    }

    public function showCommunities()
    {
        $communities = Communitie::with('owner')->latest()->paginate(10);
        $users = User::all();
        return view('admin.communities.index', compact('communities', 'users'));
    }

    public function editCommunity($id)
    {
        $community = Communitie::findOrFail($id);
        $users = User::all();
        $communities = Communitie::all();
        return view('admin.communities.edit', compact('community', 'users', 'communities'));
    }

    public function updateCommunity(Request $request, $id)
    {
        $community = Communitie::findOrFail($id);
        
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'capacity' => 'required|integer|min:1',
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'anggota' => 'nullable|array',
            'anggota.*' => 'exists:users,user_id',
            'gambar' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:5120'
        ]);

        // Get user_id from name
        $user = User::where('name', $request->owner_name)->firstOrFail();

        // Handle anggota changes
        $oldAnggota = $community->anggota ?? [];
        $newAnggota = $request->anggota ?? [];
        
        // Find removed members
        $removedMembers = array_diff($oldAnggota, $newAnggota);
        
        // Update the community
        $changes = array_filter($request->only(['name', 'description', 'capacity', 'latitude', 'longitude']), function($value, $key) use ($community) {
            return $value !== $community->$key;
        }, ARRAY_FILTER_USE_BOTH);

        // Add owner_id to changes if it's different
        if ($user->user_id !== $community->owner_id) {
            $changes['owner_id'] = $user->user_id;
        }

        // Always update anggota to ensure removed members are kicked out
        $changes['anggota'] = $newAnggota;

        // Handle image upload if present
        if ($request->hasFile('gambar')) {
            // Delete old image if exists
            if ($community->gambar && file_exists(public_path($community->gambar))) {
                unlink(public_path($community->gambar));
            }

            $image = $request->file('gambar');
            // Store in the correct directory using Laravel's storage system
            $imagePath = $request->file('gambar')->store('images/communities', 'public');
            $changes['gambar'] = $imagePath;
        }

        if (!empty($changes)) {
            $community->update($changes);
            
            // Add success message with information about removed members
            $message = 'Community updated successfully';
            if (!empty($removedMembers)) {
                $removedNames = User::whereIn('user_id', $removedMembers)->pluck('name')->join(', ');
                $message .= '. Removed members: ' . $removedNames;
            }
            
            return redirect()->route('admin.communities.index')->with('success', $message);
        }

        return redirect()->route('admin.communities.index')->with('info', 'No changes were made');
    }

    public function deleteCommunity($id)
    {
        $community = Communitie::findOrFail($id);
        $community->delete();
        return redirect()->route('admin.communities.index')->with('success', 'Community deleted successfully');
    }

    public function showPosts()
    {
        $posts = Post::with(['user', 'community', 'comments.user', 'comments.replies.user'])->latest()->paginate(10);
        $communities = Communitie::all();
        return view('admin.posts.index', compact('posts', 'communities'));
    }

    public function editPost($id)
    {
        $post = Post::findOrFail($id);
        $users = User::all();
        $communities = Communitie::all();
        return view('admin.posts.edit', compact('post', 'users', 'communities'));
    }

    public function updatePost(Request $request, $id)
    {
        $post = Post::findOrFail($id);
        
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:5120',
            'community_id' => 'required|exists:communities,community_id'
        ]);

        $changes = array_filter($request->only(['title', 'description', 'community_id']), function($value, $key) use ($post) {
            return $value !== $post->$key;
        }, ARRAY_FILTER_USE_BOTH);

        // Handle image upload if present
        if ($request->hasFile('image')) {
            // Delete old image if exists
            if ($post->image && Storage::disk('public')->exists($post->image)) {
                Storage::disk('public')->delete($post->image);
            }

            // Store in the correct directory using Laravel's storage system
            $imagePath = $request->file('image')->store('images/posts', 'public');
            $changes['image'] = $imagePath;
        }

        if (!empty($changes)) {
            $post->update($changes);
            return redirect()->route('admin.posts.index')->with('success', 'Post updated successfully');
        }

        return redirect()->route('admin.posts.index')->with('info', 'No changes were made');
    }

    public function deletePost($id)
    {
        $post = Post::findOrFail($id);
        $post->delete();
        return redirect()->route('admin.posts.index')->with('success', 'Post deleted successfully');
    }

    public function createUser()
    {
        return view('admin.users.create');
    }

    public function storeUser(Request $request)
    {
        try {
            $request->validate([
                'username' => 'required|string|unique:users,username',
                'email' => 'required|email|unique:users,email',
                'password' => 'required|string|min:6|confirmed',
                'nomor_telepon' => 'required|string|max:20'
            ]);

            $user = new User();
            $user->user_id = 'usr_' . Str::random(10);
            $user->name = $request->username;
            $user->username = $request->username;
            $user->email = $request->email;
            $user->password = Hash::make($request->password);
            $user->nomor_telepon = $request->nomor_telepon;
            $user->save();

            \Log::info('User created successfully', [
                'user_id' => $user->user_id,
                'username' => $user->username,
                'email' => $user->email
            ]);

            return redirect()->route('admin.users.index')->with('success', 'User created successfully');
        } catch (\Exception $e) {
            \Log::error('Error creating user', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return redirect()->back()
                ->withInput()
                ->with('error', 'Failed to create user: ' . $e->getMessage());
        }
    }

    public function createCommunity()
    {
        $users = User::all();
        $communities = Communitie::all();
        return view('admin.communities.create', compact('users', 'communities'));
    }

    public function storeCommunity(Request $request)
    {
        try {
            $request->validate([
                'name' => 'required|string|max:255',
                'description' => 'required|string',
                'capacity' => 'required|integer|min:1',
                'latitude' => 'required|numeric',
                'longitude' => 'required|numeric',
                'anggota' => 'nullable|array',
                'anggota.*' => 'exists:users,user_id',
                'gambar' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:5120',
                'create_chat_group' => 'nullable|boolean'
            ]);

            // Get user_id from name
            $user = User::where('name', $request->owner_name)->firstOrFail();

            // Start a database transaction
            DB::beginTransaction();

            try {
                $community = new Communitie();
                $community->name = $request->name;
                $community->description = $request->description;
                $community->capacity = $request->capacity;
                $community->latitude = $request->latitude;
                $community->longitude = $request->longitude;
                $community->owner_id = $user->user_id;
                $community->anggota = $request->anggota;

                if ($request->hasFile('gambar')) {
                    $image = $request->file('gambar');
                    // Store in the correct directory using Laravel's storage system
                    $imagePath = $request->file('gambar')->store('images/communities', 'public');
                    $community->gambar = $imagePath;
                }

                // Save the community first to get its ID
                $community->save();
                
                // Refresh the community data from the database
                $community->refresh();
                
                \Log::info('Community created with ID:', [
                    'community_id' => $community->community_id,
                    'community' => $community->toArray()
                ]);

                // Create chat group if toggle is checked
                if ($request->boolean('create_chat_group')) {
                    \Log::info('Creating chat group for community:', [
                        'community_id' => $community->community_id
                    ]);

                    $chatGroup = new ChatGroup();
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

                return redirect()->route('admin.communities.index')->with('success', 'Community created successfully');
            } catch (\Exception $e) {
                // If anything fails, rollback the transaction
                DB::rollBack();
                throw $e;
            }
        } catch (\Exception $e) {
            \Log::error('Failed to create community', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Failed to create community: ' . $e->getMessage());
        }
    }

    public function createPost()
    {
        $users = User::all();
        $communities = Communitie::all();
        return view('admin.posts.create', compact('users', 'communities'));
    }

    public function storePost(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'user_id' => 'required|exists:users,user_id',
            'community_id' => 'required|exists:communities,community_id',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:5120'
        ]);

        $post = new Post();
        $post->title = $request->title;
        $post->description = $request->description;
        $post->user_id = $request->user_id;
        $post->community_id = $request->community_id;
        $post->post_date = now();

        if ($request->hasFile('image')) {
            // Store in the correct directory using Laravel's storage system
            $imagePath = $request->file('image')->store('images/posts', 'public');
            $post->image = $imagePath;
        }

        $post->save();

        return redirect()->route('admin.posts.index')->with('success', 'Post created successfully');
    }

    public function resetPassword($id)
    {
        $user = User::findOrFail($id);
        
        // Generate a random password
        $newPassword = Str::random(8);
        
        // Update the user's password using the same approach as updateUser
        $changes = [
            'password' => Hash::make($newPassword)
        ];
        
        $user->update($changes);

        // Store the new password in session for dashboard display
        session(['last_reset_password' => [
            'user_name' => $user->name,
            'password' => $newPassword,
            'timestamp' => now()
        ]]);

        return redirect()
            ->route('admin.users.index')
            ->with('success', "Password for {$user->name} has been reset to: {$newPassword}");
    }

    public function showChats()
    {
        $chatGroups = ChatGroup::with('users')->latest()->paginate(10);
        return view('admin.chats.index', compact('chatGroups'));
    }

    public function createChat()
    {
        $users = User::all();
        return view('admin.chats.create', compact('users'));
    }

    public function storeChat(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'capacity' => 'required|integer|min:2',
            'is_private' => 'nullable|boolean',
            'user_ids' => 'required|array',
            'user_ids.*' => 'exists:users,user_id',
            'community_id' => 'nullable|exists:communities,community_id'
        ]);

        // For private chats between two users
        if ($request->is_private) {
            if (count($request->user_ids) !== 1) {
                return redirect()
                    ->back()
                    ->with('error', 'Private chats can only be created with exactly one other user');
            }
            $request->capacity = 2; // Force capacity to 2 for private chats
        }

        // Create the chat group first
        $group = new ChatGroup();
        $group->chat_group_id = 'chat_' . Str::random(8);
        $group->name = $request->name;
        $group->capacity = $request->capacity;
        $group->is_private = $request->boolean('is_private');
        $group->community_id = $request->community_id;
        $group->save();

        // After the group is created, add all users at once
        if ($request->has('user_ids')) {
            $group->users()->attach($request->user_ids);
        }

        return redirect()
            ->route('admin.chats.index')
            ->with('success', 'Chat group created successfully');
    }

    public function editChat($id)
    {
        $chatGroup = ChatGroup::with('users')->findOrFail($id);
        $users = User::all();
        return view('admin.chats.edit', compact('chatGroup', 'users'));
    }

    public function updateChat(Request $request, $id)
    {
        $group = ChatGroup::findOrFail($id);
        
        $request->validate([
            'name' => 'required|string|max:255',
            'capacity' => 'required|integer|min:2',
            'is_private' => 'nullable|boolean',
            'user_ids' => 'required|array',
            'user_ids.*' => 'exists:users,user_id'
        ]);

        // For private chats between two users
        if ($request->is_private) {
            if (count($request->user_ids) !== 1) {
                return redirect()
                    ->back()
                    ->with('error', 'Private chats can only have exactly one other user');
            }
            $request->capacity = 2; // Force capacity to 2 for private chats
        }

        // Update basic info
        $group->update([
            'name' => $request->name,
            'capacity' => $request->capacity,
            'is_private' => $request->boolean('is_private')
            // Note: community_id is not included here as it cannot be changed after creation
        ]);

        // Sync users (this will remove users not in the new list and add new ones)
        $group->users()->sync($request->user_ids);

        return redirect()
            ->route('admin.chats.index')
            ->with('success', 'Chat group updated successfully');
    }

    public function deleteChat($id)
    {
        $group = ChatGroup::findOrFail($id);
        $group->delete();
        return redirect()
            ->route('admin.chats.index')
            ->with('success', 'Chat group deleted successfully');
    }
}
