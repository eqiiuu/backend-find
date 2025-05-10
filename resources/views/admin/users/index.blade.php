@extends('admin.layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="mb-0">Users Management</h2>
    <a href="{{ route('admin.users.create') }}" class="btn btn-primary">
        <i class='bx bx-plus'></i> Add New User
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
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Password</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($users as $user)
                    <tr>
                        <td class="text-muted">{{ $user->user_id }}</td>
                        <td>{{ $user->name }}</td>
                        <td>{{ $user->email }}</td>
                        <td>{{ $user->nomor_telepon }}</td>
                        <td>
                            <div class="input-group">
                                <input type="password" class="form-control form-control-sm" value="••••••••" readonly>
                                <button type="button" class="btn btn-sm btn-outline-secondary" 
                                        onclick="showResetPasswordModal('{{ $user->user_id }}', '{{ $user->name }}')"
                                        data-bs-toggle="modal" 
                                        data-bs-target="#resetPasswordModal">
                                    <i class='bx bx-refresh'></i> Reset
                                </button>
                            </div>
                        </td>
                        <td class="text-end">
                            <a href="{{ route('admin.users.edit', $user->user_id) }}" class="btn btn-sm btn-primary">
                                <i class='bx bxs-edit'></i> Edit
                            </a>
                            <form action="{{ route('admin.users.delete', $user->user_id) }}" method="POST" class="d-inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this user?')">
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
            {{ $users->links() }}
        </div>
    </div>
</div>

<!-- Reset Password Modal -->
<div class="modal fade" id="resetPasswordModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Reset Password</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to reset the password for <strong id="userName"></strong>?</p>
                <p class="text-muted">A new random password will be generated and displayed after reset.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <form id="resetPasswordForm" method="POST" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-primary">Reset Password</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
// Define the function in the global scope
function showResetPasswordModal(userId, userName) {
    // Update the modal content
    document.getElementById('userName').textContent = userName;
    
    // Set the form action to the correct reset password route
    const form = document.getElementById('resetPasswordForm');
    form.action = `/admin/users/${userId}/reset-password`;
    
    // Show the modal
    const modal = new bootstrap.Modal(document.getElementById('resetPasswordModal'));
    modal.show();
}
</script>
@endsection 