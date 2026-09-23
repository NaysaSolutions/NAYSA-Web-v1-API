<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RMCategController extends Controller
{

    public function index(Request $request)
    {
        try {
            $results = DB::select(
                'EXEC sproc_PHP_RMCateg @mode = ?',
                ['Load']
            );

            return response()->json([
                'success' => true,
                'data'    => $results,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }


    public function lookup(Request $request)
    {
        $request->validate([
            'PARAMS' => 'required|string',
        ]);

        $params = $request->input('PARAMS');

        try {
            $results = DB::select(
                'EXEC sproc_PHP_RMCateg @mode = ?, @params = ?',
                ['Lookup', $params]
            );

            return response()->json([
                'success' => true,
                'data'    => $results,
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
            'CATEG_CODE' => 'required|string',
        ]);

        try {
            $results = DB::select(
                'EXEC sproc_PHP_RMCateg @mode = ?, @params = ?',
                ['Get', $request->CATEG_CODE]
            );

            return response()->json([
                'success' => true,
                'data'    => $results,
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
                'EXEC sproc_PHP_RMCateg @mode = ?, @params = ?',
                ['Upsert', $params]
            );

            $r0         = $rows[0] ?? null;
            $errorcount = (int)($r0->errorcount ?? 0);
            $errormsg   = (string)($r0->errormsg ?? '');

            if ($errorcount > 0) {
                return response()->json([
                    'success'    => false,
                    'errorcount' => $errorcount,
                    'errormsg'   => $errormsg,
                    'data'       => $rows,
                ], 200);
            }

            return response()->json([
                'success' => true,
                'message' => 'RM Category saved successfully.',
                'data'    => $rows,
            ], 200);

        } catch (\Exception $e) {
            Log::error('RMCateg Upsert failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to save RM Category: ' . $e->getMessage(),
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
                'EXEC sproc_PHP_RMCateg @mode = ?, @params = ?',
                ['CheckInUsed', $params]
            );

            return response()->json([
                'success' => true,
                'data'    => $results,
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
                'EXEC sproc_PHP_RMCateg @mode = ?, @params = ?',
                ['CheckDuplicate', $params]
            );

            return response()->json([
                'success' => true,
                'data'    => $results,
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
                'message' => 'RM Category code is required.',
            ], 400);
        }

        try {
            $params = json_encode([
                'json_data' => $data,
            ]);

            DB::statement(
                'EXEC sproc_PHP_RMCateg @mode = ?, @params = ?',
                ['Delete', $params]
            );

            return response()->json([
                'success' => true,
                'message' => 'RM Category deleted successfully.',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }


    public function validateBulk(Request $request)
    {
        $request->validate([
            'json_data'      => 'required|array',
            'json_data.rows' => 'required|array',
        ]);

        try {
            $params = json_encode(['json_data' => $request->input('json_data')]);

            $results = DB::select(
                'EXEC sproc_PHP_RMCateg @mode = ?, @params = ?',
                ['ValidateBulkImport', $params]
            );

            $raw = $results[0]->result ?? '[]';

            return response()->json([
                'success' => true,
                'data'    => [['result' => $raw]],
            ], 200);

        } catch (\Exception $e) {
            Log::error('RMCateg validateBulk failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}