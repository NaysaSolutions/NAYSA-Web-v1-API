<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;


class VendMasterController extends Controller
{
    
public function index(Request $request) {

    try {
        $results = DB::select(
            'EXEC sproc_PHP_VendMast @mode = ?',
            ['Load' ] 
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


   



public function lookup(Request $request) {

        $jsonString = $request->input('json_data');

    try {
        
        $results = DB::select(
            'EXEC sproc_PHP_VendMast @mode = ?, @params = ?',
            ['Lookup', $jsonString] 
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





public function get(Request $request) {

    $request->validate([
        'VEND_CODE' => 'required|string',
    ]);

    $params = $request->input('VEND_CODE');


    try {
        $results = DB::select(
            'EXEC sproc_PHP_VendMast @mode = ?, @params = ?',
            ['get' ,$params] 
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



public function upsert(Request $request)
{
     $request->validate([
        'json_data' => 'required|json',
    ]);

    try {
        $params = $request->input('json_data');
   
        
        $rows = DB::select(
            'exec dbo.sproc_PHP_VendMast @mode = ?, @params = ?',
            ['upsert', $params ]
        );

        // ✅ return raw SQL output (COAMast standard)
        return response()->json([
            'success' => true,
            'data' => $rows,
        ]);
    } catch (\Throwable $e) {
        return response()->json([
            'success' => false,
            'message' => $e->getMessage(),
        ], 500);
    }
}






public function addDetail(Request $request) {

    $jsonData = $request->all();
    $jsonString = json_encode($jsonData);

    try {
        $results = DB::select(
            'EXEC sproc_PHP_VendMast @mode = ?, @params = ?',
            ['Add_Detail' ,$jsonString] 
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

public function delete(Request $request)
{
    try {
        $vendCode = trim($request->input('VEND_CODE', ''));

        if ($vendCode === '') {
            return response()->json([
                'success' => false,
                'message' => 'Payee Code is required.',
            ], 400);
        }

        $params = [
            'json_data' => [
                'vendCode' => $vendCode,
            ],
        ];

        DB::statement(
            "EXEC sproc_PHP_VendMast @mode = ?, @params = ?",
            [
                'delete',
                json_encode($params),
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Payee deleted successfully.',
        ]);
    } catch (\Throwable $e) {
        return response()->json([
            'success' => false,
            'message' => 'Failed to delete payee.',
            'error'   => $e->getMessage(),
        ], 500);
    }
}


}
