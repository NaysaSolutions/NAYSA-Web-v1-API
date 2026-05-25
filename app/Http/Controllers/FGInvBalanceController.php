<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;


class FGInvBalanceController extends Controller
{
  


public function getInvLookup(Request $request) {


    $jsonData = $request->all();
    $jsonString = json_encode($jsonData);


    try {

       $jsonString = $request->input('PARAMS');

       $results = DB::select(
            'EXEC sproc_PHP_INVLookup_FG @params =? ',
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


public function getFGUpdateStockAllocation(Request $request)
{
    try {
        $mode = $request->input('mode', 'GetOpenStock');

        $params = $request->input('params');

        if (is_array($params)) {
            $params = json_encode($params);
        }

        $result = DB::select(
            'exec sproc_PHP_FGUpdateStockAllocation @mode = ?, @params = ?',
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


}
