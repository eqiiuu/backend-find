<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Post;

class Postingan extends Controller
{
    public function index()
    {
        return Post::all();
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'post_id' => 'required',
            'user_id' => 'required',
            'community_id' => 'required',
            'post_date' => 'required',
            'comments' => 'nullable',
        ]);

        $post = Post::create($validated);
        return response()->json($post, 201);
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
