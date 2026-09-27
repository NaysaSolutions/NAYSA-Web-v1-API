<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PRCController extends Controller
{
    public function index(Request $request)
    {
        try {
            $request->validate([
                'json_data' => 'required|json',
            ]);

            $params = $request->get('json_data');

            $results = DB::select(
                'exec sproc_php_prc @mode = ?, @params = ?',
                ['get', $params]
            );

            return response()->json([
                'success' => true,
                'data' => $results,
            ], 200);
        } catch (\Throwable $e) {
            Log::error('PRC Index Error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error executing PRC index.',
                'details' => $e->getMessage(),
            ], 500);
        }
    }

    public function get(Request $request)
    {
        $jsonData = $request->all();
        $jsonString = json_encode($jsonData);

        try {
            $results = DB::select(
                'exec sproc_php_prc @mode = ?, @params = ?',
                ['Get', $jsonString]
            );

            return response()->json([
                'success' => true,
                'data' => $results,
            ], 200);
        } catch (\Throwable $e) {
            Log::error('PRC Get Error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error executing PRC get.',
                'details' => $e->getMessage(),
            ], 500);
        }
    }

    public function upsert(Request $request)
    {
        $validated = $request->validate([
            'json_data' => 'required|array',
        ]);

        try {
            $params = json_encode(['json_data' => $validated['json_data']]);

            $result = DB::select(
                'exec sproc_php_prc @mode = ?, @params = ?',
                ['Upsert', $params]
            );

            return response()->json([
                'status' => 'success',
                'data' => $result,
            ], 200);
        } catch (\Throwable $e) {
            Log::error('PRC Upsert Error: ' . $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Error executing PRC Upsert.',
                'details' => $e->getMessage(),
            ], 500);
        }
    }

    public function cancel(Request $request)
    {
        $validated = $request->validate([
            'json_data' => 'required|array',
        ]);

        try {
            $params = json_encode(['json_data' => $validated['json_data']]);

            $result = DB::select(
                'exec sproc_php_prc @mode = ?, @params = ?',
                ['Cancel', $params]
            );

            return response()->json([
                'status' => 'success',
                'data' => $result,
            ], 200);
        } catch (\Throwable $e) {
            Log::error('PRC Cancel Error: ' . $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Error executing PRC Cancel.',
                'details' => $e->getMessage(),
            ], 500);
        }
    }

    public function history(Request $request)
    {
        $validated = $request->validate([
            'json_data' => 'required|array',
        ]);

        try {
            $params = json_encode(['json_data' => $validated['json_data']]);

            $results = DB::select(
                'exec sproc_php_prc @mode = ?, @params = ?',
                ['History', $params]
            );

            return response()->json([
                'status' => 'success',
                'data' => $results,
            ], 200);
        } catch (\Throwable $e) {
            Log::error('PRC History Error: ' . $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Error executing PRC History.',
                'details' => $e->getMessage(),
            ], 500);
        }
    }



public function getPRCCR_OpenSummary(Request $request) {

   $jsonString = $request->input('PARAMS');

    try {
        $results = DB::select(
            'EXEC sproc_PHP_PRC @mode = ?, @params = ?',
            ['getPRCCR_OpenSummary' ,$jsonString] 
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




public function getPRCCR_OpenDetail(Request $request) {
    
    $jsonString = $request->input('json_data');

    try {
        $results = DB::select(
            'EXEC sproc_PHP_PRC @mode = ?, @params = ?',
            ['getPRCCR_OpenDetail' ,$jsonString] 
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
