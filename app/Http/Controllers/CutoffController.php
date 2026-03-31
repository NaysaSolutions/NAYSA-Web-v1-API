<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;


class CutoffController extends Controller
{
    
public function index(Request $request) {

    try {
        $results = DB::select(
            'EXEC sproc_PHP_CutoffRef @mode = ?',
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

    $request->validate([
        'PARAMS' => 'required|string',
    ]);

    $params = $request->input('PARAMS');


    try {
        $results = DB::select(
            'EXEC sproc_PHP_CutoffRef @mode = ?, @params = ?',
            ['Lookup' ,$params] 
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
        'CUTOFF_CODE' => 'required|string',
    ]);

    $params = $request->input('CUTOFF_CODE');


    try {
        $results = DB::select(
            'EXEC sproc_PHP_CutoffRef @mode = ?, @params = ?',
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


        DB::statement('EXEC sproc_PHP_CutoffRef @params = :json_data, @mode = :mode', [
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

public function delete(Request $request)
{
    // Expect the same contract as upsert: a json_data string
    $request->validate([
        'json_data' => 'required|json',
    ]);

    $params = $request->get('json_data'); // this is already a JSON string

    try {
        DB::statement(
            'EXEC sproc_PHP_CutoffRef @params = :json_data, @mode = :mode',
            [
                'json_data' => $params,
                'mode'      => 'Delete', // fixed mode for delete
            ]
        );

        return response()->json([
            'success' => true,
            'status'  => 'success',
            'message' => 'Cutoff period deleted successfully.',
        ], 200);
    } catch (\Throwable $e) {
        return response()->json([
            'success' => false,
            'status'  => 'error',
            'message' => 'Failed to delete cutoff period: ' . $e->getMessage(),
        ], 500);
    }
}


}
