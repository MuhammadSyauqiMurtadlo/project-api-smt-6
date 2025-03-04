<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class Belajar extends Controller
{
    public function coba()
    {
        // return `(
        // "Kode" : "01",
        // "Test" : "API berjalan dengan baik"
        // )`;
        return response()->json([
            'Kode' => '01',
            'Test' => 'API berjalan dengan baik'
        ]);
    }
}
