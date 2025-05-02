<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Post;
use App\Models\comments;

class Postingan extends Controller
{
    public function index()
    {
        return Post::all();
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

        $imagePath = null;

        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('images', 'public');
        }
    
        Post::create([
            'title' => $request->title,
            'description' => $request->description,
            'image' => $imagePath,
            'user_id' => $request->user_id,
            'community_id' => $request->community_id,
            'post_date' => now(),
            'comments' => [],
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
}
