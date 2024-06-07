<?php

namespace App\Models\EPRS;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Date;

class PurchaseOrders extends Model
{
    protected $connection = "connection_first";
    protected $table = "tps2024.print_po";

    public function getDetailData($code)
    {
        try {
            $totalMaxObject = $this->where('pp_no', $code)
                ->selectRaw('SUM(qty * harga) AS totalMax')
                ->first();

            $totalMax = $totalMaxObject ? $totalMaxObject->totalMax : 0;

            $list =  $this->where('pp_no', $code)->get();

            $data = [
                'totalMax' => $totalMax,
                'data' => $list
            ];
            return $data;
        } catch (\Throwable $th) {
            return false;
        }
    }

    public function countData($month)
    {
        try {
            $currentYear = Date::now()->year;

            $results = $this->whereMonth('tgl_buat', $month)
                ->whereYear('tgl_buat', $currentYear)
                ->selectRaw('COUNT(*) as paymentOrderCount, SUM(qty * harga) as paymentOrderSum')
                ->first();

            $data = [
                'paymentOrderCount' => $results->paymentOrderCount,
                'paymentOrderSum' => $results->paymentOrderSum,
            ];

            return $data;
        } catch (\Throwable $th) {
            return false;
        }
    }
}
