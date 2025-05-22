<?php

namespace App\Http\Controllers;

use App\Models\ChatGroup;
use App\Models\Messages;
use App\Events\NewMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ChatController extends Controller
{

    /**
     * Create a new chat group.
     */
    public function createGroup(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'capacity' => 'required|integer|min:2',
            'is_private' => 'nullable|boolean',
            'user_ids' => 'required|array',
            'user_ids.*' => 'exists:users,user_id'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $group = ChatGroup::create([
            'name' => $request->name,
            'capacity' => $request->capacity,
            'is_private' => $request->is_private ?? false
        ]);

        // Add the creator to the group
        $group->users()->attach(Auth::id());

        // Add other users to the group
        foreach ($request->user_ids as $user_id) {
            if (!$group->isAtCapacity()) {  
                $group->users()->attach($user_id);
            }
        }

        return response()->json($group->load('users'), 201);
    }

    /**
     * Send a message to a chat group.
     */
    public function sendMessage(Request $request, $group)
    {
        try {
            \Log::info('Attempting to send message to group: ' . $group);
            \Log::info('User ID: ' . Auth::id());
            \Log::info('Request data:', $request->all());
            \Log::info('Request headers:', $request->headers->all());
            
            // Validate group ID format
            if (!Str::startsWith($group, 'chat_')) {
                \Log::error('Invalid group ID format:', ['group_id' => $group]);
                return response()->json(['error' => 'Invalid group ID format'], 400);
            }
            
            // Find the chat group
            try {
                $chatGroup = ChatGroup::findOrFail($group);
                \Log::info('Chat group found:', ['group_id' => $chatGroup->chat_group_id]);
            } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
                \Log::error('Chat group not found:', ['group_id' => $group]);
                return response()->json(['error' => 'Chat group not found'], 404);
            }
            
            // Validate message
            $validator = Validator::make($request->all(), [
                'message' => 'required|string|max:1000'
            ]);

            if ($validator->fails()) {
                \Log::error('Validation failed:', ['errors' => $validator->errors()->toArray()]);
                return response()->json(['errors' => $validator->errors()], 422);
            }

            // Check if user is a member of the group
            $isMember = $chatGroup->users()->where('chat_group_user.user_id', Auth::id())->exists();
            \Log::info('User membership check:', [
                'is_member' => $isMember,
                'user_id' => Auth::id(),
                'group_id' => $group
            ]);
            
            if (!$isMember) {
                \Log::error('User not in group:', ['user_id' => Auth::id(), 'group_id' => $group]);
                return response()->json(['error' => 'You are not a member of this group'], 403);
            }

            // Create message with error handling
            try {
                $message = Messages::create([
                    'chat_group_id' => $chatGroup->chat_group_id,
                    'user_id' => Auth::id(),
                    'message' => $request->message
                ]);
                \Log::info('Message created:', ['message_id' => $message->id]);
            } catch (\Exception $e) {
                \Log::error('Failed to create message:', [
                    'error' => $e->getMessage(),
                    'chat_group_id' => $chatGroup->chat_group_id,
                    'user_id' => Auth::id()
                ]);
                return response()->json(['error' => 'Failed to create message'], 500);
            }

            // Broadcast with error handling
            try {
                broadcast(new NewMessage($message, $chatGroup->chat_group_id))->toOthers();
                \Log::info('Message broadcasted successfully');
            } catch (\Exception $e) {
                \Log::error('Broadcasting failed:', [
                    'error' => $e->getMessage(),
                    'message_id' => $message->id
                ]);
                \Log::error($e->getTraceAsString());
                // Don't return error here, as message is already saved
            }

            return response()->json($message->load('user'), 201);
            
        } catch (\Exception $e) {
            \Log::error('Error sending message: ' . $e->getMessage());
            \Log::error($e->getTraceAsString());
            return response()->json(['error' => 'Failed to send message: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Get messages for a chat group.
     */
    public function getMessages(ChatGroup $group)
    {
        // Check if user is a member of the group
        if (!$group->users()->where('chat_group_user.user_id', Auth::id())->exists()) {
            return response()->json(['error' => 'You are not a member of this group'], 403);
        }

        $messages = $group->messages()
            ->with('user')
            ->latest()
            ->paginate(20);

        return response()->json($messages);
    }

    /**
     * Get user's chat groups.
     */
    public function getUserGroups()
    {
        try {
            // Debug information
            \Log::info('User ID: ' . Auth::id());
            \Log::info('Attempting to fetch chat groups');
            
            // Check if the user has the chatGroups relationship
            if (!method_exists(Auth::user(), 'chatGroups')) {
                \Log::error('chatGroups method does not exist on User model');
                return response()->json(['error' => 'Chat groups relationship not defined'], 500);
            }
            
            $groups = Auth::user()->chatGroups()
                ->with(['users'])
                ->get()
                ->map(function ($group) {
                    // Get latest message for this group
                    $latestMessage = Messages::where('chat_group_id', $group->chat_group_id)
                        ->with('user')
                        ->latest()
                        ->first();
                    
                    $group->messages = $latestMessage ? [$latestMessage] : [];
                    
                    if ($group->is_private) {
                        // Get the other member's name for private groups
                        $otherMember = $group->users()
                            ->where('users.user_id', '!=', Auth::id())
                            ->first();
                        
                        if ($otherMember) {
                            $group->display_name = $otherMember->name;
                        }
                    } else {
                        $group->display_name = $group->name;
                    }
                    return $group;
                });

            return response()->json($groups);
        } catch (\Exception $e) {
            \Log::error('Error fetching chat groups: ' . $e->getMessage());
            \Log::error($e->getTraceAsString());
            return response()->json(['error' => 'Failed to fetch chat groups: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Add a user to a chat group.
     */
    public function addUserToGroup(Request $request, ChatGroup $group)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,user_id'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        if ($group->isAtCapacity()) {
            return response()->json(['error' => 'Group is at full capacity'], 400);
        }

        if ($group->addUser($request->user_id)) {
            return response()->json(['message' => 'User added to group successfully']);
        }

        return response()->json(['error' => 'Failed to add user to group'], 400);
    }

    /**
     * Remove a user from a chat group.
     */
    public function removeUserFromGroup(Request $request, ChatGroup $group)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,user_id'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        if ($group->removeUser($request->user_id)) {
            return response()->json(['message' => 'User removed from group successfully']);
        }

        return response()->json(['error' => 'Failed to remove user from group'], 400);
    }

    /**
     * Get a specific chat group.
     */
    public function getGroup(ChatGroup $group)
    {
        // Check if user is a member of the group
        if (!$group->users()->where('chat_group_user.user_id', Auth::id())->exists()) {
            return response()->json(['error' => 'You are not a member of this group'], 403);
        }

        // Load the group with its users and latest message
        $group->load(['users', 'messages' => function($query) {
            $query->latest()->take(1);
        }]);

        return response()->json($group);
    }

    /**
     * Test function for debugging chat groups
     */
    public function testChatGroups()
    {
        try {
            // Check if the tables exist
            $tables = DB::select('SHOW TABLES');
            $tableNames = array_map(function($table) {
                return array_values((array)$table)[0];
            }, $tables);
            
            // Get schema for chat-related tables
            $chatGroupsSchema = [];
            $messagesSchema = [];
            $chatGroupUserSchema = [];
            
            if (in_array('chat_groups', $tableNames)) {
                $chatGroupsSchema = DB::select('DESCRIBE chat_groups');
            }
            
            if (in_array('messages', $tableNames)) {
                $messagesSchema = DB::select('DESCRIBE messages');
            }
            
            if (in_array('chat_group_user', $tableNames)) {
                $chatGroupUserSchema = DB::select('DESCRIBE chat_group_user');
            }
            
            // Check if any users exist
            $users = DB::table('users')->select('user_id', 'name', 'email')->limit(5)->get();
            
            // Create a test chat group if none exist
            $chatGroups = DB::table('chat_groups')->get();
            if (count($chatGroups) === 0 && count($users) > 0) {
                $newGroupId = 'chat_' . Str::random(8);
                DB::table('chat_groups')->insert([
                    'chat_group_id' => $newGroupId,
                    'name' => 'Test Group',
                    'capacity' => 10,
                    'is_private' => false,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
                
                // Add first user to the group
                DB::table('chat_group_user')->insert([
                    'chat_group_id' => $newGroupId,
                    'user_id' => $users[0]->user_id,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
                
                // Add a test message
                DB::table('messages')->insert([
                    'chat_group_id' => $newGroupId,
                    'user_id' => $users[0]->user_id,
                    'message' => 'This is a test message',
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
                
                $chatGroups = DB::table('chat_groups')->get();
            }
            
            return response()->json([
                'status' => 'success',
                'data' => [
                    'tables' => $tableNames,
                    'chat_groups_schema' => $chatGroupsSchema,
                    'messages_schema' => $messagesSchema,
                    'chat_group_user_schema' => $chatGroupUserSchema,
                    'users_sample' => $users,
                    'chat_groups' => $chatGroups
                ]
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ], 500);
        }
    }
}
