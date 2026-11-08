@extends('layouts.app')

@section('title', 'Pendaftaran')

@section('content')
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <div>
            <h4 class="mb-1">Pendaftaran</h4>
            <p class="text-muted mb-0 small">Data tersimpan tanpa memuat ulang halaman.</p>
        </div>
        <button type="button" class="btn btn-primary" id="addBtn">
            <i class="bi bi-plus-lg me-1"></i> Tambah Pendaftar
        </button>
    </div>

    <div id="alert-container"></div>

    <div class="card">
        <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2 py-3">
            <span class="fw-semibold">
                Daftar Pendaftar <span class="badge text-bg-light border ms-1" id="total-count">0</span>
            </span>
            <div class="input-group" style="max-width: 300px">
                <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
                <input type="search" id="search" class="form-control border-start-0" placeholder="Cari pendaftar...">
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-hover" id="pendaftaran-table">
                <thead>
                    <tr>
                        <th width="5%">No</th>
                        <th>Nama</th>
                        <th>Asal Sekolah</th>
                        <th>Prodi Tujuan</th>
                        <th>Kontak</th>
                        <th>Berkas</th>
                        <th class="text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td colspan="7" class="text-center text-muted py-5">
                            <div class="spinner-border spinner-border-sm me-2" role="status"></div> Memuat data...
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal form -->
    <div class="modal fade" id="pendaftaranModal" tabindex="-1" aria-labelledby="pendaftaranModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="pendaftaranModalLabel">Tambah Pendaftar</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div class="modal-body">
                    <form id="pendaftaranForm">
                        @csrf
                        <input type="hidden" name="id" id="id">

                        @foreach ([
                            'nama' => ['label' => 'Nama Lengkap', 'type' => 'text', 'placeholder' => 'Nama pendaftar'],
                            'asal_sekolah' => ['label' => 'Asal Sekolah', 'type' => 'text', 'placeholder' => 'Contoh: SMA Negeri 1 Bandung'],
                            'prodi_tujuan' => ['label' => 'Prodi Tujuan', 'type' => 'text', 'placeholder' => 'Contoh: Informatika'],
                            'email' => ['label' => 'Email', 'type' => 'email', 'placeholder' => 'nama@email.com'],
                            'no_hp' => ['label' => 'No HP', 'type' => 'tel', 'placeholder' => 'Contoh: 081234567890'],
                        ] as $name => $field)
                            <div class="mb-3">
                                <label for="{{ $name }}" class="form-label">{{ $field['label'] }} <span class="text-danger">*</span></label>
                                <input type="{{ $field['type'] }}" class="form-control" id="{{ $name }}" name="{{ $name }}" placeholder="{{ $field['placeholder'] }}" required>
                                <div class="invalid-feedback" id="{{ $name }}-error"></div>
                            </div>
                        @endforeach

                        <div class="mb-3">
                            <label for="berkas" class="form-label">
                                Berkas Pendukung <span class="text-danger" id="berkas-required">*</span>
                            </label>
                            <div class="small mb-2 d-none" id="berkas-current">
                                Berkas saat ini: <a href="#" target="_blank" rel="noopener" id="berkas-current-link"></a>
                            </div>
                            <input type="file" class="form-control" id="berkas" name="berkas" accept=".jpg,.jpeg,.png,.pdf">
                            <div class="invalid-feedback" id="berkas-error"></div>
                            <div class="form-text" id="berkas-help">Format jpg, jpeg, png, atau pdf. Maksimal 2 MB.</div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                    <button type="button" class="btn btn-primary" id="saveBtn">
                        <span class="spinner-border spinner-border-sm me-1 d-none" role="status" aria-hidden="true"></span>
                        <span class="submit-text">Simpan</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    $(document).ready(function () {
        const urls = {
            data: "{{ route('pendaftaran.data') }}",
            store: "{{ route('pendaftaran.store') }}",
            edit: "{{ route('pendaftaran.edit', ':id') }}",
            update: "{{ route('pendaftaran.update', ':id') }}",
            destroy: "{{ route('pendaftaran.destroy', ':id') }}",
        };

        let records = [];

        // Set CSRF token untuk semua request AJAX
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                'Accept': 'application/json'
            }
        });

        // Escape karakter HTML (termasuk tanda kutip untuk atribut) agar data user tidak dieksekusi sebagai script (XSS)
        function escapeHtml(value) {
            return String(value ?? '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        // Link untuk membuka berkas di tab baru
        function fileLink(item) {
            if (! item.berkas_url) {
                return '<span class="text-muted">-</span>';
            }

            const isPdf = (item.nama_berkas || item.berkas).toLowerCase().endsWith('.pdf');
            const icon = isPdf ? 'bi-file-earmark-pdf text-danger' : 'bi-file-earmark-image text-primary';

            return `<a href="${escapeHtml(item.berkas_url)}" target="_blank" rel="noopener" class="small text-decoration-none">
                        <i class="bi ${icon} me-1"></i>${escapeHtml(item.nama_berkas || 'Lihat berkas')}
                    </a>`;
        }

        // Mode input berkas: wajib saat tambah, opsional saat edit
        function setFileMode(item = null) {
            $('#berkas').val('');
            $('#berkas-required').toggleClass('d-none', item !== null);
            $('#berkas-current').toggleClass('d-none', ! item?.berkas_url);
            $('#berkas-help').text(item
                ? 'Kosongkan jika tidak ingin mengganti berkas. Format jpg, jpeg, png, atau pdf. Maksimal 2 MB.'
                : 'Format jpg, jpeg, png, atau pdf. Maksimal 2 MB.');

            if (item?.berkas_url) {
                $('#berkas-current-link').attr('href', item.berkas_url).text(item.nama_berkas || 'Lihat berkas');
            }
        }

        function showAlert(message, type = 'success') {
            const icon = type === 'success' ? 'bi-check-circle' : 'bi-exclamation-triangle';
            const alert = $(`
                <div class="alert alert-${type} alert-dismissible fade show d-flex align-items-center gap-2" role="alert">
                    <i class="bi ${icon}"></i>
                    <span>${escapeHtml(message)}</span>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
                </div>
            `);

            $('#alert-container').html(alert);
            setTimeout(() => bootstrap.Alert.getOrCreateInstance(alert[0]).close(), 4000);
        }

        function clearErrors() {
            $('#pendaftaranForm .invalid-feedback').text('');
            $('#pendaftaranForm .is-invalid').removeClass('is-invalid');
        }

        function emptyRow(icon, text) {
            return `
                <tr>
                    <td colspan="7">
                        <div class="empty-state">
                            <i class="bi ${icon}"></i>
                            <p class="mt-2 mb-0">${text}</p>
                        </div>
                    </td>
                </tr>`;
        }

        // Tampilkan data ke tabel sesuai kata kunci pencarian
        function renderTable() {
            const keyword = $('#search').val().trim().toLowerCase();
            const rows = records.filter((item) =>
                [item.nama, item.asal_sekolah, item.prodi_tujuan, item.email, item.no_hp]
                    .some((value) => (value || '').toLowerCase().includes(keyword))
            );

            $('#total-count').text(records.length);

            if (records.length === 0) {
                $('#pendaftaran-table tbody').html(emptyRow('bi-inbox', 'Belum ada pendaftar. Klik <strong>Tambah Pendaftar</strong> untuk memulai.'));
                return;
            }

            if (rows.length === 0) {
                $('#pendaftaran-table tbody').html(emptyRow('bi-search', 'Tidak ada pendaftar yang cocok dengan pencarian.'));
                return;
            }

            const html = rows.map((item, index) => `
                <tr>
                    <td class="text-muted">${index + 1}</td>
                    <td class="fw-medium">${escapeHtml(item.nama)}</td>
                    <td>${escapeHtml(item.asal_sekolah)}</td>
                    <td><span class="badge text-bg-light border">${escapeHtml(item.prodi_tujuan)}</span></td>
                    <td class="small">
                        <div>${escapeHtml(item.email)}</div>
                        <div class="text-muted">${escapeHtml(item.no_hp)}</div>
                    </td>
                    <td>${fileLink(item)}</td>
                    <td class="text-end text-nowrap">
                        <button data-id="${item.id}" class="edit btn btn-sm btn-outline-primary">
                            <i class="bi bi-pencil"></i> Edit
                        </button>
                        <button data-id="${item.id}" data-nama="${escapeHtml(item.nama)}" class="delete btn btn-sm btn-outline-danger">
                            <i class="bi bi-trash"></i> Hapus
                        </button>
                    </td>
                </tr>
            `).join('');

            $('#pendaftaran-table tbody').html(html);
        }

        // Muat data dari server
        function loadData() {
            $.ajax({
                url: urls.data,
                type: 'GET',
                dataType: 'json',
                success: function (data) {
                    records = data;
                    renderTable();
                },
                error: function (xhr) {
                    console.error('Error:', xhr.responseText);
                    $('#pendaftaran-table tbody').html(emptyRow('bi-wifi-off', 'Gagal memuat data. Silakan refresh halaman.'));
                }
            });
        }

        loadData();
        $('#search').on('input', renderTable);

        const pendaftaranModal = new bootstrap.Modal(document.getElementById('pendaftaranModal'));

        $('#pendaftaranModal').on('shown.bs.modal', function () {
            $('#nama').trigger('focus');
        });

        $('#pendaftaranModal').on('hidden.bs.modal', function () {
            $('#pendaftaranForm').trigger('reset');
            $('#id').val('');
            clearErrors();
            setFileMode();
        });

        // Tambah data
        $('#addBtn').click(function () {
            $('#pendaftaranModalLabel').text('Tambah Pendaftar');
            setFileMode();
            pendaftaranModal.show();
        });

        // Tekan Enter di form = simpan
        $('#pendaftaranForm').on('submit', function (e) {
            e.preventDefault();
            $('#saveBtn').trigger('click');
        });

        // Simpan data (create / update)
        $('#saveBtn').click(function () {
            const btn = $(this);
            const id = $('#id').val();

            btn.prop('disabled', true);
            btn.find('.submit-text').text('Menyimpan...');
            btn.find('.spinner-border').removeClass('d-none');
            clearErrors();

            // FormData dipakai (bukan serialize) agar file ikut terkirim
            const formData = new FormData(document.getElementById('pendaftaranForm'));
            if (id) {
                formData.append('_method', 'PUT');
            }

            $.ajax({
                url: id ? urls.update.replace(':id', id) : urls.store,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                dataType: 'json',
                success: function (response) {
                    pendaftaranModal.hide();
                    loadData();
                    showAlert(response.success);
                },
                error: function (xhr) {
                    if (xhr.status === 422) {
                        $.each(xhr.responseJSON.errors, function (key, value) {
                            $(`#${key}`).addClass('is-invalid');
                            $(`#${key}-error`).text(value[0]);
                        });
                        $('#pendaftaranForm .is-invalid:first').focus();
                    } else {
                        console.error('Error:', xhr.responseText);
                        pendaftaranModal.hide();
                        showAlert('Terjadi kesalahan. Silakan coba lagi.', 'danger');
                    }
                },
                complete: function () {
                    btn.prop('disabled', false);
                    btn.find('.submit-text').text('Simpan');
                    btn.find('.spinner-border').addClass('d-none');
                }
            });
        });

        // Edit data
        $(document).on('click', '.edit', function () {
            const id = $(this).data('id');

            $.get(urls.edit.replace(':id', id), function (data) {
                $('#pendaftaranModalLabel').text('Edit Pendaftar');
                $('#id').val(data.id);
                $('#nama').val(data.nama);
                $('#asal_sekolah').val(data.asal_sekolah);
                $('#prodi_tujuan').val(data.prodi_tujuan);
                $('#email').val(data.email);
                $('#no_hp').val(data.no_hp);
                setFileMode(data);
                pendaftaranModal.show();
            }).fail(function () {
                showAlert('Gagal memuat data untuk diedit.', 'danger');
            });
        });

        // Hapus data
        $(document).on('click', '.delete', function () {
            const id = $(this).data('id');
            const nama = $(this).data('nama');

            confirmDelete(`Data pendaftar "${nama}" akan dihapus dan tidak bisa dikembalikan.`).then(function (confirmed) {
                if (! confirmed) {
                    return;
                }

                $.ajax({
                    type: 'DELETE',
                    url: urls.destroy.replace(':id', id),
                    dataType: 'json',
                    success: function (response) {
                        loadData();
                        showAlert(response.success);
                    },
                    error: function () {
                        showAlert('Gagal menghapus data. Silakan coba lagi.', 'danger');
                    }
                });
            });
        });
    });
</script>
@endpush
