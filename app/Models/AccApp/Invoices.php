<?php

namespace App\Models\AccApp;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class Invoices extends Model
{
    protected $connection = "connection_second";
    protected $table = "accapptps2023.ap_invobl";

    protected $connSecond;

    public function __construct()
    {
        $this->connSecond = DB::connection('connection_second');
    }

    public function vendors()
    {
        return $this->belongsTo(Vendors::class, 'vendcode', 'vendcode');
    }

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
                ->where('t1.RequisitionNo', $code);

            $amount = $amount->groupBy('t5.docnum')
                ->selectRaw('
                        SUM(DISTINCT t5.doctotalamt) AS doctotalamt
                    ')
                ->first();

            $results = $this->from('tps2024.Requisitions as t1')
                ->join('accapptps2023.ap_invd as t3', 't1.RequisitionNo', 't3.dscription')
                ->join('accapptps2023.ap_invh as t4', function ($join) {
                    $join->on('t3.batchno', 't4.batchno')
                        ->on('t3.docentry', 't4.docentry');
                })
                ->join('accapptps2023.ap_invobl as t5', 't4.docnum', 't5.docnum')
                ->join('accapptps2023.ap_vendor as t6', 't5.vendcode', 't6.vendcode')
                ->where('t1.RequisitionNo', $code);

            $results = $results->groupBy('t5.docnum')
                ->selectRaw('
                        t5.docnum,
                        MIN(t5.swpaid) AS swpaid,
                        MAX(t5.audituser) AS audituser,
                        MAX(t5.docduedate) AS docduedate,
                        MAX(t5.docdate) AS docdate,
                        MAX(t5.invdesc) AS invdesc,
                        MAX(t5.paytotalamt) AS paytotalamt,
                        MAX(t5.doctotalamt) AS doctotalamt,
                        MAX(t5.doctotalamtr) AS doctotalamtr,
                        MAX(t6.vendname) AS vendname
                    ')
                ->get();

            $data = [
                'amount' => $amount ? $amount->doctotalamt : 0,
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
            $results = $this->whereMonth('docdate', $month)
                ->whereYear('docdate', Date::now()->year)
                ->selectRaw('COUNT(*) as invoiceCount, SUM(doctotalamt) as invoiceSum')
                ->first();

            $data = [
                'invoiceCount' => $results->invoiceCount,
                'invoiceSum' => $results->invoiceSum,
            ];

            return $data;
        } catch (\Throwable $th) {
            return false;
        }
    }

    public function getAging($month)
    {
        try {
            $today = Date::now();
            $currentYear = (int) $today->format('Y');

            $results = $this->selectRaw('
                SUM(doctotalamtr) as totalBill,
                SUM(CASE WHEN MONTH(docduedate) = ? AND YEAR(docduedate) = ? THEN doctotalamtr ELSE 0 END) as totalBillByMonth,
                SUM(CASE WHEN MONTH(docduedate) = ? AND YEAR(docduedate) = ? THEN doctotalamtr ELSE 0 END) as totalNotPaidByMonth,
                SUM(CASE WHEN MONTH(docduedate) = ? AND YEAR(docduedate) = ? THEN paytotalamt ELSE 0 END) as totalPaidByMonth,
                SUM(CASE WHEN swpaid = 0 AND docduedate < ? THEN doctotalamtr ELSE 0 END) as totalBillOverDue
            ', [$month, $currentYear, $month, $currentYear, $month, $currentYear, $today])
                ->where('swpaid', 0)
                ->first();

            $monthlyDebts = $this->selectRaw('
                mop AS month,
                SUM(DISTINCT doctotalamt) as totalDebt,
                SUM(DISTINCT paytotalamt) as totalPaid
            ')
                ->where('yop', $currentYear)
                ->groupBy('month')
                ->orderBy('month')
                ->get();

            $vendors = Vendors::whereHas('invoices', function ($query) {
                $query->where('swpaid', 0);
            })
                ->with(['invoices' => function ($query) {
                    $query->where('swpaid', 0);
                }])
                ->withSum('invoices', 'doctotalamtr')
                ->get();

            $data = [
                "totalBill" => $results->totalBill,
                "totalBillByMonth" => $results->totalBillByMonth,
                "totalPaidByMonth" => $results->totalPaidByMonth,
                "totalNotPaidByMonth" => $results->totalNotPaidByMonth,
                "monthlyDebts" => $monthlyDebts,
                "vendors" => $vendors,
                "totalBillOverDue" => $results->totalBillOverDue,
            ];

            return $data;
        } catch (\Throwable $th) {
            return false;
        }
    }

    public function getAgingAll($cutOffDue)
    {
        try {
            $data = Vendors::with(['invoices' => function ($query) {
                $query->select('vendcode', 'docnum', 'docdate', 'docduedate', 'doctotalamtr')
                    ->where('swpaid', 0)
                    ->orderBy('docduedate', 'asc');
            }])->whereHas('invoices', function ($query) {
                $query->where('swpaid', 0);
            })->get();

            $currentTotal = 0;
            $afterTotal = 0;
            $totalData = 0;

            $groupedData = $data->map(function ($vendor) use ($cutOffDue, &$currentTotal, &$afterTotal, &$totalData) {
                $currentData = $vendor->invoices->filter(function ($invoice) use ($cutOffDue) {
                    return Carbon::parse($invoice->docduedate)->lte($cutOffDue);
                });

                $afterData = $vendor->invoices->filter(function ($invoice) use ($cutOffDue) {
                    return Carbon::parse($invoice->docduedate)->gt($cutOffDue);
                });

                $currentTotal += $currentData->sum('doctotalamtr');
                $afterTotal += $afterData->sum('doctotalamtr');
                $totalData += $vendor->invoices->sum('doctotalamtr');

                return [
                    'vendor' => [
                        'id' => $vendor->id,
                        'vendcode' => $vendor->vendcode,
                        'vendname' => $vendor->vendname,
                        'current' => $currentData->sum('doctotalamtr'),
                        'after' => $afterData->sum('doctotalamtr'),
                        'total' => $vendor->invoices->sum('doctotalamtr'),
                        'invoices' => [
                            'current' => $currentData->values()->all(),
                            'after' => $afterData->values()->all()
                        ],
                    ]
                ];
            });

            $sortedGroupedData = $groupedData->sortByDesc(function ($vendor) {
                return $vendor['vendor']['total'];
            })->values()->all();

            return [
                'vendors' => $sortedGroupedData,
                'current' => $currentTotal,
                'after' => $afterTotal,
                'total' => $totalData
            ];
        } catch (\Throwable $th) {
            return false;
        }
    }

    public function getAgingDetailVendor($cutOffDue, $code)
    {
        try {
            $vendor = Vendors::with(['invoices' => function ($query) {
                $query->select('vendcode', 'docnum', 'docdate', 'docduedate', 'doctotalamtr')
                    ->where('swpaid', 0)
                    ->orderBy('docduedate', 'asc');
            }])->where('vendcode', $code)->firstOrFail();

            $currentData = $vendor->invoices->filter(function ($invoice) use ($cutOffDue) {
                return Carbon::parse($invoice->docduedate)->lte($cutOffDue);
            });

            $afterData = $vendor->invoices->filter(function ($invoice) use ($cutOffDue) {
                return Carbon::parse($invoice->docduedate)->gt($cutOffDue);
            });

            return [
                'vendor' => [
                    'id' => $vendor->id,
                    'vendcode' => $vendor->vendcode,
                    'vendname' => $vendor->vendname,
                    'current' => $currentData->sum('doctotalamtr'),
                    'after' => $afterData->sum('doctotalamtr'),
                    'total' => $vendor->invoices->sum('doctotalamtr'),
                    'invoices' => [
                        'current' => $currentData->values()->all(),
                        'after' => $afterData->values()->all()
                    ],
                ]
            ];
        } catch (\Throwable $th) {
            return false;
        }
    }
}
