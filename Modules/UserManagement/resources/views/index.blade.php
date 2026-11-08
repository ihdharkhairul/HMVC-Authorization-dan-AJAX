@extends('layouts.app')

@section('title', 'Kelola User')

@section('content')
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <div>
            <h4 class="mb-1">Kelola User</h4>
            <p class="text-muted mb-0 small">Tambah, ubah, dan hapus akun pengguna aplikasi.</p>
        </div>
        <a href="{{ route('admin.users.create') }}" class="btn btn-primary">
            <i class="bi bi-person-plus me-1"></i> Tambah User
        </a>
    </div>

    <div class="card">
        <div class="card-header py-3">
            <form method="GET" action="{{ route('admin.users.index') }}" class="row g-2 align-items-center">
                <div class="col-sm-6 col-lg-4">
                    <div class="input-group">
                        <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
                        <input type="search" name="search" value="{{ $search }}" class="form-control border-start-0" placeholder="Cari nama atau email...">
                    </div>
                </div>
                <div class="col-sm-3 col-lg-2">
                    <select name="role" class="form-select" onchange="this.form.submit()" aria-label="Filter role">
                        <option value="">Semua role</option>
                        @foreach ($roles as $role)
                            <option value="{{ $role->value }}" @selected($selectedRole === $role)>{{ $role->label() }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-auto">
                    <button type="submit" class="btn btn-light border">Cari</button>
                    @if ($search !== '' || $selectedRole)
                        <a href="{{ route('admin.users.index') }}" class="btn btn-link text-decoration-none">Reset</a>
                    @endif
                </div>
            </form>
        </div>

        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th width="5%">No</th>
                        <th>Nama</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th class="text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($users as $user)
                        @php($isCurrentUser = $user->is(auth()->user()))
                        <tr>
                            <td class="text-muted">{{ $users->firstItem() + $loop->index }}</td>
                            <td class="fw-medium">
                                {{ $user->name }}
                                @if ($isCurrentUser)
                                    <span class="badge text-bg-light border ms-1">Anda</span>
                                @endif
                            </td>
                            <td>{{ $user->email }}</td>
                            <td>
                                <span class="badge {{ $user->isAdmin() ? 'text-bg-primary' : 'text-bg-secondary' }}">{{ $user->role->label() }}</span>
                            </td>
                            <td class="text-end text-nowrap">
                                <a href="{{ route('admin.users.edit', $user) }}" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-pencil"></i> Edit
                                </a>
                                @unless ($isCurrentUser)
                                    <form action="{{ route('admin.users.destroy', $user) }}" method="POST" class="d-inline" data-confirm="Akun &quot;{{ $user->name }}&quot; akan dihapus dan tidak bisa dikembalikan.">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger">
                                            <i class="bi bi-trash"></i> Hapus
                                        </button>
                                    </form>
                                @endunless
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5">
                                <div class="empty-state">
                                    <i class="bi bi-person-x"></i>
                                    <p class="mt-2 mb-0">Tidak ada user yang cocok dengan pencarian.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($users->hasPages())
            <div class="card-footer bg-white d-flex flex-wrap justify-content-between align-items-center gap-2">
                <small class="text-muted">Menampilkan {{ $users->firstItem() }}-{{ $users->lastItem() }} dari {{ $users->total() }} user</small>
                {{ $users->links('pagination::bootstrap-4') }}
            </div>
        @endif
    </div>
@endsection