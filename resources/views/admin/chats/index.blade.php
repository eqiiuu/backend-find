@extends('admin.layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="mb-0">Chat Groups Management</h2>
    <a href="{{ route('admin.chats.create') }}" class="btn btn-primary">
        <i class='bx bx-plus'></i> Add New Chat Group
    </a>
</div>

<div class="card shadow-sm">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Type</th>
                        <th>Capacity</th>
                        <th>Members</th>
                        <th>Created</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($chatGroups as $group)
                    <tr>
                        <td class="text-muted">{{ $group->chat_group_id }}</td>
                        <td>{{ $group->name }}</td>
                        <td>
                            @if($group->is_private)
                                <span class="badge bg-info">Private</span>
                            @else
                                <span class="badge bg-success">Public</span>
                            @endif
                        </td>
                        <td>{{ $group->capacity }}</td>
                        <td>
                            <div class="d-flex flex-wrap gap-1">
                                @foreach($group->users as $user)
                                    <span class="badge bg-secondary">{{ $user->name }}</span>
                                @endforeach
                            </div>
                        </td>
                        <td>
                            <small class="text-muted">
                                {{ $group->created_at->format('M d, Y') }}
                            </small>
                        </td>
                        <td class="text-end">
                            <a href="{{ route('admin.chats.edit', $group->chat_group_id) }}" class="btn btn-sm btn-primary">
                                <i class='bx bxs-edit'></i> Edit
                            </a>
                            <form action="{{ route('admin.chats.delete', $group->chat_group_id) }}" method="POST" class="d-inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this chat group?')">
                                    <i class='bx bxs-trash'></i> Delete
                                </button>
                            </form>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="mt-4">
            {{ $chatGroups->links() }}
        </div>
    </div>
</div>
@endsection 