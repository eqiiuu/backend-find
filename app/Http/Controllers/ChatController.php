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
use App\Models\UnreadMessage;

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
            'user_ids.*' => 'exists:users,user_id',
            'community_id' => 'nullable|exists:communities,community_id'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // For private chats between two users
        if ($request->is_private) {
            if (count($request->user_ids) !== 1) {
                return response()->json(['error' => 'Private chats can only be created with exactly one other user'], 422);
            }
            $request->capacity = 2; // Force capacity to 2 for private chats
        }

        $group = ChatGroup::create([
            'name' => $request->name,
            'capacity' => $request->capacity,
            'is_private' => $request->is_private ?? false,
            'community_id' => $request->community_id
        ]);

        // Add the creator to the group
        $group->users()->attach(Auth::id());

        // Add other users to the group
        foreach ($request->user_ids as $user_id) {
            if (!$group->isAtCapacity()) {  
                $group->users()->attach($user_id);
            }
        }

        return response()->json($group->load(['users', 'community']), 201);
    }

    /**
     * Send a message to a chat group.
     */
    public function sendMessage(Request $request, $groupId)
    {
        try {
            \Log::info('Attempting to send message:', [
                'user_id' => Auth::id(),
                'group_id' => $groupId,
                'request_data' => $request->all()
            ]);

            // Validate group ID format
            if (!preg_match('/^chat_[A-Za-z0-9]{8}$/', $groupId)) {
                \Log::warning('Invalid group ID format:', ['group_id' => $groupId]);
                return response()->json(['error' => 'Invalid group ID format'], 400);
            }

            // Get chat group
            $chatGroup = ChatGroup::where('chat_group_id', $groupId)->first();
            if (!$chatGroup) {
                \Log::warning('Chat group not found:', ['group_id' => $groupId]);
                return response()->json(['error' => 'Chat group not found'], 404);
            }

            // Validate message
            $message = $request->input('message');
            if (empty($message)) {
                \Log::warning('Empty message content');
                return response()->json(['error' => 'Message cannot be empty'], 400);
            }

            // Check if user is member of the group
            $isMember = $chatGroup->users()->where('users.user_id', Auth::id())->exists();
            if (!$isMember) {
                \Log::warning('User is not a member of the group:', [
                    'user_id' => Auth::id(),
                    'group_id' => $groupId
                ]);
                return response()->json(['error' => 'You are not a member of this group'], 403);
            }

            DB::beginTransaction();
            try {
                // Create the message
                $messageModel = new Messages([
                    'chat_group_id' => $chatGroup->chat_group_id,
                    'user_id' => Auth::id(),
                    'message' => $message
                ]);
                
                if (!$messageModel->save()) {
                    throw new \Exception('Failed to save message');
                }

                // Create unread message entries for all group members except the sender
                $groupMembers = $chatGroup->users()
                    ->where('users.user_id', '!=', Auth::id())
                    ->get();

                foreach ($groupMembers as $member) {
                    try {
                        UnreadMessage::create([
                            'chat_group_id' => $chatGroup->chat_group_id,
                            'user_id' => $member->user_id,
                            'message_id' => $messageModel->message_id,
                            'is_read' => false
                        ]);
                    } catch (\Exception $e) {
                        \Log::warning('Failed to create unread message entry:', [
                            'error' => $e->getMessage(),
                            'user_id' => $member->user_id,
                            'message_id' => $messageModel->message_id
                        ]);
                        // Continue with other members even if one fails
                        continue;
                    }
                }
                
                // Load the user relationship
                $messageModel->load('user');
                
                DB::commit();
                
                \Log::info('Message created successfully:', [
                    'message_id' => $messageModel->message_id,
                    'chat_group_id' => $messageModel->chat_group_id,
                    'user_id' => $messageModel->user_id
                ]);

                try {
                    // Broadcast the new message
                    broadcast(new NewMessage($messageModel, $chatGroup->chat_group_id))->toOthers();
                } catch (\Exception $e) {
                    \Log::warning('Failed to broadcast message:', [
                        'error' => $e->getMessage(),
                        'message_id' => $messageModel->message_id
                    ]);
                    // Don't throw error here, just log it
                }

                return response()->json([
                    'message' => $messageModel,
                    'status' => 'success'
                ]);

            } catch (\Exception $e) {
                DB::rollBack();
                \Log::error('Error in transaction:', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                throw $e;
            }

        } catch (\Exception $e) {
            \Log::error('Error in sendMessage:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['error' => 'Failed to send message: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Get messages for a chat group.
     */
    public function getMessages(Request $request, $group)
    {
        try {
            \Log::info('Attempting to fetch messages for group: ' . $group, [
                'user_id' => Auth::id(),
                'request_data' => $request->all()
            ]);
            
            // Validate group ID format
            if (!Str::startsWith($group, 'chat_')) {
                \Log::error('Invalid group ID format:', ['group_id' => $group]);
                return response()->json(['error' => 'Invalid group ID format'], 400);
            }
            
            // Find the chat group with error handling
            try {
                $chatGroup = ChatGroup::findOrFail($group);
                \Log::info('Chat group found:', [
                    'group_id' => $chatGroup->chat_group_id,
                    'group_name' => $chatGroup->name
                ]);
            } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
                \Log::error('Chat group not found:', ['group_id' => $group]);
                return response()->json(['error' => 'Chat group not found'], 404);
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

            // Mark all unread messages as read when user loads the chat
            UnreadMessage::where('chat_group_id', $chatGroup->chat_group_id)
                ->where('user_id', Auth::id())
                ->where('is_read', false)
                ->update(['is_read' => true]);

            // Get messages with pagination and error handling
            try {
                // Log the query we're about to execute
                \Log::info('Executing message query for group:', ['group_id' => $group]);
                
                $messages = Messages::with(['user' => function($query) {
                        $query->select('user_id', 'name', 'photo')
                            ->withDefault([
                                'user_id' => null,
                                'name' => 'Unknown User',
                                'photo' => null
                            ]);
                    }])
                    ->where('chat_group_id', $chatGroup->chat_group_id)
                    ->orderBy('created_at', 'desc')
                    ->paginate(50);

                // Log the results
                \Log::info('Messages retrieved successfully:', [
                    'group_id' => $group,
                    'message_count' => $messages->count(),
                    'total_messages' => $messages->total(),
                    'first_message' => $messages->first() ? [
                        'message_id' => $messages->first()->message_id,
                        'user_id' => $messages->first()->user_id,
                        'created_at' => $messages->first()->created_at
                    ] : null
                ]);

                // Transform the response to ensure correct user data
                $transformedMessages = $messages->through(function ($message) {
                    return [
                        'message_id' => $message->message_id,
                        'chat_group_id' => $message->chat_group_id,
                        'user_id' => $message->user_id,
                        'message' => $message->message,
                        'created_at' => $message->created_at,
                        'updated_at' => $message->updated_at,
                        'user' => $message->user ? [
                            'user_id' => $message->user->user_id,
                            'name' => $message->user->name,
                            'photo' => $message->user->photo ? 
                                str_replace('storage/storage/', 'storage/', url('storage/' . $message->user->photo)) : 
                                null
                        ] : null
                    ];
                });

                return response()->json($transformedMessages);
            } catch (\Exception $e) {
                \Log::error('Failed to retrieve messages:', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                    'group_id' => $group,
                    'sql' => $e instanceof \Illuminate\Database\QueryException ? $e->getSql() : null
                ]);
                return response()->json([
                    'error' => 'Failed to retrieve messages',
                    'details' => $e->getMessage()
                ], 500);
            }
            
        } catch (\Exception $e) {
            \Log::error('Error in getMessages:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'group_id' => $group,
                'user_id' => Auth::id()
            ]);
            return response()->json([
                'error' => 'Failed to retrieve messages',
                'details' => $e->getMessage()
            ], 500);
        }
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
                ->with(['users', 'community']) // Load community relationship
                ->get()
                ->map(function ($group) {
                    // Get latest message for this group
                    $latestMessage = Messages::where('chat_group_id', $group->chat_group_id)
                        ->with('user')
                        ->latest()
                        ->first();
                    
                    $group->messages = $latestMessage ? [$latestMessage] : [];
                    
                    // Get unread message count
                    $unreadCount = UnreadMessage::where('chat_group_id', $group->chat_group_id)
                        ->where('user_id', Auth::id())
                        ->where('is_read', false)
                        ->count();
                    
                    $group->unread_count = $unreadCount;
                    
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

                    // Log community data for debugging
                    \Log::info('Chat group community data:', [
                        'chat_group_id' => $group->chat_group_id,
                        'community_id' => $group->community_id,
                        'community' => $group->community
                    ]);

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
        // Use the authenticated user's ID
        $user_id = Auth::id();

        // Check if user is a member of the group
        if (!$group->users()->where('chat_group_user.user_id', $user_id)->exists()) {
            return response()->json(['error' => 'You are not a member of this group'], 403);
        }

        if ($group->removeUser($user_id)) {
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
