<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BudItemRefController extends Controller
{
    public function index(Request $request)
    {
        try {
            $results = DB::select(
                'EXEC sproc_PHP_BUDItemRef @mode = ?',
                ['Load']
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
            'EXEC sproc_PHP_BUDItemRef @mode = ?, @params = ?',
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


    public function get(Request $request)
    {
        $request->validate([
            'BUDGET_CODE' => 'required|string',
        ]);

        try {
            $results = DB::select(
                'EXEC sproc_PHP_BUDItemRef @mode = ?, @params = ?',
                ['Get', $request->BUDGET_CODE]
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
                'EXEC sproc_PHP_BUDItemRef @mode = ?, @params = ?',
                ['Upsert', $params]
            );

            $r0 = $rows[0] ?? null;
            $errorcount = (int)($r0->errorcount ?? 0);
            $errormsg = (string)($r0->errormsg ?? '');

            if ($errorcount > 0) {
                return response()->json([
                    'success' => false,
                    'errorcount' => $errorcount,
                    'errormsg' => $errormsg,
                    'data' => $rows,
                ], 200);
            }

            return response()->json([
                'success' => true,
                'message' => 'Budget item saved successfully.',
                'data' => $rows,
            ], 200);

        } catch (\Exception $e) {
            Log::error('BudItemRef upsert failed: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to save budget item: ' . $e->getMessage(),
            ], 500);
        }
    }


    public function checkInUsed(Request $request)
    {
        $validated = $request->validate([
            'json_data' => 'required|array',
        ]);

        $params = json_encode(['json_data' => $validated['json_data']]);

        try {
            $results = DB::select(
                'EXEC sproc_PHP_BUDItemRef @mode = ?, @params = ?',
                ['CheckInUsed', $params]
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


    public function checkDuplicate(Request $request)
    {
        $validated = $request->validate([
            'json_data' => 'required|array',
        ]);

        $params = json_encode(['json_data' => $validated['json_data']]);

        try {
            $results = DB::select(
                'EXEC sproc_PHP_BUDItemRef @mode = ?, @params = ?',
                ['CheckDuplicate', $params]
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
        $request->validate([
            'json_data' => 'required|array',
        ]);

        $data = $request->json_data;
        $code = $data['code'] ?? null;

        if (!$code) {
            return response()->json([
                'success' => false,
                'message' => 'Budget code is required.',
            ], 400);
        }

        try {
            $params = json_encode([
                'json_data' => $data,
            ]);

            $rows = DB::select(
                'EXEC sproc_PHP_BUDItemRef @mode = ?, @params = ?',
                ['Delete', $params]
            );

            $r0 = $rows[0] ?? null;
            $errorcount = (int)($r0->errorcount ?? 0);
            $errormsg = (string)($r0->errormsg ?? '');

            if ($errorcount > 0) {
                return response()->json([
                    'success' => false,
                    'errorcount' => $errorcount,
                    'errormsg' => $errormsg,
                    'data' => $rows,
                ], 200);
            }

            return response()->json([
                'success' => true,
                'message' => 'Budget item deleted successfully.',
                'data' => $rows,
            ], 200);

        } catch (\Exception $e) {
            Log::error('BudItemRef delete failed: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
