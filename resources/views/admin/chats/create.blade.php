@extends('admin.layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="mb-0">Add New Chat Group</h2>
    <a href="{{ route('admin.chats.index') }}" class="btn btn-secondary">
        <i class='bx bx-arrow-back'></i> Back to Chat Groups
    </a>
</div>

<div class="card shadow-sm">
    <div class="card-body">
        <form action="{{ route('admin.chats.store') }}" method="POST">
            @csrf
            <div class="mb-3">
                <label for="name" class="form-label">Group Name</label>
                <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name') }}" required>
                @error('name')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3">
                <label for="capacity" class="form-label">Capacity</label>
                <input type="number" class="form-control @error('capacity') is-invalid @enderror" id="capacity" name="capacity" value="{{ old('capacity', 2) }}" min="2" required>
                @error('capacity')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3">
                <div class="form-check">
                    <input type="checkbox" class="form-check-input @error('is_private') is-invalid @enderror" id="is_private" name="is_private" value="1" {{ old('is_private') ? 'checked' : '' }}>
                    <label class="form-check-label" for="is_private">Private Chat</label>
                    @error('is_private')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="mb-3">
                <label for="user_ids" class="form-label">Members</label>
                <select class="form-select @error('user_ids') is-invalid @enderror" id="user_ids" name="user_ids[]" multiple required>
                    @foreach($users as $user)
                        <option value="{{ $user->user_id }}" {{ in_array($user->user_id, old('user_ids', [])) ? 'selected' : '' }}>
                            {{ $user->name }}
                        </option>
                    @endforeach
                </select>
                <small class="text-muted">Hold Ctrl/Cmd to select multiple members</small>
                @error('user_ids')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="text-end">
                <button type="submit" class="btn btn-primary">
                    <i class='bx bx-save'></i> Create Chat Group
                </button>
            </div>
        </form>
    </div>
</div>
@endsection 