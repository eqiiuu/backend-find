<?php

namespace App\Http\Controllers;

use App\Models\ChatGroup;
use App\Models\Messages;
use App\Events\NewMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

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
    public function sendMessage(Request $request, ChatGroup $group)
    {
        $validator = Validator::make($request->all(), [
            'message' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Check if user is a member of the group
        if (!$group->users()->where('chat_group_user.user_id', Auth::id())->exists()) {
            return response()->json(['error' => 'You are not a member of this group'], 403);
        }

        $message = Messages::create([
            'chat_group_id' => $group->id,
            'user_id' => Auth::id(),
            'message' => $request->message
        ]);

        broadcast(new NewMessage($message, $group->id))->toOthers();

        return response()->json($message->load('user'), 201);
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
        $groups = Auth::user()->chatGroups()
            ->with(['users', 'messages' => function($query) {
                $query->latest()->take(1);
            }])
            ->get()
            ->map(function ($group) {
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
}
