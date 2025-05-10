@extends('admin.layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="mb-0">Dashboard Overview</h2>
</div>

<!-- Last Password Reset -->
@if(session('last_reset_password'))
<div class="alert alert-info mb-4">
    <h5 class="alert-heading">Last Password Reset</h5>
    <p class="mb-0">
        <strong>User:</strong> {{ session('last_reset_password')['user_name'] }}<br>
        <strong>New Password:</strong> {{ session('last_reset_password')['password'] }}<br>
        <strong>Time:</strong> {{ session('last_reset_password')['timestamp']->format('M d, Y H:i:s') }}
    </p>
</div>
@endif

<!-- Statistics Cards -->
<div class="row g-4 mb-4">
    <div class="col-md-3">
        <div class="card stat-card bg-primary text-white shadow-sm">
            <div class="card-body text-center">
                <i class='bx bxs-user stat-icon'></i>
                <h5 class="card-title">Total Users</h5>
                <h2 class="mb-0">{{ $stats['total_users'] }}</h2>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card bg-success text-white shadow-sm">
            <div class="card-body text-center">
                <i class='bx bxs-message-square-dots stat-icon'></i>
                <h5 class="card-title">Total Posts</h5>
                <h2 class="mb-0">{{ $stats['total_posts'] }}</h2>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card bg-info text-white shadow-sm">
            <div class="card-body text-center">
                <i class='bx bxs-group stat-icon'></i>
                <h5 class="card-title">Communities</h5>
                <h2 class="mb-0">{{ $stats['total_communities'] }}</h2>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card bg-warning text-white shadow-sm">
            <div class="card-body text-center">
                <i class='bx bxs-chat stat-icon'></i>
                <h5 class="card-title">Chat Groups</h5>
                <h2 class="mb-0">{{ $stats['total_chats'] }}</h2>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- Recent Posts -->
    <div class="col-md-4">
        <div class="card shadow-sm">
            <div class="card-header bg-white">
                <h5 class="card-title mb-0">Recent Posts</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr>
                                <th>Title</th>
                                <th>User ID</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($stats['recent_posts'] as $post)
                            <tr>
                                <td>{{ Str::limit($post->title, 20) }}</td>
                                <td class="text-muted">{{ $post->user_id }}</td>
                                <td>
                                    <small class="text-muted">
                                        {{ $post->post_date->format('M d, Y') }}
                                    </small>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Communities -->
    <div class="col-md-4">
        <div class="card shadow-sm">
            <div class="card-header bg-white">
                <h5 class="card-title mb-0">Recent Communities</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr>
                                <th>Description</th>
                                <th>Owner ID</th>
                                <th>Capacity</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($stats['recent_communities'] as $community)
                            <tr>
                                <td>{{ Str::limit($community->description, 30) }}</td>
                                <td class="text-muted">{{ $community->owner_id }}</td>
                                <td>{{ $community->capacity }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Chat Groups -->
    <div class="col-md-4">
        <div class="card shadow-sm">
            <div class="card-header bg-white">
                <h5 class="card-title mb-0">Recent Chat Groups</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Type</th>
                                <th>Members</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($stats['recent_chats'] as $chat)
                            <tr>
                                <td>{{ Str::limit($chat->name, 20) }}</td>
                                <td>
                                    @if($chat->is_private)
                                        <span class="badge bg-info">Private</span>
                                    @else
                                        <span class="badge bg-success">Public</span>
                                    @endif
                                </td>
                                <td>{{ $chat->users->count() }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection 