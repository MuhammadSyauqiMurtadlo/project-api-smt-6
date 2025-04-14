<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Belajar;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::get('test_api', [Belajar::class, 'coba']);
Route::post('cek_hari', [Belajar::class, 'cek_tgl']);
Route::get('set_rahasia/{jenis}/{teks}', [Belajar::class, 'enkripsi_deskripsi']);
