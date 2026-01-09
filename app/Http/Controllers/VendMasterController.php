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
    $paramsString = $request->input('PARAMS');
    $params = json_decode($paramsString, true);
   
    try {
        $results = DB::select(
            'EXEC sproc_PHP_VendMast @mode = ?, @params = ?',
            ['Lookup' ,$params['search']] 
        );

        return response()->json([
            'success' => true,
            'data' => $results,
        ], 200);


        // return response()->json([
        //     'success' => true,
        //     'data' => json_encode($results)
        // ],200);

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
    try {
        $request->validate([
            'json_data' => 'required|json',
        ]);

        $params = $request->get('json_data');

      

        if (json_last_error() !== JSON_ERROR_NONE) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid JSON data provided.',
            ], 400);
        }


        DB::statement('EXEC sproc_PHP_VendMast @params = :json_data, @mode = :mode', [
            'json_data' => $params,
            'mode' => 'upsert'
        ]);


        return response()->json([
            'status' => 'success',
            'message' => 'Transaction saved successfully.',
        ], 200);
    } catch (\Exception $e) {
        Log::error('Transaction save failed:', ['error' => $e->getMessage()]);

        return response()->json([
            'status' => 'error',
            'message' => 'Failed to save transaction: ' . $e->getMessage(),
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

}
