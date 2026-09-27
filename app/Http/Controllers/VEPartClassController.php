<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
// pang tawag sa database operations
use Illuminate\Support\Facades\DB;
// pang log ng errors
use Illuminate\Support\Facades\Log;

class VEPartClassController extends Controller
{
    // Retrive the VEPartClass
    // Get /api/vePartClass
    // @param REQUEST
    public function index(Request $request)
{
    $rows = DB::select(
        'EXEC sproc_PHP_VEPartClass @mode = ?',
        ['Load']
    );

    $result = $rows[0]->result ?? '[]';

    return response()->json([
        'ok' => true,
        'message' => 'Vehicle Part Classes have been retrieved.',
        'data' => json_decode($result, true) ?? [],
    ]);
}


    // Upsert the VEPartClass 
    // POST /api/vepartclass/upsert 
    // @param REQUEST 
    public function upsert(Request $request)
    {
        $validated = $request->validate([
            'json_data' => 'required|array',
            'json_data.code' => 'required|string',
            'json_data.description' => 'required|string',
            'json_data.userCode' => 'required|string',
        ]);

        try {
            $params = json_encode([
                'json_data' => $validated['json_data'],
            ]);

            $rows = DB::select(
                'EXEC sproc_PHP_VEPartClass @mode = ?, @params = ?',
                ['Upsert', $params]
            );

            $row = $rows[0] ?? null;

            $errorCount = (int) ($row->errorcount ?? 0);
            $errorMessage = (string) ($row->errormsg ?? '');

            return response()->json([
                'success' => $errorCount === 0,
                'message' => $errorMessage ?: (
                    $errorCount === 0
                        ? 'Vehicle Part Class saved successfully.'
                        : 'Failed to save Vehicle Part Class.'
                ),
                'data' => $rows,
            ]);
        } catch (\Throwable $e) {
            Log::error('VEPartClass Upsert Error', [
                'message' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to save Vehicle Part Class.',
                'data' => [],
            ], 500);
        }
    }

    // Check for duplicate VEPartClass 
    // POST /api/vepartclass/check-duplicate 
    // @param REQUEST 
    public function checkDuplicate(Request $request) { 
        $validated = $request->validate([ 'json_data' => 'required|array' ]); 
        $params = json_encode([ 
            'json_data' => $validated['json_data'] 
        ]); 
        return response()->json([ 'success' => true, 'data' => DB::select( 'EXEC sproc_PHP_VEPartClass @mode = ?, @params = ?', [ 'CheckDuplicate', $params ] ) ]); 
    } 
    
    
    // Delete the VEPartClass 
    // POST /api/vepartclass/delete 
    // @param REQUEST 
    // @return response indicating success or failure 
    public function delete(Request $request) { 
        $validated = $request->validate([ 'json_data' => 'required|array' ]); $params = json_encode([ 'json_data' => $validated['json_data'] ]); 
        $rows = DB::select( 'EXEC sproc_PHP_VEPartClass @mode = ?, @params = ?', [ 'Delete', $params ] ); 
        $row = $rows[0] ?? null; 
        return response()->json([ 
            'success' => (int) ($row->errorcount ?? 0) === 0, 
            'message' => (string) ($row->errormsg ?? 'Deleted successfully.'), 
            'data' => $rows, ]); 
    }

}