<?php

namespace App\Models\AccApp;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Date;

class PaymentVouchers extends Model
{
    protected $connection = "connection_second";
    protected $table = "accapptps2023.cb_batchh";

    public function getDetailData($code)
    {
        try {

            $amount = $this->from('tps2024.Requisitions as t1')
                ->join('accapptps2023.ap_invd as t3', 't1.RequisitionNo', 't3.dscription')
                ->join('accapptps2023.ap_invh as t4', function ($join) {
                    $join->on('t3.batchno', 't4.batchno')
                        ->on('t3.docentry', 't4.docentry');
                })
                ->join('accapptps2023.ap_invobl as t5', 't4.docnum', 't5.docnum')
                ->join('accapptps2023.cb_batchsd as t6', 't5.docnum', 't6.docno')
                ->join('accapptps2023.cb_batchh as t7', function ($join) {
                    $join->on('t6.batchno', '=', 't7.batchno')
                        ->on('t6.entryno', '=', 't7.entryno');
                })
                ->where('t1.RequisitionNo', $code);

            $amount = $amount->groupBy('t7.dscription')
                ->orderByDesc('t1.RequisitionDate')
                ->selectRaw('
                        SUM(DISTINCT ABS(t7.totamount)) AS totamount
                    ')
                ->first();

            $results = $this->from('tps2024.Requisitions as t1')
                ->join('accapptps2023.ap_invd as t3', 't1.RequisitionNo', 't3.dscription')
                ->join('accapptps2023.ap_invh as t4', function ($join) {
                    $join->on('t3.batchno', 't4.batchno')
                        ->on('t3.docentry', 't4.docentry');
                })
                ->join('accapptps2023.ap_invobl as t5', 't4.docnum', 't5.docnum')
                ->join('accapptps2023.cb_batchsd as t6', 't5.docnum', 't6.docno')
                ->join('accapptps2023.cb_batchh as t7', function ($join) {
                    $join->on('t6.batchno', '=', 't7.batchno')
                        ->on('t6.entryno', '=', 't7.entryno');
                })
                ->where('t1.RequisitionNo', $code);

            $results = $results->groupBy('t5.docnum')
                ->selectRaw('
                        t5.docnum,
                        MAX(t7.dscription) AS dscription,
                        MAX(t7.reference) AS reference,
                        MAX(t7.batchdate) AS batchdate,
                        MAX(ABS(t7.totamount)) AS totamount,
                        MAX(t7.audituser) AS audituser
                    ')
                ->get();

            $data = [
                'amount' => $amount ? $amount->totamount : 0,
                'list' => $results
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

            $results = $this->whereMonth('batchdate', $month)
                ->whereYear('batchdate', $currentYear)
                ->selectRaw('COUNT(*) as paymentVoucherCount, ABS(SUM(totamount)) as paymentVoucherSum')
                ->first();

            $data = [
                'paymentVoucherCount' => $results->paymentVoucherCount,
                'paymentVoucherSum' => $results->paymentVoucherSum,
            ];

            return $data;
        } catch (\Throwable $th) {
            return false;
        }
    }
}
