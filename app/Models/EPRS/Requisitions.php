<?php

namespace App\Models\EPRS;

use App\Models\Users\EPRSUsers;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;

class Requisitions extends Model
{
    protected $connection = "connection_first";
    protected $table = "tps2024.Requisitions";

    protected $connFirst;

    public function __construct()
    {
        $this->connFirst = DB::connection('connection_first');
    }

    public function requisitionDetail()
    {
        return $this->hasMany(RequisitionDetail::class, 'RequisitionNo', 'RequisitionNo');
    }

    public function EPRSUsers()
    {
        return $this->belongsTo(EPRSUsers::class, 'userid', 'cbisms_user_id');
    }

    public function getAllData($perPage = 50, $type = "all", $step = "all", $status = "all", $keyword = null, $dateStart = null, $dateEnd = null)
    {
        try {
            $results = $this->from('tps2024.Requisitions as t1')
                ->leftJoin('tps2024.print_po as t2', 't1.RequisitionNo', 't2.pp_no')
                ->leftJoin('accapptps2023.ap_invd as t3', 't1.RequisitionNo', 't3.dscription')
                ->leftJoin('accapptps2023.ap_invh as t4', function ($join) {
                    $join->on('t3.batchno', 't4.batchno')
                        ->on('t3.docentry', 't4.docentry');
                })
                ->leftJoin('accapptps2023.ap_invobl as t5', 't4.docnum', 't5.docnum')
                ->leftJoin('accapptps2023.cb_batchsd as t6', 't5.docnum', 't6.docno')
                ->leftJoin('accapptps2023.cb_batchh as t7', function ($join) {
                    $join->on('t6.batchno', '=', 't7.batchno')
                        ->on('t6.entryno', '=', 't7.entryno');
                });

            $results = $results->where(function ($query) use ($type, $step, $status, $keyword) {
                if ($type == "pp") {
                    $query->where('t1.RequisitionCatery', 1);
                } elseif ($type == "ppnpo") {
                    $query->where('t1.RequisitionCatery', 4)->where($this->connFirst->raw("SUBSTRING(RequisitionNo, 1, 2)"), 'PP');;
                } elseif ($type == "um") {
                    $query->where('t1.RequisitionCatery', 4)->where($this->connFirst->raw("SUBSTRING(RequisitionNo, 1, 2)"), 'UM');
                }

                if ($step == "pp") {
                    $query->whereNull('t2.po_no')->whereNull('t5.docnum')->whereNull('t7.batchno');
                } elseif ($step == "po") {
                    $query->whereNotNull('t2.po_no')->whereNull('t5.docnum')->whereNull('t7.batchno');
                } elseif ($step == "inv") {
                    $query->whereNotNull('t5.docnum')->whereNull('t7.batchno');
                } elseif ($step == "pv") {
                    $query->whereNotNull('t7.batchno');
                }

                if ($status == "proccess") {
                    $query->where('t5.swpaid', 0);
                } elseif ($status == "finish") {
                    $query->where('t5.swpaid', 1);
                }

                if ($keyword) {
                    $query->where('t1.RequisitionNo', 'like', '%' . $keyword . '%');
                }
            });

            $results = $results->groupBy('t1.RequisitionNo')
                ->orderByDesc('t1.RequisitionDate')
                ->selectRaw('
                        t1.RequisitionNo,
                        MAX(t1.RequisitionDate) AS RequisitionDate,
                        MAX(t1.TotalCatery) AS TotalCatery,
                        MAX(t1.RequisitionCatery) as RequisitionCatery,
                        MAX(t2.po_no) as po_no,
                        MAX(t2.tgl_buat) AS tgl_buat,
                        SUM(DISTINCT t2.qty * t2.harga) AS total_po,
                        MIN(t5.swpaid) AS swpaid,
                        SUM(DISTINCT t5.paytotalamt) AS paytotalamt,
                        SUM(DISTINCT t5.doctotalamt) AS doctotalamt,
                        SUM(DISTINCT ABS(t7.totamount)) AS totamount
                    ');

            $results = $results->when($dateStart && $dateEnd, function ($query) use ($dateStart, $dateEnd) {
                return $query->whereBetween('RequisitionDate', [$dateStart, $dateEnd]);
            });

            return $results->paginate($perPage);
        } catch (\Throwable $th) {
            return false;
        }
    }

    public function getDetailData($code)
    {
        try {
            return $this->with('requisitionDetail', 'EPRSUsers')
                ->where('RequisitionNo', $code)
                ->get();
        } catch (\Throwable $th) {
            return false;
        }
    }

    public function countData($month)
    {
        try {

            $results = $this->whereMonth('RequisitionDate', $month)
                ->whereYear('RequisitionDate', Date::now()->year)
                ->selectRaw('
                    COUNT(CASE WHEN LEFT(RequisitionNo, 2) = "UM" THEN 1 END) as downPayments,
                    COUNT(CASE WHEN LEFT(RequisitionNo, 2) = "PP" AND RequisitionCatery = 4 THEN 1 END) as reqNonPO,
                    COUNT(CASE WHEN RequisitionCatery = 1 THEN 1 END) as reqWithPO,
                    SUM(CASE WHEN LEFT(RequisitionNo, 2) = "UM" THEN TotalCatery ELSE 0 END) as downPaymentsSum,
                    SUM(CASE WHEN LEFT(RequisitionNo, 2) = "PP" AND RequisitionCatery = 4 THEN TotalCatery ELSE 0 END) as reqNonPOSum,
                    SUM(CASE WHEN RequisitionCatery = 1 THEN TotalCatery ELSE 0 END) as reqWithPOSum
                ')
                ->first();

            $data = [
                "downPayments" => $results->downPayments,
                "reqNonPO" => $results->reqNonPO,
                "reqWithPO" => $results->reqWithPO,
                "downPaymentsAmount" => $results->downPaymentsSum,
                "reqNonPOAmount" => $results->reqNonPOSum,
                "reqWithPOAmount" => $results->reqWithPOSum
            ];

            return $data;
        } catch (\Throwable $th) {
            return false;
        }
    }

    public function exportAllData($type = "all", $step = "all", $status = "all", $dateStart = null, $dateEnd = null)
    {
        try {
            $results = $this->from('tps2024.Requisitions as t1')
                ->leftJoin('tps2024.print_po as t2', 't1.RequisitionNo', 't2.pp_no')
                ->leftJoin('accapptps2023.ap_invd as t3', 't1.RequisitionNo', 't3.dscription')
                ->leftJoin('accapptps2023.ap_invh as t4', function ($join) {
                    $join->on('t3.batchno', 't4.batchno')
                        ->on('t3.docentry', 't4.docentry');
                })
                ->leftJoin('accapptps2023.ap_invobl as t5', 't4.docnum', 't5.docnum')
                ->leftJoin('accapptps2023.cb_batchsd as t6', 't5.docnum', 't6.docno')
                ->leftJoin('accapptps2023.cb_batchh as t7', function ($join) {
                    $join->on('t6.batchno', '=', 't7.batchno')
                        ->on('t6.entryno', '=', 't7.entryno');
                });

            $results = $results->where(function ($query) use ($type, $step, $status) {
                if ($type == "pp") {
                    $query->where('t1.RequisitionCatery', 1);
                } elseif ($type == "ppnpo") {
                    $query->where('t1.RequisitionCatery', 4)->where($this->connFirst->raw("SUBSTRING(RequisitionNo, 1, 2)"), 'PP');;
                } elseif ($type == "um") {
                    $query->where('t1.RequisitionCatery', 4)->where($this->connFirst->raw("SUBSTRING(RequisitionNo, 1, 2)"), 'UM');
                }

                if ($step == "pp") {
                    $query->whereNull('t2.po_no')->whereNull('t5.docnum')->whereNull('t7.batchno');
                } elseif ($step == "po") {
                    $query->whereNotNull('t2.po_no')->whereNull('t5.docnum')->whereNull('t7.batchno');
                } elseif ($step == "inv") {
                    $query->whereNotNull('t5.docnum')->whereNull('t7.batchno');
                } elseif ($step == "pv") {
                    $query->whereNotNull('t7.batchno');
                }

                if ($status == "proccess") {
                    $query->where('t5.swpaid', 0);
                } elseif ($status == "finish") {
                    $query->where('t5.swpaid', 1);
                }
            });

            $results = $results->groupBy('t1.RequisitionNo')
                ->orderByDesc('t1.RequisitionDate')
                ->selectRaw('
                        t1.RequisitionNo as Nomer_PP,
                        DATE_FORMAT(MAX(t1.RequisitionDate), "%d-%m-%Y") as Tanggal_PP,
                        MAX(t1.TotalCatery) as Nilai_PP,
                        MAX(t2.po_no) as Nomer_PO,
                        DATE_FORMAT(MAX(t2.tgl_buat), "%d-%m-%Y") as Tanggal_PO,
                        SUM(DISTINCT t2.qty * t2.harga) as Nilai_PO,
                        MAX(t5.docnum) as Nomer_Invoice,
                        DATE_FORMAT(MAX(t5.docdate), "%d-%m-%Y") as Tanggal_Invoice,
                        DATE_FORMAT(MAX(t5.docduedate), "%d-%m-%Y") as Due_Invoice,
                        MAX(t5.doctotalamt) as Nilai_Invoice,
                        MAX(t7.batchdate) as Batch_Date,
                        MAX(t7.reference) as Nomer_Payment,
                        MAX(ABS(t7.totamount)) as Nilai_Payment
                    ');

            $results = $results->whereBetween('t1.RequisitionDate', [$dateStart, $dateEnd]);

            return $results->get();
        } catch (\Throwable $th) {
            return false;
        }
    }

    public function exportDetail($code)
    {
        try {
            $results = $this->from('tps2024.Requisitions as t1')
                ->leftJoin('tps2024.print_po as t2', 't1.RequisitionNo', 't2.pp_no')
                ->leftJoin('accapptps2023.ap_invd as t3', 't1.RequisitionNo', 't3.dscription')
                ->leftJoin('accapptps2023.ap_invh as t4', function ($join) {
                    $join->on('t3.batchno', 't4.batchno')
                        ->on('t3.docentry', 't4.docentry');
                })
                ->leftJoin('accapptps2023.ap_invobl as t5', 't4.docnum', 't5.docnum')
                ->leftJoin('accapptps2023.cb_batchsd as t6', 't5.docnum', 't6.docno')
                ->leftJoin('accapptps2023.cb_batchh as t7', function ($join) {
                    $join->on('t6.batchno', '=', 't7.batchno')
                        ->on('t6.entryno', '=', 't7.entryno');
                })
                ->where('t1.RequisitionNo', $code);

            $results = $results->groupBy('t1.RequisitionNo')
                ->orderByDesc('t1.RequisitionDate')
                ->selectRaw('
                    t1.RequisitionNo as Nomer_PP,
                    DATE_FORMAT(MAX(t1.RequisitionDate), "%d-%m-%Y") as Tanggal_PP,
                    MAX(t1.TotalCatery) as Nilai_PP,
                    MAX(t2.po_no) as Nomer_PO,
                    DATE_FORMAT(MAX(t2.tgl_buat), "%d-%m-%Y") as Tanggal_PO,
                    SUM(DISTINCT t2.qty * t2.harga) as Nilai_PO,
                    MAX(t5.docnum) as Nomer_Invoice,
                    DATE_FORMAT(MAX(t5.docdate), "%d-%m-%Y") as Tanggal_Invoice,
                    DATE_FORMAT(MAX(t5.docduedate), "%d-%m-%Y") as Due_Invoice,
                    MAX(t5.doctotalamt) as Nilai_Invoice,
                    MAX(t7.batchdate) as Batch_Date,
                    MAX(t7.reference) as Nomer_Payment,
                    MAX(ABS(t7.totamount)) as Nilai_Payment
                ');

            return $results->get();
        } catch (\Throwable $th) {
            return false;
        }
    }
}
