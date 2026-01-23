<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;


class PayTermController extends Controller
{
    
public function index(Request $request) {

    try {
        $results = DB::select(
            'EXEC sproc_PHP_PayTermRef @mode = ?',
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
            'EXEC sproc_PHP_PayTermRef @mode = ?, @params = ?',
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
        'PAYTERM_CODE' => 'nullable|string',
        'paytermCode'  => 'nullable|string',
    ]);

    $params = $request->input('PAYTERM_CODE') ?? $request->input('paytermCode');

    if (!$params) {
        return response()->json([
            'success' => false,
            'message' => 'Pay Term Code is required.',
        ], 422);
    }

    try {
        $results = DB::select(
            'EXEC sproc_PHP_PayTermRef @mode = ?, @params = ?',
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




public function upsert(Request $request)
{
    try {
        $request->validate([
            'json_data' => 'required|json',
        ]);

        $params = $request->get('json_data');

        if (!is_string($params)) {
            $params = json_encode($params);
        }

        // use select so we can read errorCount/errorMsg from sproc
        $rows = DB::select(
            'EXEC sproc_PHP_PayTermRef @params = :json_data, @mode = :mode',
            [
                'json_data' => $params,
                'mode'      => 'Upsert',
            ]
        );

        if (!empty($rows)) {
            $first = (array) $rows[0];
            $errorCount = (int) ($first['errorCount'] ?? 0);
            $errorMsg   = $first['errorMsg'] ?? '';

            if ($errorCount > 0 && trim($errorMsg) !== '') {
                return response()->json([
                    'status'  => 'error',
                    'message' => $errorMsg,
                ], 422);
            }
        }

        return response()->json([
            'status'  => 'success',
            'message' => 'Pay term saved successfully.',
        ], 200);

    } catch (ValidationException $e) {
        return response()->json([
            'status'  => 'error',
            'message' => $e->validator->errors()->first(),
        ], 422);

    } catch (\Exception $e) {
        Log::error('Payterm upsert failed:', ['error' => $e->getMessage()]);

        return response()->json([
            'status'  => 'error',
            'message' => 'Failed to save pay term: ' . $e->getMessage(),
        ], 500);
    }
}


public function delete(Request $request)
{
    try {
        $request->validate([
            'json_data' => 'required|json',
        ]);

        $raw = $request->get('json_data');

        if (!is_string($raw)) {
            $raw = json_encode($raw);
        }

        // normalize delete payload to match sproc expected structure:
        // sproc reads $.json_data.paytermCode
        $decoded = json_decode($raw, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Invalid JSON data provided.',
            ], 400);
        }

        // if payload is { "PAYTERM_CODE": "PT001" } or { "paytermCode": "PT001" }
        if (isset($decoded['PAYTERM_CODE']) || isset($decoded['paytermCode'])) {
            $code = $decoded['PAYTERM_CODE'] ?? $decoded['paytermCode'];
            $raw = json_encode([
                'json_data' => [
                    'paytermCode' => $code
                ]
            ]);
        }

        // if payload is already { "json_data": { ... } } keep as-is
        $rows = DB::select(
            'EXEC sproc_PHP_PayTermRef @params = :json_data, @mode = :mode',
            [
                'json_data' => $raw,
                'mode'      => 'Delete',
            ]
        );

        if (!empty($rows)) {
            $first = (array) $rows[0];
            $errorCount = (int) ($first['errorCount'] ?? 0);
            $errorMsg   = $first['errorMsg'] ?? '';

            if ($errorCount > 0 && trim($errorMsg) !== '') {
                return response()->json([
                    'status'  => 'error',
                    'message' => $errorMsg,
                ], 422);
            }
        }

        return response()->json([
            'status'  => 'success',
            'message' => 'Pay term successfully deleted.',
        ], 200);

    } catch (ValidationException $e) {
        return response()->json([
            'status'  => 'error',
            'message' => $e->validator->errors()->first(),
        ], 422);

    } catch (\Exception $e) {
        Log::error('Payterm delete failed:', ['error' => $e->getMessage()]);

        return response()->json([
            'status'  => 'error',
            'message' => 'Failed to delete pay term: ' . $e->getMessage(),
        ], 500);
    }
}


}
