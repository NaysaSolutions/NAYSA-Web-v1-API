<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
// pang tawag sa database operations
use Illuminate\Support\Facades\DB;
// pang log ng errors
use Illuminate\Support\Facades\Log;

class VETypeController extends Controller
{
    // Retrive the VEType
    // Get /api/vetype
    // @param REQUEST
    public function index(Request $request){
        return response()->json([
            "ok" => true,
            "message" => "type have been retrieved.",
            "data" => DB::select('EXEC sproc_PHP_VEType @mode = ?', ['Load'])]);
    }

    // Upsert the VEType
    // POST /api/vetype/upsert
    // @param REQUEST
    public function upsert(Request $request)
    {
        $request->validate(['json_data' => 'required|json']);
        $rows = DB::select('EXEC sproc_PHP_VEType @mode = ?, @params = ?', ['Upsert', $request->input('json_data')]);
        $row = $rows[0] ?? null;
        return response()->json([
            'oks' => (int) ($row->errorcount ?? 0) === 0,
            'message' => (string) ($row->errormsg ?? ''),
            'data' => $rows,
        ]);
    }

    // Check for duplicate VEType
    // POST /api/vetype/check-duplicate
    // @param REQUEST
    public function checkDuplicate(Request $request)
    {
        $validated = $request->validate(['json_data' => 'required|array']);
        $params = json_encode(['json_data' => $validated['json_data']]);
        return response()->json(['success' => true, 'data' => DB::select('EXEC sproc_PHP_VEType @mode = ?, @params = ?', ['CheckDuplicate', $params])]);
    }

    // Delete the VEType
    // POST /api/vetype/delete
    // @param REQUEST
    // @return response indicating success or failure
    public function delete(Request $request)
    {
        $validated = $request->validate(['json_data' => 'required|array']);
        $params = json_encode(['json_data' => $validated['json_data']]);
        $rows = DB::select('EXEC sproc_PHP_VEType @mode = ?, @params = ?', ['Delete', $params]);
        $row = $rows[0] ?? null;
        return response()->json([
            'success' => (int) ($row->errorcount ?? 0) === 0,
            'message' => (string) ($row->errormsg ?? 'Deleted successfully.'),
            'data' => $rows,
        ]);
    }

}
