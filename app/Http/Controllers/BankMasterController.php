<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;



class BankMasterController extends Controller
{
  
public function index(Request $request) {

    try {
        $results = DB::select(
            'EXEC sproc_PHP_BankMast @mode = ?',
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
            'EXEC sproc_PHP_BankMast @mode = ?, @params = ?',
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
        'BANK_CODE' => 'required|string',
    ]);

    $params = $request->input('BANK_CODE');


    try {
        $results = DB::select(
            'EXEC sproc_PHP_BankMast @mode = ?, @params = ?',
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

public function getDuplicateCheck(Request $request) {
    // Log the start of the request
    \Log::info('Request received for duplicate check.', $request->all());

    // Validate the request parameters, making docId optional
    $request->validate([
        'bankCode' => 'required|string',
        'checkNo' => 'required|string',
        // 'docId' => 'required|string', <--- Remove this line to make docId optional
    ]);

    $bankCode = $request->input('bankCode');
    $checkNo = $request->input('checkNo');
    $docId = $request->input('docId'); // This will be null if not provided

    // Log the parameters for debugging
    \Log::info('BankCode: ' . $bankCode . ', CheckNo: ' . $checkNo . ', DocId: ' . $docId);

    try {
        // Construct the JSON string to pass to the stored procedure
        $paramsJson = json_encode([
            'json_data' => [
                'bankCode' => $bankCode,
                'checkNo' => $checkNo,
                'docId' => $docId // Pass the docId, which can be null
            ]
        ]);

        // Log the params passed to the stored procedure
        \Log::info('Parameters for stored procedure: ' . $paramsJson);

        // Execute the stored procedure and get the result
        $results = DB::select(
            'EXEC sproc_PHP_BankMast @mode = ?, @params = ?',
            ['ValidateDuplicateCheck', $paramsJson]
        );

        // Log the results of the stored procedure
        \Log::info('Stored Procedure Results:', $results);

        // Assume no duplicate by default
        $isDuplicate = false;

        // Check if the query returned a result and if the 'result' property exists
        if (!empty($results) && isset($results[0]->result)) {
            // Set isDuplicate to true if the returned value is 1
            $isDuplicate = ($results[0]->result == 1);
        }

        // Return a consistent JSON response
        return response()->json([
            'success' => true,
            'result' => $isDuplicate ? 1 : 0,
        ], 200);

    } catch (\Exception $e) {
        // Log the error and return a JSON error response
        \Log::error('Error in getDuplicate method: ' . $e->getMessage());
        return response()->json([
            'success' => false,
            'message' => 'An internal server error occurred.',
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


        DB::statement('EXEC sproc_PHP_BankMast @params = :json_data, @mode = :mode', [
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

}
