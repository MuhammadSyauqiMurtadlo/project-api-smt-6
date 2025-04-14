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

    public function cek_tgl(Request $request)
    {
        $tgl = $request->input('tanggal');
        $hari = date('l', strtotime($tgl));
        $translate = [
            'Sunday' => 'Minggu',
            'Monday' => 'Senin',
            'Tuesday' => 'Selasa',
            'Wednesday' => 'Rabu',
            'Thursday' => 'Kamis',
            'Friday' => 'Jumat',
            'Saturday' => 'Sabtu'
        ];
        // $hari = $terjemah[$hari];
        // return response()->json([
        //     'Kode' => '02',
        //     'Hari' => $hari
        // ]);
        $json = '{
            "Tanggal" : "' . $tgl . '",
            "Hari" : "' . $translate[$hari] . '"
        }';
        return $json;
    }

    public function enkripsi_deskripsi($jenis, $teks)
    {
        $key = '123456';
        if ($jenis == 'enkripsi') {
            $hasil = openssl_encrypt($teks, 'aes-128-cbc', $key, 0, '1234567890123456');
            $kategori = 'Enkripsi';
        } else {
            $hasil = openssl_decrypt($teks, 'aes-128-cbc', $key, 0, '1234567890123456');
            $kategori = 'Deskripsi';
        }
        // return response()->json([
        //     'Jenis' => $jenis,
        //     'Teks' => $teks,
        //     'Hasil' => $hasil
        // ]);
        $json = '{
            "Jenis" : "' . $kategori . '",
            "Teks" : "' . $teks . '",
            "Hasil" : "' . $hasil . '"
        }';
        return $json;
    }
}
