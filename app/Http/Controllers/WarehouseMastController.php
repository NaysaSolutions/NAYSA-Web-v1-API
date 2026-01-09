<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WarehouseMastController extends Controller
{
    public function upsert(Request $request)
    {
        try {
            $request->validate([
                'json_data' => 'required|array',
            ]);

            $params = json_encode($request->get('json_data'));

            DB::statement('EXEC sproc_PHP_WareMast @params = ?, @mode = ?', [
                $params,
                'Upsert'
            ]);

            return response()->json([
                'success' => true,
                'data'    => ['status' => 'success'],
                'message' => 'Warehouse saved successfully.',
            ], 200);

        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'data'    => ['status' => 'error', 'details' => $e->getMessage()],
                'message' => 'Error saving warehouse.',
            ], 500);
        }
    }

    public function load(Request $request)
    {
        try {
            $rows = DB::select('EXEC sproc_PHP_WareMast @params = ?, @mode = ?', [
                json_encode(['json_data' => (object)[]]),
                'Load'
            ]);

            return response()->json([
                'success' => true,
                'data'    => $rows,
                'message' => 'Warehouse loaded successfully.',
            ], 200);

        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'data'    => ['status' => 'error', 'details' => $e->getMessage()],
                'message' => 'Error loading warehouse.',
            ], 500);
        }
    }

    public function get(Request $request)
    {
        try {
            $whCode = $request->query('whCode', '');

            $rows = DB::select('EXEC sproc_PHP_WareMast @params = ?, @mode = ?', [
                $whCode,   // sproc wraps this for Get
                'Get'
            ]);

            return response()->json([
                'success' => true,
                'data'    => $rows,
                'message' => 'Warehouse fetched successfully.',
            ], 200);

        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'data'    => ['status' => 'error', 'details' => $e->getMessage()],
                'message' => 'Error fetching warehouse.',
            ], 500);
        }
    }

    public function lookup(Request $request)
    {
        try {
            // same pattern as CustMast/VendMast: filter string
            $filter = $request->query('filter', 'ActiveAll');

            $rows = DB::select('EXEC sproc_PHP_WareMast @params = ?, @mode = ?', [
                $filter,   // sproc wraps this for Lookup
                'Lookup'
            ]);

            return response()->json([
                'success' => true,
                'data'    => $rows,
                'message' => 'Warehouse lookup loaded successfully.',
            ], 200);

        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'data'    => ['status' => 'error', 'details' => $e->getMessage()],
                'message' => 'Error loading warehouse lookup.',
            ], 500);
        }
    }
}
