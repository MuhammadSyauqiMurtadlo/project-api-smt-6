<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class TaxiRekapController extends Controller
{
    public function rekap(Request $request)
    {
        // Validasi input
        $request->validate([
            'start_date' => 'required|date',
            'end_date'   => 'required|date|after_or_equal:start_date',
            'limit'      => 'sometimes|integer|min:1',
            'page'       => 'sometimes|integer|min:1',
        ]);

        $startDate = Carbon::parse($request->start_date);
        $endDate = Carbon::parse($request->end_date);
        $limit = $request->input('limit', 10); // default 10
        $page = $request->input('page', 1);    // default 1

        $offset = ($page - 1) * $limit;

        // Data dari semua tabel
        $tables = ['2018_taxi_trips', '2019_taxi_trips', '2020_taxi_trips'];

        $allData = collect();

        foreach ($tables as $table) {
            $data = DB::table($table)
                ->select(
                    'lpep_pickup_datetime',
                    'payment_type',
                    'tip_amount',
                    'PULocationID',
                    'DOLocationID'
                )
                ->whereBetween('lpep_pickup_datetime', [$startDate, $endDate])
                ->offset($offset)
                ->limit($limit)
                ->get();

            $allData = $allData->merge($data);
        }

        $paymentTypes = [
            1 => 'credit_card',
            2 => 'cash',
            3 => 'no_charge',
            4 => 'dispute',
            5 => 'unknown',
            6 => 'voided_trip',
        ];

        $groupedByMonth = $allData->groupBy(function ($item) {
            return Carbon::parse($item->lpep_pickup_datetime)->format('Y-m');
        });

        $result = [];

        foreach ($groupedByMonth as $month => $dataMonth) {
            $groupedByWeek = $dataMonth->groupBy(function ($item) {
                return Carbon::parse($item->lpep_pickup_datetime)->weekOfMonth;
            });

            foreach ($groupedByWeek as $week => $dataWeek) {
                $totalTransactions = $dataWeek->count();

                $paymentSummary = [
                    'cash'        => 0,
                    'debit'       => 0,
                    'credit'      => 0,
                    'e_wallet'    => 0,
                    'transfer'    => 0,
                ];

                $totalTip = 0;
                $naik = [];
                $turun = [];

                foreach ($dataWeek as $trip) {
                    $paymentType = $paymentTypes[$trip->payment_type] ?? 'unknown';

                    if (isset($paymentSummary[$paymentType])) {
                        $paymentSummary[$paymentType]++;
                    } else {
                        // Kalau payment type tidak dikenali, simpan ke 'unknown'
                        if (!isset($paymentSummary['unknown'])) {
                            $paymentSummary['unknown'] = 0;
                        }
                        $paymentSummary['unknown']++;
                    }

                    $totalTip += $trip->tip_amount;
                    $naik[] = $trip->PULocationID;
                    $turun[] = $trip->DOLocationID;
                }

                // Hapus duplikat lokasi
                $naik = array_values(array_unique($naik));
                $turun = array_values(array_unique($turun));

                $result[$month]["minggu{$week}"] = [
                    'total_transaksi' => $totalTransactions,
                    'payment'         => $paymentSummary,
                    'tip'             => $totalTip,
                    'lokasi'          => [
                        'naik'  => $naik,
                        'turun' => $turun,
                    ],
                ];
            }
        }

        return response()->json($result);
    }
}
