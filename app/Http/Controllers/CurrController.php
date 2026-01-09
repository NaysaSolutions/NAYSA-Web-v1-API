<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;


class CurrController extends Controller
{
    
public function index(Request $request) {

    try {
        $results = DB::select(
            'EXEC sproc_PHP_CurrRef @mode = ?',
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


public function upsert(Request $request)
{
    try {
        $request->validate([
            'currCode' => 'required|string',
            'currName' => 'required|string',
            'userCode' => 'required|string',
        ]);

        // Build the JSON structure exactly as the stored procedure expects
        $jsonData = [
            'json_data' => [
                'currCode' => $request->input('currCode'),
                'currName' => $request->input('currName'),
                'userCode' => $request->input('userCode')
            ]
        ];
        
        // Convert to JSON string
        $params = json_encode($jsonData);

        // Log the params for debugging
        Log::info('Currency upsert params:', ['params' => $params]);

        DB::statement('EXEC sproc_PHP_CurrRef @params = :json_data, @mode = :mode', [
            'json_data' => $params,
            'mode' => 'Upsert'
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Currency saved successfully.',
        ], 200);
    } catch (\Exception $e) {
        Log::error('Currency save failed:', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);

        return response()->json([
            'status' => 'error',
            'message' => 'Failed to save currency: ' . $e->getMessage(),
        ], 500);
    }
}


public function lookup(Request $request) {

 
    try {
        $results = DB::select(
            'EXEC sproc_PHP_CurrRef @mode = ?',
            ['Lookup' ] 
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
        'CURR_CODE' => 'required|string',
    ]);

    $params = $request->input('CURR_CODE');


    try {
        $results = DB::select(
            'EXEC sproc_PHP_CurrRef @mode = ?, @params = ?',
            ['Get' ,$params] 
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
        $request->validate([
            'json_data' => 'required|string',
        ]);

        $jsonData = json_decode($request->input('json_data'), true);
        
        if (!is_array($jsonData) || empty($jsonData)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid JSON data format',
            ], 400);
        }
        
        // Get the first item in the array
        $item = $jsonData[0];
        $currCode = $item['currCode'] ?? $item['CURR_CODE'] ?? null;
        
        if (!$currCode) {
            return response()->json([
                'success' => false,
                'message' => 'Currency code is required',
            ], 400);
        }
        
        // Format data for stored procedure
        $params = json_encode([
            'json_data' => [
                'currCode' => $currCode,
                'userCode' => $request->input('userCode', 'SYSTEM')
            ]
        ]);

        Log::info('Currency delete params:', ['params' => $params]);

        DB::statement('EXEC sproc_PHP_CurrRef @params = :json_data, @mode = :mode', [
            'json_data' => $params,
            'mode' => 'Delete'
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Currency deleted successfully.',
        ], 200);
    } catch (\Exception $e) {
        Log::error('Currency delete failed:', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);

        return response()->json([
            'success' => false,
            'message' => 'Failed to delete currency: ' . $e->getMessage(),
        ], 500);
    }
}

}
