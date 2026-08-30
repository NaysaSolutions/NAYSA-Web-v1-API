<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class VEMakeController extends Controller
{
    public function index()
    {
        return response()->json(['success' => true, 'data' => DB::select('EXEC sproc_PHP_VEMake @mode = ?', ['Load'])]);
    }

    public function upsert(Request $request)
    {
        $request->validate(['json_data' => 'required|json']);
        $rows = DB::select('EXEC sproc_PHP_VEMake @mode = ?, @params = ?', ['Upsert', $request->input('json_data')]);
        $row = $rows[0] ?? null;
        return response()->json([
            'success' => (int) ($row->errorcount ?? 0) === 0,
            'errormsg' => (string) ($row->errormsg ?? ''),
            'data' => $rows,
        ]);
    }

    public function checkDuplicate(Request $request)
    {
        $validated = $request->validate(['json_data' => 'required|array']);
        $params = json_encode(['json_data' => $validated['json_data']]);
        return response()->json(['success' => true, 'data' => DB::select('EXEC sproc_PHP_VEMake @mode = ?, @params = ?', ['CheckDuplicate', $params])]);
    }

    public function checkInUsed(Request $request)
    {
        $validated = $request->validate(['json_data' => 'required|array']);
        $params = json_encode(['json_data' => $validated['json_data']]);
        return response()->json(['success' => true, 'data' => DB::select('EXEC sproc_PHP_VEMake @mode = ?, @params = ?', ['CheckInUsed', $params])]);
    }

    public function delete(Request $request)
    {
        $validated = $request->validate(['json_data' => 'required|array']);
        $params = json_encode(['json_data' => $validated['json_data']]);
        $rows = DB::select('EXEC sproc_PHP_VEMake @mode = ?, @params = ?', ['Delete', $params]);
        $row = $rows[0] ?? null;
        return response()->json([
            'success' => (int) ($row->errorcount ?? 0) === 0,
            'message' => (string) ($row->errormsg ?? 'Deleted successfully.'),
            'data' => $rows,
        ]);
    }
}
