<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RCTypeController extends Controller
{
    /**
     * Load all RC Types
     */
    public function index(Request $request)
    {
        try {
            $results = DB::select(
                'EXEC sproc_PHP_RCTypeRef @mode = ?',
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

    /**
     * Add or Update RC Type
     */
    public function upsert(Request $request)
    {
        try {
            $request->validate([
                'json_data' => 'required|json',
            ]);

            $params = $request->input('json_data');

            // Use DB::select to capture the Sproc's output (errormsg, errorcount)
            $results = DB::select('EXEC sproc_PHP_RCTypeRef @mode = :mode, @params = :json_data', [
                'mode' => 'Upsert',
                'json_data' => $params,
            ]);

            return response()->json([
                'status' => 'success',
                'data' => $results,
            ], 200);
        } catch (\Exception $e) {
            Log::error('Upsert failed:', ['error' => $e->getMessage()]);
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to save: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete RC Type (with usage validation)
     */
    public function delete(Request $request)
    {
        try {
            $request->validate([
                'json_data' => 'required|json',
            ]);

            $params = $request->input('json_data');

            // IMPORTANT: Use DB::select instead of DB::statement 
            // so we can catch the 'errormsg' returned by the Sproc
            $results = DB::select('EXEC sproc_PHP_RCTypeRef @mode = ?, @params = ?', [
                'Delete',
                $params
            ]);

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

    /**
     * Get a single RC Type by Code
     */
    public function get(Request $request)
    {
        try {
            $code = $request->query('rcTypeCode');

            $results = DB::select(
                'EXEC sproc_PHP_RCTypeRef @mode = ?, @params = ?',
                ['Get', $code]
            );

            return response()->json([
                'success' => true,
                'data' => $results,
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Check if a Code already exists
     */
    public function checkDuplicate(Request $request)
    {
        try {
            // FIX: Pass the raw json_data string directly. 
            // Do NOT use json_encode here.
            $params = $request->input('json_data');

            $results = DB::select(
                'EXEC sproc_PHP_RCTypeRef @mode = ?, @params = ?',
                ['CheckDuplicate', $params]
            );

            return response()->json([
                'success' => true,
                'data' => $results,
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Check if the record is used in other tables (RC Master)
     */
    public function checkInUsed(Request $request)
    {
        try {
            // FIX: Pass the raw json_data string directly.
            $params = $request->input('json_data');

            $results = DB::select(
                'EXEC sproc_PHP_RCTypeRef @mode = ?, @params = ?',
                ['CheckInUsed', $params]
            );

            return response()->json([
                'success' => true,
                'data' => $results,
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Lookup for Dropdowns
     */
    public function lookup(Request $request)
    {
        try {
            $params = $request->input('PARAMS', '');

            $results = DB::select(
                'EXEC sproc_PHP_RCTypeRef @mode = ?, @params = ?',
                ['Lookup', $params]
            );

            return response()->json([
                'success' => true,
                'data' => $results,
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}