<?php

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Modules\Pendaftaran\Models\Daftar;

function dataDaftar(array $ubah = []): array
{
    return array_merge([
        'nama' => 'Budi Santoso',
        'asal_sekolah' => 'SMA Negeri 1 Bandung',
        'prodi_tujuan' => 'Informatika',
        'email' => 'budi@example.com',
        'no_hp' => '081234567890',
        'berkas' => UploadedFile::fake()->create('ijazah.pdf', 100, 'application/pdf'),
    ], $ubah);
}

// ---------- Authentication ----------

test('tamu diarahkan ke halaman login', function () {
    $this->get('/dashboard')->assertRedirect('/login');
    $this->get('/pendaftaran')->assertRedirect('/login');
    $this->get('/admin/users')->assertRedirect('/login');
});

test('register membuat akun dengan role user', function () {
    $this->post('/register', [
        'name' => 'Siti',
        'email' => 'siti@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ])->assertRedirect('/dashboard');

    $this->assertDatabaseHas('users', ['email' => 'siti@example.com', 'role' => 'user']);
});

test('login gagal dengan password salah dan berhasil dengan password benar', function () {
    $user = User::factory()->create(['email' => 'a@example.com']);

    $this->post('/login', ['email' => 'a@example.com', 'password' => 'salah'])
        ->assertSessionHasErrors('email');

    $this->post('/login', ['email' => 'a@example.com', 'password' => 'password'])
        ->assertRedirect('/dashboard');

    $this->assertAuthenticatedAs($user);
});

test('login dibatasi setelah 5 kali gagal', function () {
    User::factory()->create(['email' => 'a@example.com']);

    foreach (range(1, 5) as $i) {
        $this->post('/login', ['email' => 'a@example.com', 'password' => 'salah']);
    }

    $this->post('/login', ['email' => 'a@example.com', 'password' => 'password'])
        ->assertSessionHasErrors('email');

    $this->assertGuest();
});

// ---------- Authorization ----------

test('user biasa mendapat 403 di halaman admin', function () {
    $this->actingAs(User::factory()->create())
        ->get('/admin/users')
        ->assertForbidden();
});

test('admin dapat membuka halaman kelola user', function () {
    $this->actingAs(User::factory()->admin()->create())
        ->get('/admin/users')
        ->assertOk();
});

// ---------- UserManagement ----------

test('admin dapat menambah, mengubah, dan menghapus user', function () {
    $this->actingAs(User::factory()->admin()->create());

    $this->post('/admin/users', [
        'name' => 'Rina',
        'email' => 'rina@example.com',
        'role' => 'user',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ])->assertRedirect('/admin/users');

    $rina = User::where('email', 'rina@example.com')->firstOrFail();

    $this->put("/admin/users/{$rina->id}", [
        'name' => 'Rina Baru',
        'email' => 'rina@example.com',
        'role' => 'admin',
    ])->assertRedirect('/admin/users');

    expect($rina->fresh()->name)->toBe('Rina Baru');
    expect($rina->fresh()->role->value)->toBe('admin');

    $this->delete("/admin/users/{$rina->id}")->assertRedirect('/admin/users');
    $this->assertDatabaseMissing('users', ['id' => $rina->id]);
});

test('admin tidak dapat menurunkan role atau menghapus akunnya sendiri', function () {
    $admin = User::factory()->admin()->create();
    $this->actingAs($admin);

    $this->put("/admin/users/{$admin->id}", [
        'name' => $admin->name,
        'email' => $admin->email,
        'role' => 'user',
    ])->assertSessionHasErrors('role');

    $this->delete("/admin/users/{$admin->id}")->assertSessionHas('error');

    $this->assertDatabaseHas('users', ['id' => $admin->id, 'role' => 'admin']);
});

// ---------- Pendaftaran (AJAX / JSON) ----------

test('pendaftaran dapat ditambah beserta berkas', function () {
    Storage::fake('public');
    $this->actingAs(User::factory()->create());

    $this->postJson('/pendaftaran', dataDaftar())
        ->assertOk()
        ->assertJsonStructure(['success']);

    $daftar = Daftar::firstOrFail();
    expect($daftar->nama_berkas)->toBe('ijazah.pdf');
    Storage::disk('public')->assertExists($daftar->berkas);

    $this->getJson('/pendaftaran/data')->assertOk()->assertJsonCount(1);
});

test('validasi pendaftaran mengembalikan 422', function () {
    $this->actingAs(User::factory()->create());

    $this->postJson('/pendaftaran', [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['nama', 'asal_sekolah', 'prodi_tujuan', 'email', 'no_hp', 'berkas']);
});

test('berkas dengan format atau ukuran tidak sesuai ditolak', function () {
    Storage::fake('public');
    $this->actingAs(User::factory()->create());

    $this->postJson('/pendaftaran', dataDaftar(['berkas' => UploadedFile::fake()->create('a.zip', 100)]))
        ->assertJsonValidationErrors('berkas');

    $this->postJson('/pendaftaran', dataDaftar(['berkas' => UploadedFile::fake()->create('besar.pdf', 3000)]))
        ->assertJsonValidationErrors('berkas');
});

test('berkas lama diganti saat edit dan terhapus saat data dihapus', function () {
    Storage::fake('public');
    $this->actingAs(User::factory()->create());

    $this->postJson('/pendaftaran', dataDaftar())->assertOk();
    $daftar = Daftar::firstOrFail();
    $berkasLama = $daftar->berkas;

    $this->post("/pendaftaran/{$daftar->id}", dataDaftar([
        '_method' => 'PUT',
        'berkas' => UploadedFile::fake()->create('baru.png', 100, 'image/png'),
    ]), ['Accept' => 'application/json'])->assertOk();

    $daftar->refresh();
    Storage::disk('public')->assertMissing($berkasLama);
    Storage::disk('public')->assertExists($daftar->berkas);

    $this->deleteJson("/pendaftaran/{$daftar->id}")->assertOk();
    $this->assertDatabaseMissing('daftars', ['id' => $daftar->id]);
    Storage::disk('public')->assertMissing($daftar->berkas);
});

test('data pendaftaran yang tidak ada mengembalikan 404', function () {
    $this->actingAs(User::factory()->create());

    $this->getJson('/pendaftaran/999/edit')->assertNotFound();
});