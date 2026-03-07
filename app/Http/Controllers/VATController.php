<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
class VATController extends Controller
{
  
public function index(Request $request) {

    try {
        $results = DB::select(
            'EXEC sproc_PHP_VATRef @mode = ?',
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
            'EXEC sproc_PHP_VATRef @mode = ?, @params = ?',
            ['Lookup' ,$params['search']] 
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
        'VAT_CODE' => 'required|string',
    ]);

    $params = $request->input('VAT_CODE');


    try {
        $results = DB::select(
            'EXEC sproc_PHP_VATRef @mode = ?, @params = ?',
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

        // (optional safety) ensure string json
        if (!is_string($params)) {
            $params = json_encode($params);
        }

        // ✅ READ resultset from sproc (SVI style)
        $rows = DB::select(
            'EXEC sproc_PHP_VATRef @params = :json_data, @mode = :mode',
            [
                'json_data' => $params,
                'mode' => 'Upsert', // keep same casing as sproc comparisons
            ]
        );

        // If sproc returns: errorMsg, errorCount
        if (!empty($rows)) {
            $first = (array) $rows[0];

            $errorCount = isset($first['errorCount']) ? (int) $first['errorCount'] : 0;
            $errorMsg   = $first['errorMsg'] ?? '';

            if ($errorCount > 0 && trim($errorMsg) !== '') {
                return response()->json([
                    'status'  => 'error',
                    'message' => $errorMsg, // ✅ shows exact SVI formatted list
                ], 422);
            }
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Transaction saved successfully.',
        ], 200);

    } catch (\Illuminate\Validation\ValidationException $e) {
        return response()->json([
            'status' => 'error',
            'message' => $e->validator->errors()->first(),
            'errors' => $e->validator->errors(),
        ], 422);

    } catch (\Exception $e) {
        Log::error('COA save failed:', ['error' => $e->getMessage()]);

        return response()->json([
            'status' => 'error',
            'message' => 'Failed to save transaction: ' . $e->getMessage(),
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


//         DB::statement('EXEC sproc_PHP_VATRef @params = :json_data, @mode = :mode', [
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

public function delete(Request $request)
{
    $data = $request->validate([
        'vatCode'  => 'required|string',
        'userCode' => 'required|string',
    ]);

    $wrapper = json_encode([
        'json_data' => [
            'vatCode'  => $data['vatCode'],
            'userCode' => $data['userCode'],
        ],
    ]);

    $result = DB::select(
        'EXEC sproc_PHP_VATRef @mode = ?, @params = ?',
        ['Delete', $wrapper]
    );

    $row = $result[0] ?? null;

    if ($row && $row->success == 1) {
        return response()->json([
            'success' => true,
            'message' => $row->message,
        ]);
    }

    return response()->json([
        'success' => false,
        'message' => $row->message ?? 'Failed to delete VAT record.',
    ], 422);
}



}
