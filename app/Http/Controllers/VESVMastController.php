<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class VESVMastController extends Controller
{
    public function index(Request $request)
    {
        try {
            $results = DB::select(
                'EXEC sproc_PHP_VESVMast @mode = ?',
                ['Load']
            );

            return response()->json([
                'success' => true,
                'data' => $results,
            ], 200);
        } catch (\Exception $e) {
            Log::error('VESVMast Load failed: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function get(Request $request)
    {
        $request->validate([
            'PLATE_NO' => 'required|string',
        ]);

        try {
            $results = DB::select(
                'EXEC sproc_PHP_VESVMast @mode = ?, @params = ?',
                [
                    'Get',
                    strtoupper(trim((string) $request->PLATE_NO)),
                ]
            );

            return response()->json([
                'success' => true,
                'data' => $results,
            ], 200);
        } catch (\Exception $e) {
            Log::error('VESVMast Get failed: '.$e->getMessage());

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
                'EXEC sproc_PHP_VESVMast @mode = ?, @params = ?',
                [
                    'Upsert',
                    $params,
                ]
            );

            $row = $rows[0] ?? null;
            $errorcount = (int) ($row->errorcount ?? 0);
            $errormsg = (string) ($row->errormsg ?? '');

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
                'message' => 'Vehicle Service Master saved successfully.',
                'data' => $rows,
            ], 200);
        } catch (\Exception $e) {
            Log::error('VESVMast Upsert failed: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to save Vehicle Service Master: '.$e->getMessage(),
            ], 500);
        }
    }

    public function checkDuplicate(Request $request)
    {
        $validated = $request->validate([
            'json_data' => 'required|array',
            'json_data.plateNo' => 'required|string',
        ]);

        $params = json_encode([
            'json_data' => $validated['json_data'],
        ], JSON_UNESCAPED_UNICODE);

        try {
            $results = DB::select(
                'EXEC sproc_PHP_VESVMast @mode = ?, @params = ?',
                [
                    'CheckDuplicate',
                    $params,
                ]
            );

            return response()->json([
                'success' => true,
                'data' => $results,
            ], 200);
        } catch (\Exception $e) {
            Log::error('VESVMast CheckDuplicate failed: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function checkInUsed(Request $request)
    {
        $validated = $request->validate([
            'json_data' => 'required|array',
            'json_data.plateNo' => 'required|string',
        ]);

        $params = json_encode([
            'json_data' => $validated['json_data'],
        ], JSON_UNESCAPED_UNICODE);

        try {
            $results = DB::select(
                'EXEC sproc_PHP_VESVMast @mode = ?, @params = ?',
                [
                    'CheckInUsed',
                    $params,
                ]
            );

            return response()->json([
                'success' => true,
                'data' => $results,
            ], 200);
        } catch (\Exception $e) {
            Log::error('VESVMast CheckInUsed failed: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function delete(Request $request)
    {
        $validated = $request->validate([
            'json_data' => 'required|array',
            'json_data.plateNo' => 'required|string',
        ]);

        $params = json_encode([
            'json_data' => $validated['json_data'],
        ], JSON_UNESCAPED_UNICODE);

        try {
            $rows = DB::select(
                'EXEC sproc_PHP_VESVMast @mode = ?, @params = ?',
                [
                    'Delete',
                    $params,
                ]
            );

            $row = $rows[0] ?? null;
            $errorcount = (int) ($row->errorcount ?? 0);
            $errormsg = (string) ($row->errormsg ?? '');

            if ($errorcount > 0) {
                return response()->json([
                    'success' => false,
                    'errorcount' => $errorcount,
                    'errormsg' => $errormsg,
                    'message' => $errormsg,
                    'data' => $rows,
                ], 200);
            }

            return response()->json([
                'success' => true,
                'message' => 'Deleted successfully.',
                'data' => $rows,
            ], 200);
        } catch (\Exception $e) {
            Log::error('VESVMast Delete failed: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function lookup(Request $request)
    {
        $params = $request->input('PARAMS');

        if (! $params) {
            $params = json_encode([
                'json_data' => [
                    'search' => (string) $request->input('search', ''),
                    'searchMode' => (string) $request->input('searchMode', 'part'),
                    'pageSize' => (int) $request->input('pageSize', 100),
                ],
            ], JSON_UNESCAPED_UNICODE);
        }

        try {
            $results = DB::select(
                'EXEC sproc_PHP_VESVMast @mode = ?, @params = ?',
                [
                    'Lookup',
                    $params,
                ]
            );

            return response()->json([
                'success' => true,
                'data' => $results,
            ], 200);
        } catch (\Exception $e) {
            Log::error('VESVMast Lookup failed: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
