<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class TaxiCtrl extends Controller
{
    public function taxitrips(Request $request)
    {
        // Validasi input dari request
        $request->validate([
            'startDate' => 'required|date',
            'endDate' => 'required|date',
            'Pagination' => 'integer|min:1',
            'page' => 'integer|min:1',
        ]);

        // Ambil nilai dari request
        $startDate = $request->input('startDate');
        $endDate = $request->input('endDate');
        $Pagination = $request->input('Pagination', 10);
        $page = $request->input('page', 1);
        $offset = ($page - 1) * $Pagination;
        $limit = $Pagination;

        $startDate = $request->input('startDate');
        $endDate = $request->input('endDate');
        $Pagination = $request->input('Pagination', 10);
        $page = $request->input('page', 1);
        $offset = ($page - 1) * $Pagination;
        $limit = $Pagination;

        // Menulis query raw SQL
        $sql = "
            SELECT
                MONTH(lpep_pickup_datetime) AS month_val,
                FLOOR((DAYOFMONTH(lpep_pickup_datetime) - 1) / 7) + 1 AS week_val,
                MIN(DATE(lpep_pickup_datetime)) AS week_start,
                ROUND(SUM(passenger_count), 2) AS total_transaksi,
                COUNT(CASE WHEN payment_type = 1 THEN 1 END) AS Credit_Card,
                COUNT(CASE WHEN payment_type = 2 THEN 1 END) AS Cash,
                COUNT(CASE WHEN payment_type = 3 THEN 1 END) AS No_Charge,
                COUNT(CASE WHEN payment_type = 4 THEN 1 END) AS Dispute,
                COUNT(CASE WHEN payment_type = 5 THEN 1 END) AS Unknown,
                COUNT(CASE WHEN payment_type = 6 THEN 1 END) AS Voided_Trip,
                SUM(tip_amount) AS total_tip,
                GROUP_CONCAT(DISTINCT tz_drop.zone ORDER BY lpep_pickup_datetime) AS Dropoff,
                GROUP_CONCAT(DISTINCT tz_pickup.zone ORDER BY lpep_pickup_datetime) AS pickup
            FROM (
                SELECT lpep_pickup_datetime, passenger_count, payment_type, tip_amount, PULocationID, DOLocationID
                FROM 2018_taxi_trips
                WHERE lpep_pickup_datetime BETWEEN ? AND ?
                UNION ALL
                SELECT lpep_pickup_datetime, passenger_count, payment_type, tip_amount, PULocationID, DOLocationID
                FROM 2019_taxi_trips
                WHERE lpep_pickup_datetime BETWEEN ? AND ?
                UNION ALL
                SELECT lpep_pickup_datetime, passenger_count, payment_type, tip_amount, PULocationID, DOLocationID
                FROM 2020_taxi_trips
                WHERE lpep_pickup_datetime BETWEEN ? AND ?
            ) AS combined
            LEFT JOIN taxi_zones AS tz_drop ON combined.DOLocationID = tz_drop.LocationID
            LEFT JOIN taxi_zones AS tz_pickup ON combined.PULocationID = tz_pickup.LocationID
            GROUP BY month_val, week_val
            ORDER BY week_start ASC
            LIMIT ? OFFSET ?
        ";

        // Menjalankan query dengan parameter yang sesuai
        $result = DB::select($sql, [
            $startDate,
            $endDate,
            $startDate,
            $endDate,
            $startDate,
            $endDate,
            $limit,
            $offset
        ]);
        // dd($result);

        // Struktur data yang diinginkan
        $structuredData = [];

        // Proses hasil query
        foreach ($result as $row) {
            $monthKey = 'Months ' . $row->month_val;
            $weekKey = 'Weeks ' . $row->week_val;

            // Membuat bulan jika belum ada
            if (!isset($structuredData[$monthKey])) {
                $structuredData[$monthKey] = [];
            }

            // Membentuk struktur data untuk minggu tertentu
            $structuredData[$monthKey][$weekKey] = [
                'total_transaksi' => $row->total_transaksi,
                'payment' => [
                    'cash' => $row->Cash,
                    'credit' => $row->Credit_Card,
                    'no_charge' => $row->No_Charge,
                    'dispute' => $row->Dispute,
                    'unknown' => $row->Unknown,
                    'voided_trip' => $row->Voided_Trip,
                ],
                'tip' => $row->total_tip,
                'location' => [
                    'pickup' => $row->pickup,
                    'Dropoff' => $row->Dropoff,
                    // 'pickup' => explode(',', $row->pickup),
                    // 'Dropoff' => explode(',', $row->Dropoff),
                ]
            ];
        }
        if (!isset($structuredData["Metadata"])) {
            $structuredData["Metadata"] = [
                "Pagination" => $Pagination,
                "page" => $page,
            ];
        }

        return response()->json($structuredData);
    }
}
