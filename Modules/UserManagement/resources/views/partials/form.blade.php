<div class="mb-3">
    <label for="name" class="form-label">Nama Lengkap <span class="text-danger">*</span></label>
    <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $user?->name) }}" required>
    @error('name')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="mb-3">
    <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
    <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email" value="{{ old('email', $user?->email) }}" placeholder="nama@email.com" required>
    @error('email')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="mb-3">
    <label for="role" class="form-label">Role <span class="text-danger">*</span></label>
    <select class="form-select @error('role') is-invalid @enderror" id="role" name="role" required>
        @foreach ($roles as $role)
            <option value="{{ $role->value }}" @selected(old('role', $user?->role->value ?? 'user') === $role->value)>{{ $role->label() }}</option>
        @endforeach
    </select>
    @error('role')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
    <div class="form-text">Admin dapat mengelola user dan pendaftaran. User hanya dapat mengelola pendaftaran.</div>
</div>

<hr class="my-4">

<div class="row g-3 mb-3">
    <div class="col-md-6">
        <label for="password" class="form-label">
            Password @unless ($user)<span class="text-danger">*</span>@endunless
        </label>
        <div class="input-group has-validation">
            <input type="password" class="form-control @error('password') is-invalid @enderror" id="password" name="password" autocomplete="new-password" @required(! $user)>
            <button type="button" class="btn btn-outline-secondary" data-toggle-password="password" aria-label="Tampilkan password">
                <i class="bi bi-eye"></i>
            </button>
            @error('password')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
        <div class="form-text">
            {{ $user ? 'Kosongkan jika tidak ingin mengubah password.' : 'Minimal 8 karakter.' }}
        </div>
    </div>

    <div class="col-md-6">
        <label for="password_confirmation" class="form-label">Ulangi Password</label>
        <input type="password" class="form-control" id="password_confirmation" name="password_confirmation" autocomplete="new-password" @required(! $user)>
    </div>
</div>