<?php

namespace App\Http\Controllers\AccApp;

use App\Http\Controllers\Controller;
use App\Models\AccApp\Invoices;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class InvoicesController extends Controller
{
    protected $invoicesModel;

    public function __construct()
    {
        $this->invoicesModel = new Invoices();
    }

    public function getData(Request $request): JsonResponse
    {
        try {
            $cutOffDue = $request->input('cutOffDue');

            $data = $this->invoicesModel->getAgingAll($cutOffDue);

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

    public function getDetail(Request $request, $code): JsonResponse
    {
        try {
            $cutOffDue = $request->input('cutOffDue');

            $data = $this->invoicesModel->getAgingDetailVendor($cutOffDue, $code);

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
