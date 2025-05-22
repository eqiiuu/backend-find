<?php

use Illuminate\Support\Facades\Broadcast;
use App\Models\ChatGroup;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. The given channel authorization callbacks are
| used to check if an authenticated user can listen to the channel.
|
*/

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('chat.group.{groupId}', function ($user, $groupId) {
    $group = ChatGroup::findOrFail($groupId);
    
    // Check if the user is a member of this chat group
    if ($group->users()->where('chat_group_user.user_id', $user->user_id)->exists()) {
        // Return user information for presence channel
        return [
            'id' => $user->user_id,
            'name' => $user->name,
            'profile_photo_url' => $user->profile_photo_url
        ];
    }
    
    return false;
});
