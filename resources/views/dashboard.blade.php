@extends('admin.layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="mb-0">Dashboard Overview</h2>
</div>

<!-- Statistics Cards -->
<div class="row g-4 mb-4">
    <div class="col-md-4">
        <div class="card stat-card bg-primary text-white shadow-sm">
            <div class="card-body text-center">
                <i class='bx bxs-user stat-icon'></i>
                <h5 class="card-title">Total Users</h5>
                <h2 class="mb-0">{{ $stats['total_users'] }}</h2>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card stat-card bg-success text-white shadow-sm">
            <div class="card-body text-center">
                <i class='bx bxs-message-square-dots stat-icon'></i>
                <h5 class="card-title">Total Posts</h5>
                <h2 class="mb-0">{{ $stats['total_posts'] }}</h2>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card stat-card bg-info text-white shadow-sm">
            <div class="card-body text-center">
                <i class='bx bxs-group stat-icon'></i>
                <h5 class="card-title">Communities</h5>
                <h2 class="mb-0">{{ $stats['total_communities'] }}</h2>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- Recent Posts -->
    <div class="col-md-6">
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
                                <th>Community ID</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($stats['recent_posts'] as $post)
                            <tr>
                                <td>{{ Str::limit($post->title, 20) }}</td>
                                <td class="text-muted">{{ $post->user_id }}</td>
                                <td class="text-muted">{{ $post->community_id }}</td>
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
    <div class="col-md-6">
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
</div>
@endsection 