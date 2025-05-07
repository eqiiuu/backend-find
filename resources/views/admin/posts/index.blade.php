@extends('admin.layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="mb-0">Posts Management</h2>
    <a href="{{ route('admin.posts.create') }}" class="btn btn-primary">
        <i class='bx bx-plus'></i> Add New Post
    </a>
</div>

<div class="card shadow-sm">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Image</th>
                        <th>Title</th>
                        <th>Author</th>
                        <th>Community</th>
                        <th>Date</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($posts as $post)
                    <tr>
                        <td class="text-muted">{{ $post->post_id }}</td>
                        <td>
                            @if($post->image)
                                <img src="{{ asset($post->image) }}" alt="Post Image" class="img-thumbnail" style="max-height: 50px;">
                            @else
                                <span class="text-muted">No image</span>
                            @endif
                        </td>
                        <td>{{ Str::limit($post->title, 50) }}</td>
                        <td>{{ $post->user->name ?? 'N/A' }}</td>
                        <td>{{ $post->community->description ?? 'N/A' }}</td>
                        <td>
                            <small class="text-muted">
                                {{ $post->post_date->format('M d, Y') }}
                            </small>
                        </td>
                        <td class="text-end">
                            <div class="btn-group">
                                <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#editPostModal{{ $post->post_id }}">
                                    <i class='bx bxs-edit'></i> Edit
                                </button>
                                <form action="{{ route('admin.posts.delete', $post->post_id) }}" method="POST" class="d-inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this post?')">
                                        <i class='bx bxs-trash'></i> Delete
                                    </button>
                                </form>
                            </div>

                            <!-- Edit Post Modal -->
                            <div class="modal fade" id="editPostModal{{ $post->post_id }}" tabindex="-1" aria-labelledby="editPostModalLabel{{ $post->post_id }}" aria-hidden="true">
                                <div class="modal-dialog modal-lg">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title" id="editPostModalLabel{{ $post->post_id }}">Edit Post</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <form action="{{ route('admin.posts.update', $post->post_id) }}" method="POST" enctype="multipart/form-data">
                                            @csrf
                                            @method('PUT')
                                            <div class="modal-body">
                                                <div class="row">
                                                    <div class="col-md-6">
                                                        <div class="mb-3">
                                                            <label for="title" class="form-label">Title</label>
                                                            <input type="text" class="form-control" id="title" name="title" value="{{ $post->title }}" required>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label for="description" class="form-label">Description</label>
                                                            <textarea class="form-control" id="description" name="description" rows="3" required>{{ $post->description }}</textarea>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label for="community_id" class="form-label">Community</label>
                                                            <select class="form-select" id="community_id" name="community_id" required>
                                                                @foreach($communities as $community)
                                                                    <option value="{{ $community->community_id }}" {{ $post->community_id == $community->community_id ? 'selected' : '' }}>
                                                                        {{ $community->description }}
                                                                    </option>
                                                                @endforeach
                                                            </select>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="mb-3">
                                                            <label class="form-label">Current Image</label>
                                                            @if($post->image)
                                                                <div class="mb-2">
                                                                    <img src="{{ asset($post->image) }}" alt="Current Image" class="img-fluid rounded">
                                                                </div>
                                                            @else
                                                                <p class="text-muted">No image uploaded</p>
                                                            @endif
                                                            <label for="image" class="form-label">Change Image</label>
                                                            <input type="file" class="form-control" id="image" name="image" accept="image/*">
                                                            <small class="text-muted">Leave empty to keep current image</small>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                <button type="submit" class="btn btn-primary">Save Changes</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="mt-4">
            {{ $posts->links() }}
        </div>
    </div>
</div>
@endsection 