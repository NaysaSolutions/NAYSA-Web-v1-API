<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;


class VEInvBalanceController extends Controller
{
  


public function getInvLookup(Request $request) {


    $jsonData = $request->all();
    $jsonString = json_encode($jsonData);


    try {

       $jsonString = $request->input('PARAMS');

       $results = DB::select(
            'EXEC sproc_PHP_INVLookup_VE @params =? ',
            [$jsonString] 
        );
        return response()->json([
            'success' => true,
            'data' => $results,
        ], 200);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => $e->getMessage(),
            'trace' => $e->getTraceAsString()

        ], 500);
    }
}


public function getVEUpdateStockAllocation(Request $request)
{
    try {
        $mode = $request->input('mode', 'GetOpenStock');

        $params = $request->input('params');

        if (is_array($params)) {
            $params = json_encode($params);
        }

        $result = DB::select(
            'exec sproc_PHP_VEUpdateStockAllocation @mode = ?, @params = ?',
            [$mode, $params]
        );

        return response()->json([
            'success' => true,
            'data' => $result,
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => $e->getMessage(),
        ], 500);
    }
}

public function getVEStockCardByCS(Request $request)
{
    $request->validate([
        'json_data' => 'required|json',
    ]);

    try {
        $payload = $request->input('json_data');
        $decoded = json_decode($payload, true, 512, JSON_THROW_ON_ERROR);
        $mode = data_get($decoded, 'json_data.mode', 'FindCSForVSO');

        $result = DB::select(
            'EXEC sproc_PHP_VEStockCard_Inq @mode = ?, @params = ?',
            [$mode, $payload]
        );

        return response()->json([
            'success' => true,
            'data' => $result,
        ], 200);
    } catch (\Throwable $e) {
        Log::error('Vehicle CS lookup failed', ['message' => $e->getMessage()]);

        return response()->json([
            'success' => false,
            'message' => 'Unable to retrieve vehicle information.',
            'details' => $e->getMessage(),
        ], 500);
    }
}


}
