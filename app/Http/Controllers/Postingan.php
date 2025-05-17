<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Post;
use App\Models\comments;
use App\Models\Communitie;
use App\Models\Like;

class Postingan extends Controller
{
    public function index()
    {
        // Get all posts with community and user information
        $posts = Post::with(['community', 'user', 'likes'])
                    ->orderBy('created_at', 'desc')
                    ->get();
        
        return response()->json($posts);
    }

    
    public function post(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:5120',
            'community_id' => 'required',
            'user_id' => 'required',
        ]);

        // Get the community and check permissions
        $community = Communitie::findOrFail($request->community_id);
        $user_id = auth()->user()->user_id;

        // Check if user is the owner or if members are allowed to post
        if ($community->owner_id !== $user_id && !$community->isMemberPostable) {
            return response()->json([
                'error' => 'You are not allowed to post in this community. Only the owner can post.'
            ], 403);
        }

        $imagePath = null;

        if ($request->hasFile('image')) {
            // Store in images/posts folder
            $imagePath = $request->file('image')->store('images/posts', 'public');
            \Log::info('Stored post image at: ' . $imagePath);
        }
    
        Post::create([
            'title' => $request->title,
            'description' => $request->description,
            'image' => $imagePath,
            'user_id' => $user_id,
            'community_id' => $request->community_id,
            'post_date' => now(),
            'comments' => []
        ]);
        
        return response()->json(['message' => 'BERHASIL MENAMBAHKAN POST!']);
    }

    public function addComment(Request $request)
    {
        $request->validate([
            'post_id' => 'required|string',
            'content' => 'required|string',
            'parent_id' => 'nullable|string'
        ]);

        $comment = comments::create([
            'comment_id' => uniqid('cmt_'),
            'post_id' => $request->post_id,
            'user_id' => auth()->user()->user_id,
            'parent_id' => $request->parent_id,
            'content' => $request->content
        ]);

        // Update the post's comments array with the new comment ID
        $post = Post::findOrFail($request->post_id);
        $currentComments = is_array($post->comments) ? $post->comments : [];
        $currentComments[] = $comment->comment_id;
        $post->comments = $currentComments;
        $post->save();

        // Load the comment with its relationships
        $comment->load(['user', 'replies.user']);

        return response()->json([
            'message' => 'Comment added successfully',
            'comment' => $comment
        ], 201);
    }

    public function getComments($post_id)
    {
        $comments = comments::with(['user', 'replies.user'])
            ->where('post_id', $post_id)
            ->whereNull('parent_id')
            ->get();

        return response()->json($comments);
    }

    public function deleteComment($comment_id)
    {
        $comment = comments::findOrFail($comment_id);
        
        // Check if the user is authorized to delete the comment
        if ($comment->user_id !== auth()->user()->user_id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Remove comment ID from post's comments array
        $post = Post::findOrFail($comment->post_id);
        $comments = $post->comments ?? [];
        $comments = array_diff($comments, [$comment_id]);
        $post->update(['comments' => array_values($comments)]);

        $comment->delete();
        return response()->json(['message' => 'Comment deleted successfully']);
    }

    public function show($id)
    {
        return Post::findOrFail($id);
    }

    public function update(Request $request, $id)
    {
        $post = Post::findOrFail($id);
        $post->update($request->all());
        return response()->json($post);
    }

    public function destroy($id)
    {
        Post::destroy($id);
        return response()->json(['message' => 'Post deleted']);
    }

    public function getUserCommunityPosts()
    {
        $user_id = auth()->user()->user_id;
        
        // Get all communities where user is a member (either as owner or in anggota array)
        $posts = Post::whereHas('community', function($query) use ($user_id) {
            $query->where('owner_id', $user_id)
                  ->orWhereJsonContains('anggota', $user_id);
        })
        ->with(['user', 'community', 'comments' => function($query) {
            $query->with('user');
        }, 'likes'])
        ->latest('post_date')
        ->paginate(10);

        return response()->json($posts);
    }

    public function toggleLike($postId)
    {
        try {
            $user_id = auth()->user()->user_id;
            $post = Post::findOrFail($postId);
            
            $existingLike = Like::where('user_id', $user_id)
                               ->where('post_id', $postId)
                               ->first();

            if ($existingLike) {
                $existingLike->delete();
                return response()->json([
                    'message' => 'Post unliked successfully',
                    'is_liked' => false,
                    'likes_count' => $post->likes()->count()
                ]);
            }

            Like::create([
                'user_id' => $user_id,
                'post_id' => $postId
            ]);

            return response()->json([
                'message' => 'Post liked successfully',
                'is_liked' => true,
                'likes_count' => $post->likes()->count()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error toggling like',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}