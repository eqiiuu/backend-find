@extends('admin.layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="mb-0">Communities Management</h2>
    <a href="{{ route('admin.communities.create') }}" class="btn btn-primary">
        <i class='bx bx-plus'></i> Add New Community
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
                        <th>Name</th>
                        <th>Description</th>
                        <th>Owner</th>
                        <th>Members</th>
                        <th>Capacity</th>
                        <th>Location</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($communities as $community)
                    <tr>
                        <td class="text-muted">{{ $community->community_id }}</td>
                        <td>
                            @if($community->gambar)
                                <img src="{{ asset('storage/' . $community->gambar) }}" alt="Community Image" class="img-thumbnail" style="max-height: 50px;">
                            @else
                                <span class="text-muted">No image</span>
                            @endif
                        </td>
                        <td>{{ $community->name }}</td>
                        <td>{{ Str::limit($community->description, 50) }}</td>
                        <td>{{ $community->owner->name ?? 'N/A' }}</td>
                        <td>
                            @if($community->anggota && is_array($community->anggota))
                                <div class="d-flex flex-wrap gap-1">
                                    @foreach($community->anggota as $memberId)
                                        @php
                                            $member = $users->firstWhere('user_id', $memberId);
                                        @endphp
                                        @if($member)
                                            <span class="badge bg-info">{{ $member->name }}</span>
                                        @endif
                                    @endforeach
                                </div>
                            @else
                                <span class="text-muted">No members</span>
                            @endif
                        </td>
                        <td>{{ $community->capacity }}</td>
                        <td>
                            <small class="text-muted">
                                {{ number_format($community->latitude, 6) }}, 
                                {{ number_format($community->longitude, 6) }}
                            </small>
                        </td>
                        <td class="text-end">
                            <div class="btn-group">
                                <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#editCommunityModal{{ $community->community_id }}">
                                    <i class='bx bxs-edit'></i> Edit
                                </button>
                                <form action="{{ route('admin.communities.delete', $community->community_id) }}" method="POST" class="d-inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this community?')">
                                        <i class='bx bxs-trash'></i> Delete
                                    </button>
                                </form>
                            </div>

                            <!-- Edit Community Modal -->
                            <div class="modal fade" id="editCommunityModal{{ $community->community_id }}" tabindex="-1" aria-labelledby="editCommunityModalLabel{{ $community->community_id }}" aria-hidden="true">
                                <div class="modal-dialog modal-lg">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title" id="editCommunityModalLabel{{ $community->community_id }}">Edit Community</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <form action="{{ route('admin.communities.update', $community->community_id) }}" method="POST" enctype="multipart/form-data">
                                            @csrf
                                            @method('PUT')
                                            <div class="modal-body">
                                                <div class="row">
                                                    <div class="col-md-6">
                                                        <div class="mb-3">
                                                            <label for="name" class="form-label">Name</label>
                                                            <input type="text" class="form-control" id="name" name="name" value="{{ $community->name }}" required>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label for="description" class="form-label">Description</label>
                                                            <textarea class="form-control" id="description" name="description" rows="3" required>{{ $community->description }}</textarea>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label for="capacity" class="form-label">Capacity</label>
                                                            <input type="number" class="form-control" id="capacity" name="capacity" value="{{ $community->capacity }}" min="1" required>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label for="owner_name" class="form-label">Owner</label>
                                                            <input type="text" class="form-control" id="owner_name" name="owner_name" value="{{ $community->owner->name }}" required list="users-list">
                                                            <datalist id="users-list">
                                                                @foreach($users as $user)
                                                                    <option value="{{ $user->name }}">
                                                                @endforeach
                                                            </datalist>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label for="anggota{{ $community->community_id }}" class="form-label">Members</label>
                                                            <select class="form-select anggota-select" id="anggota{{ $community->community_id }}" name="anggota[]" multiple>
                                                                @foreach($users as $user)
                                                                    <option value="{{ $user->user_id }}" {{ in_array($user->user_id, $community->anggota ?? []) ? 'selected' : '' }}>
                                                                        {{ $user->name }}
                                                                    </option>
                                                                @endforeach
                                                            </select>
                                                            <small class="text-muted">Search and select multiple members</small>
                                                        </div>
                                                        <div class="row">
                                                            <div class="col-md-6">
                                                                <div class="mb-3">
                                                                    <label for="latitude" class="form-label">Latitude</label>
                                                                    <input type="number" step="any" class="form-control" id="latitude" name="latitude" value="{{ $community->latitude }}" required>
                                                                </div>
                                                            </div>
                                                            <div class="col-md-6">
                                                                <div class="mb-3">
                                                                    <label for="longitude" class="form-label">Longitude</label>
                                                                    <input type="number" step="any" class="form-control" id="longitude" name="longitude" value="{{ $community->longitude }}" required>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="mb-3">
                                                            <label class="form-label">Current Image</label>
                                                            @if($community->gambar)
                                                                <div class="mb-2">
                                                                    <img src="{{ asset('storage/' . $community->gambar) }}" alt="Current Image" class="img-fluid rounded">
                                                                </div>
                                                            @else
                                                                <p class="text-muted">No image uploaded</p>
                                                            @endif
                                                            <label for="gambar" class="form-label">Change Image</label>
                                                            <input type="file" class="form-control" id="gambar" name="gambar" accept="image/*">
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
            {{ $communities->links() }}
        </div>
    </div>
</div>
@endsection

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
    $(document).ready(function() {
        $('.anggota-select').each(function() {
            $(this).select2({
                placeholder: 'Select members',
                allowClear: true,
                width: '100%',
                dropdownParent: $(this).closest('.modal')
            });
        });
    });
</script>
@endpush 