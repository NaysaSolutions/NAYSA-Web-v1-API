<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class POInqController extends Controller
{
    public function getPOInquiry(Request $request)
{ 
    try {

        $dataArray = $request->input('json_data');
        $jsonString = json_encode([
            'json_data' => $dataArray
        ]);

        $results = DB::select(
            'EXEC sproc_PHP_PO_Inq @_params = ? ',
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


}