<?php

namespace App\Http\Controllers\EPRS;

use App\Http\Controllers\Controller;
use App\Models\AccApp\Invoices;
use App\Models\AccApp\PaymentVouchers;
use App\Models\EPRS\PurchaseOrders;
use App\Models\EPRS\Requisitions;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class RequisitionsController extends Controller
{
    protected $requisitionsModel, $purchaseOrdersModel, $invoicesModel, $paymentVouchersModel;

    public function __construct()
    {
        $this->requisitionsModel = new Requisitions();
        $this->purchaseOrdersModel = new PurchaseOrders();
        $this->invoicesModel = new Invoices();
        $this->paymentVouchersModel = new PaymentVouchers();
    }

    public function getData(Request $request): JsonResponse
    {
        try {
            $step = $request->input("step", "all");
            $perPage = $request->input("perPage", 50);
            $type = $request->input("type", "all");
            $status = $request->input("status", "all");
            $keyword = $request->input('keyword');
            $dateStart = $request->input('dateStart');
            $dateEnd = $request->input('dateEnd');

            $data = $this->requisitionsModel->getAllData($perPage, $type, $step, $status, $keyword, $dateStart, $dateEnd);

            if ($data) {
                return response()->json([
                    "code" => 200,
                    "status" => true,
                    "data" => $data
                ], 200);
            } else {
                return response()->json([
                    "code" => 404,
                    "status" => false,
                    "message" => "Not found.",
                ], 404);
            }
        } catch (\Throwable $th) {
            return response()->json([
                "code" => 500,
                "status" => false,
                "message" => $th->getMessage(),
            ], 500);
        }
    }

    public function getDetailData(Request $request): JsonResponse
    {
        try {
            $step = $request->input("step");
            $code = $request->input("code");

            if ($step == "pp") {
                $data = $this->requisitionsModel->getDetailData($code);
            } elseif ($step == "po") {
                $data = $this->purchaseOrdersModel->getDetailData($code);
            } elseif ($step == "inv") {
                $data = $this->invoicesModel->getDetailData($code);
            } elseif ($step == "pv") {
                $data = $this->paymentVouchersModel->getDetailData($code);
            }

            if ($data) {
                return response()->json([
                    "code" => 200,
                    "status" => true,
                    "data" => $data
                ], 200);
            } else {
                return response()->json([
                    "code" => 404,
                    "status" => false,
                    "message" => "Not found.",
                ], 404);
            }
        } catch (\Throwable $th) {
            return response()->json([
                "code" => 500,
                "status" => false,
                "message" => $th->getMessage(),
            ], 500);
        }
    }

    public function exportDetail(Request $request): JsonResponse
    {
        try {
            $code = $request->input("code");

            $data = $this->requisitionsModel->exportDetail($code);

            if ($data) {
                return response()->json([
                    "code" => 200,
                    "status" => true,
                    "data" => $data
                ], 200);
            } else {
                return response()->json([
                    "code" => 404,
                    "status" => false,
                    "message" => "Not found.",
                ], 404);
            }
        } catch (\Throwable $th) {
            return response()->json([
                "code" => 500,
                "status" => false,
                "message" => $th->getMessage(),
            ], 500);
        }
    }

    public function searchData(Request $request): JsonResponse
    {
        try {
            $keyword = $request->input('keyword');
            $perPage = $request->input('perPage');

            $data = $this->requisitionsModel->getAllData($perPage, null, null, null, $keyword);

            if ($data) {
                return response()->json([
                    "code" => 200,
                    "status" => true,
                    "data" => $data
                ], 200);
            } else {
                return response()->json([
                    "code" => 404,
                    "status" => false,
                    "message" => "Not found.",
                ], 404);
            }
        } catch (\Throwable $th) {
            return response()->json([
                'code' => 500,
                'status' => false,
                'message' => $th->getMessage()
            ], 500);
        }
    }

    public function countData(Request $request): JsonResponse
    {
        try {
            $month = $request->input('month');

            if ($month == '00') {
                $month = date("n");
            }

            $requisitions = $this->requisitionsModel->countData($month);
            $purchaseOrders = $this->purchaseOrdersModel->countData($month);
            $invoices = $this->invoicesModel->countData($month);
            $paymentVouchers = $this->paymentVouchersModel->countData($month);
            $aging = $this->invoicesModel->getAging($month);


            $data = [
                "requisitions" => $requisitions,
                "purchaseOrders" => $purchaseOrders,
                "invoices" => $invoices,
                "paymentVouchers" => $paymentVouchers,
                "aging" => $aging,
            ];

            return response()->json([
                "code" => 200,
                "status" => true,
                "data" => $data
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'code' => 500,
                'status' => false,
                'message' => $th->getMessage()
            ], 500);
        }
    }

    public function exportData(Request $request): JsonResponse
    {
        try {
            $step = $request->input("step", "all");
            $type = $request->input("type", "all");
            $status = $request->input("status", "all");
            $dateStart = $request->input('dateStart');
            $dateEnd = $request->input('dateEnd');

            $data = $this->requisitionsModel->exportAllData($type, $step, $status, $dateStart, $dateEnd);

            if ($data) {
                return response()->json([
                    "code" => 200,
                    "status" => true,
                    "data" => $data
                ], 200);
            } else {
                return response()->json([
                    "code" => 404,
                    "status" => false,
                    "message" => "Not found.",
                ], 404);
            }
        } catch (\Throwable $th) {
            return response()->json([
                "code" => 500,
                "status" => false,
                "message" => $th->getMessage(),
            ], 500);
        }
    }
}
