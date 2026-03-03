<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LocationController extends Controller
{
    public function upsert(Request $request)
    {
        try {
            $request->validate([
                'json_data' => 'required|array',
            ]);

            $params = json_encode($request->get('json_data'));

            DB::statement('EXEC sproc_PHP_Location @params = ?, @mode = ?', [
                $params,
                'Upsert'
            ]);

            return response()->json([
                'success' => true,
                'data'    => ['status' => 'success'],
                'message' => 'Location saved successfully.',
            ], 200);

        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'data'    => ['status' => 'error', 'details' => $e->getMessage()],
                'message' => 'Error saving location.',
            ], 500);
        }
    }

    public function load(Request $request)
    {
        try {
            $rows = DB::select('EXEC sproc_PHP_Location @params = ?, @mode = ?', [
                json_encode(['json_data' => (object)[]]),
                'Load'
            ]);

            return response()->json([
                'success' => true,
                'data'    => $rows,
                'message' => 'Location loaded successfully.',
            ], 200);

        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'data'    => ['status' => 'error', 'details' => $e->getMessage()],
                'message' => 'Error loading location.',
            ], 500);
        }
    }

    public function get(Request $request)
    {
        try {
            $locCode = $request->query('locCode', '');

            $rows = DB::select('EXEC sproc_PHP_Location @params = ?, @mode = ?', [
                $locCode,  // sproc wraps this for Get
                'Get'
            ]);

            return response()->json([
                'success' => true,
                'data'    => $rows,
                'message' => 'Location fetched successfully.',
            ], 200);

        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'data'    => ['status' => 'error', 'details' => $e->getMessage()],
                'message' => 'Error fetching location.',
            ], 500);
        }
    }

    public function lookup(Request $request)
    {
        try {
            $filter = $request->query('filter', 'ActiveAll');

            $rows = DB::select('EXEC sproc_PHP_Location @params = ?, @mode = ?', [
                $filter,  // sproc wraps this for Lookup
                'Lookup'
            ]);

            return response()->json([
                'success' => true,
                'data'    => $rows,
                'message' => 'Location lookup loaded successfully.',
            ], 200);

        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'data'    => ['status' => 'error', 'details' => $e->getMessage()],
                'message' => 'Error loading location lookup.',
            ], 500);
        }
    }

    public function byWarehouse(Request $request)
{
    $whCode = $request->input('json_data.whCode', ''); // ✅ FIX

    $params = json_encode([
        "json_data" => [
            "whCode" => $whCode
        ]
    ]);

    $rows = DB::select('EXEC dbo.sproc_PHP_Location @mode = ?, @params = ?', [
        'ByWarehouse',
        $params
    ]);

    $json = $rows[0]->result ?? '[]';

    return response()->json([
        'success' => true,
        'data' => json_decode($json, true),
    ]);
}


}
