<?php


namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;



class HSToolsController extends Controller
{
    



public function getTblGetFieldLenght(Request $request) {

     $request->validate([
            'tableName' => 'required|string'
        ]);

        // Build the JSON structure exactly as the stored procedure expects
        $jsonData = [
            'json_data' => [
                'tableName' => $request->input('tableName')]
        ];

        $params = json_encode($jsonData);

    try {
        $results = DB::select(
            'EXEC sproc_PHP_HSTools @mode = ?, @params = ?',
            ['TblGetFieldLenght' ,$params] 
        );

        return response()->json([
            'success' => true,
            'data' => $results,
        ], 200);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => $e->getMessage(),
        ], 500);
    }
}




    public function getDocTrail(Request $request) {
    // 1. Get the raw JSON and decode it
    $params = json_decode($request->input('PARAMS'), true);

    try {
        $results = DB::select(
            'EXEC sproc_PHP_DocTrail 
                @_mode = "GetDocTrail" , 
                @_startdate = ?, 
                @_enddate = ?, 
                @_usercode = ?, 
                @_doccode = ?, 
                @_docNo =?,
                @_branchcode = ?',
            [
                $params['startDate'] ?? null,
                $params['endDate'] ?? null,
                $params['userCode'] ?? null,
                $params['docCode'] ?? null,
                $params['docNo'] ?? null,
                $params['branchCode'] ?? null
            ]
        );

        return response()->json([
            'success' => true,
            'data' => $results,
        ], 200);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => $e->getMessage(),
        ], 500);
    }
    }



    
  public function getRefTrail(Request $request) {
    // 1. Get the raw JSON and decode it
    
    $params = json_decode($request->input('PARAMS'), true);

   
    try {
      
        $results = DB::select(
            'EXEC sproc_PHP_DocTrail 
                @_mode = "GetRefTrail", 
                @_startdate = ?, 
                @_enddate = ?, 
                @_usercode = ?, 
                @_tblCode = ?', 
            [
                $params['startDate'] ?? null,
                $params['endDate'] ?? null,
                $params['userCode'] ?? '',
                $params['refFile'] ?? ''
            ]
        );

        return response()->json([
            'success' => true,
            'data' => $results,
        ], 200);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => $e->getMessage(),
        ], 500);
    }
}


}
