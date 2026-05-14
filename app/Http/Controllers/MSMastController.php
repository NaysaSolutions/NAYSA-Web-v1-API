<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MSMastController extends Controller
{
   
public function index(Request $request) {

    try {
        $results = DB::select(
            'EXEC sproc_PHP_MSMast @mode = ?',
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
    // 1. Get the raw JSON string sent by the generic lookup component
    $params = $request->input('PARAMS');

    try {
        $results = DB::select(
            'EXEC sproc_PHP_MSMast @mode = ?, @params = ?',
            ['Lookup', $params] // Pass the whole JSON string
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
        'ITEM_CODE' => 'required|string',
    ]);

    $params = $request->input('ITEM_CODE');


    try {
        $results = DB::select(
            'EXEC sproc_PHP_MSMast @mode = ?, @params = ?',
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




// public function upsert(Request $request)
// {
//     try {
//         $request->validate([
//             'json_data' => 'required|json',
//         ]);

//         $params = $request->get('json_data');

      

//         if (json_last_error() !== JSON_ERROR_NONE) {
//             return response()->json([
//                 'status' => 'error',
//                 'message' => 'Invalid JSON data provided.',
//             ], 400);
//         }


//         DB::statement('EXEC sproc_PHP_MSMast @params = :json_data, @mode = :mode', [
//             'json_data' => $params,
//             'mode' => 'upsert'
//         ]);


//         return response()->json([
//             'status' => 'success',
//             'message' => 'Transaction saved successfully.',
//         ], 200);
//     } catch (\Exception $e) {
//         Log::error('Transaction save failed:', ['error' => $e->getMessage()]);

//         return response()->json([
//             'status' => 'error',
//             'message' => 'Failed to save transaction: ' . $e->getMessage(),
//         ], 500);
//     }
// }

public function upsert(Request $request)
{
    try {
        $request->validate([
            'json_data' => 'required|json',
        ]);

        $params = $request->get('json_data');

        // Use select() instead of statement() — SQLSRV requires a result set
        $result = DB::select(
            'EXEC sproc_PHP_MSMast @params = :json_data, @mode = :mode',
            [
                'json_data' => $params,
                'mode'      => 'Upsert',   // capital U to match the sproc IF check
            ]
        );

        return response()->json([
            'status'  => 'success',
            'message' => 'Transaction saved successfully.',
            'data'    => $result,           // lets frontend read errorcount/generatedCode
        ], 200);

    } catch (\Exception $e) {
        Log::error('Transaction save failed:', ['error' => $e->getMessage()]);

        return response()->json([
            'status'  => 'error',
            'message' => 'Failed to save transaction: ' . $e->getMessage(),
        ], 500);
    }
}


}
