{{-- Modal konfirmasi yang dipakai bersama oleh seluruh halaman --}}
<div class="modal fade" id="confirmModal" tabindex="-1" aria-labelledby="confirmModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content">
            <div class="modal-body text-center p-4">
                <i class="bi bi-exclamation-circle text-danger fs-1"></i>
                <h6 class="mt-2 mb-1" id="confirmModalLabel">Hapus data?</h6>
                <p class="text-muted small mb-0" id="confirmModalMessage"></p>
            </div>
            <div class="modal-footer justify-content-center border-0 pt-0 pb-4">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-danger" id="confirmModalYes">Ya, hapus</button>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    /**
     * Tampilkan modal konfirmasi. Mengembalikan Promise yang
     * bernilai true jika user menekan "Ya, hapus".
     */
    window.confirmDelete = function (message) {
        const modalElement = document.getElementById('confirmModal');
        const modal = bootstrap.Modal.getOrCreateInstance(modalElement);
        document.getElementById('confirmModalMessage').textContent = message;

        return new Promise(function (resolve) {
            let confirmed = false;
            const yesButton = document.getElementById('confirmModalYes');

            yesButton.onclick = function () {
                confirmed = true;
                modal.hide();
            };

            modalElement.addEventListener('hidden.bs.modal', function () {
                resolve(confirmed);
            }, { once: true });

            modal.show();
        });
    };

    document.addEventListener('DOMContentLoaded', function () {
        // Form dengan atribut data-confirm meminta konfirmasi sebelum dikirim
        document.querySelectorAll('form[data-confirm]').forEach(function (form) {
            form.addEventListener('submit', function (event) {
                event.preventDefault();
                confirmDelete(form.dataset.confirm).then(function (confirmed) {
                    if (confirmed) {
                        form.submit();
                    }
                });
            });
        });

        // Tombol tampilkan / sembunyikan password
        document.querySelectorAll('[data-toggle-password]').forEach(function (button) {
            button.addEventListener('click', function () {
                const input = document.getElementById(button.dataset.togglePassword);
                const isHidden = input.type === 'password';
                input.type = isHidden ? 'text' : 'password';
                button.querySelector('i').className = isHidden ? 'bi bi-eye-slash' : 'bi bi-eye';
            });
        });

        // Pesan sukses hilang otomatis setelah 4 detik
        document.querySelectorAll('.alert-autohide').forEach(function (alert) {
            setTimeout(function () {
                bootstrap.Alert.getOrCreateInstance(alert).close();
            }, 4000);
        });
    });
</script>
@stack('scripts')