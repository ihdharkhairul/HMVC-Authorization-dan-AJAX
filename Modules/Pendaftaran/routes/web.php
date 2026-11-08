<?php

use Illuminate\Support\Facades\Route;
use Modules\Pendaftaran\Http\Controllers\PendaftaranController;

Route::middleware(['auth', 'role:admin,user'])->group(function () {
    Route::get('pendaftaran', [PendaftaranController::class, 'index'])->name('pendaftaran.index');
    Route::get('pendaftaran/data', [PendaftaranController::class, 'getData'])->name('pendaftaran.data');
    Route::post('pendaftaran', [PendaftaranController::class, 'store'])->name('pendaftaran.store');
    Route::get('pendaftaran/{daftar}/edit', [PendaftaranController::class, 'edit'])->name('pendaftaran.edit');
    Route::put('pendaftaran/{daftar}', [PendaftaranController::class, 'update'])->name('pendaftaran.update');
    Route::delete('pendaftaran/{daftar}', [PendaftaranController::class, 'destroy'])->name('pendaftaran.destroy');
});
