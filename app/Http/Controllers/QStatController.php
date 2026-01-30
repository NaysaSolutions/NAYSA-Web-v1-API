<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class QStatController extends Controller
{
<<<<<<< Updated upstream
    
    
public function lookup(Request $request) {
=======
    /**
     * LOAD (list)
     * GET /qstat (or whatever route you assign)
     */
    public function index(Request $request)
    {
        try {
            $results = DB::select(
                'EXEC sproc_PHP_QStatRef @mode = ?',
                ['Load']
            );
>>>>>>> Stashed changes

    $request->validate([
        'PARAMS' => 'required',
    ]);
    $params = $request->input('PARAMS');

    try {
        $results = DB::select(
            'EXEC sproc_PHP_QStatRef @mode = ?, @params = ?',
            ['Lookup' ,$params] 
        );

<<<<<<< Updated upstream
        return response()->json([
            'success' => true,
            'data' => $results,
        ], 200);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => $e->getMessage(),
        ], 500);
=======
    /**
     * LOOKUP (modal / filtering)
     * GET /lookupQStat with params: PARAMS (JSON string)
     * Example PARAMS: {"search":"","page":1,"pageSize":50}
     *
     * IMPORTANT:
     * Your sproc Lookup mode usually expects @params = filter string,
     * so we extract 'search' and pass ONLY the string.
     */
    public function lookup(Request $request)
    {
        $request->validate([
            'PARAMS' => 'required',
        ]);

        $raw = $request->input('PARAMS');

        // PARAMS coming from React is JSON string. Extract search.
        $decoded = json_decode($raw, true);
        $search = $decoded['search'] ?? "";

        try {
            $results = DB::select(
                'EXEC sproc_PHP_QStatRef @mode = ?, @params = ?',
                ['Lookup', $search]
            );

            return response()->json([
                'success' => true,
                'data' => $results,
            ], 200);
        } catch (\Exception $e) {
            Log::error('QSTAT Lookup failed:', [
                'error' => $e->getMessage(),
                'PARAMS' => $raw
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * GET single record
     * POST/GET /getQStat (depends on your route)
     * expects QSTAT_CODE
     */
    public function get(Request $request)
    {
        $request->validate([
            'QSTAT_CODE' => 'required|string',
        ]);

        $code = $request->input('QSTAT_CODE');

        try {
            $results = DB::select(
                'EXEC sproc_PHP_QStatRef @mode = ?, @params = ?',
                ['Get', $code]
            );

            return response()->json([
                'success' => true,
                'data' => $results,
            ], 200);
        } catch (\Exception $e) {
            Log::error('QSTAT Get failed:', [
                'error' => $e->getMessage(),
                'QSTAT_CODE' => $code
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * UPSERT
     * POST /upsertQStat
     * expects json_data as array
     */
    public function upsert(Request $request)
    {
        try {
            $request->validate([
                'json_data' => 'required|array',
            ]);

            $jsonData = $request->input('json_data');

            // Keep consistent: always wrap json_data
            $params = json_encode(['json_data' => $jsonData]);

            Log::info('Upsert QStat params:', ['params' => $params]);

            DB::statement(
                'EXEC sproc_PHP_QStatRef @params = :params, @mode = :mode',
                [
                    'params' => $params,
                    'mode'   => 'Upsert',
                ]
            );

            return response()->json([
                'status'  => 'success',
                'message' => 'Transaction saved successfully.',
            ], 200);
        } catch (\Exception $e) {
            Log::error('QStat upsert failed:', ['error' => $e->getMessage()]);

            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to save transaction: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * DELETE
     * POST /deleteQStat
     * expects json_data as array
     */
    public function delete(Request $request)
    {
        try {
            $request->validate([
                'json_data' => 'required|array',
            ]);

            $jsonData = $request->input('json_data');

            $params = json_encode(['json_data' => $jsonData]);

            Log::info('Deleting QStat with params:', ['params' => $params]);

            DB::statement(
                'EXEC sproc_PHP_QStatRef @params = :params, @mode = :mode',
                [
                    'params' => $params,
                    'mode'   => 'Delete',
                ]
            );

            return response()->json([
                'status'  => 'success',
                'message' => 'Record deleted successfully.',
            ], 200);
        } catch (\Exception $e) {
            Log::error('QStat deletion failed:', ['error' => $e->getMessage()]);

            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to delete record: ' . $e->getMessage(),
            ], 500);
        }
>>>>>>> Stashed changes
    }
}


    
    

}
