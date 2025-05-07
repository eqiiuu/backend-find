@extends('admin.layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="mb-0">Edit Post</h2>
    <a href="{{ route('admin.posts.index') }}" class="btn btn-secondary">
        <i class='bx bx-arrow-back'></i> Back to Posts
    </a>
</div>

<div class="card shadow-sm">
    <div class="card-body">
        <form action="{{ route('admin.posts.update', $post->post_id) }}" method="POST" enctype="multipart/form-data">
            @csrf
            @method('PUT')
            <div class="mb-3">
                <label for="title" class="form-label">Title</label>
                <input type="text" class="form-control @error('title') is-invalid @enderror" id="title" name="title" value="{{ old('title', $post->title) }}" required>
                @error('title')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3">
                <label for="description" class="form-label">Description</label>
                <textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description" rows="3" required>{{ old('description', $post->description) }}</textarea>
                @error('description')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3">
                <label for="user_id" class="form-label">Author</label>
                <select class="form-select @error('user_id') is-invalid @enderror" id="user_id" name="user_id" required>
                    <option value="">Select Author</option>
                    @foreach($users as $user)
                        <option value="{{ $user->user_id }}" {{ old('user_id', $post->user_id) == $user->user_id ? 'selected' : '' }}>
                            {{ $user->name }}
                        </option>
                    @endforeach
                </select>
                @error('user_id')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3">
                <label for="community_id" class="form-label">Community</label>
                <select class="form-select @error('community_id') is-invalid @enderror" id="community_id" name="community_id" required>
                    <option value="">Select Community</option>
                    @foreach($communities as $community)
                        <option value="{{ $community->community_id }}" {{ old('community_id', $post->community_id) == $community->community_id ? 'selected' : '' }}>
                            {{ $community->description }}
                        </option>
                    @endforeach
                </select>
                @error('community_id')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3">
                <label for="image" class="form-label">Image</label>
                @if($post->image)
                    <div class="mb-2">
                        <img src="{{ asset($post->image) }}" alt="Current Image" class="img-thumbnail" style="max-height: 200px;">
                    </div>
                @endif
                <input type="file" class="form-control @error('image') is-invalid @enderror" id="image" name="image" accept="image/*">
                @error('image')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="text-end">
                <button type="submit" class="btn btn-primary">
                    <i class='bx bx-save'></i> Update Post
                </button>
            </div>
        </form>
    </div>
</div>
@endsection 