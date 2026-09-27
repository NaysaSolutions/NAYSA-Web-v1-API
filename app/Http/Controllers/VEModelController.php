<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class VEModelController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | LOAD
    |--------------------------------------------------------------------------
    | No makeCode:
    |   GET /api/veModel
    |   -> returns all Vehicle Models
    |
    | With makeCode:
    |   GET /api/veModel?makeCode=TOYOTA
    |   -> returns only models tagged to TOYOTA
    */
    public function index(Request $request)
    {
        $makeCode = strtoupper(trim((string) $request->query('makeCode', '')));

        if ($makeCode !== '') {
            $params = json_encode([
                'json_data' => [
                    'makeCode' => $makeCode,
                ],
            ]);

            $rows = DB::select(
                'EXEC sproc_PHP_VEModel @mode = ?, @params = ?',
                ['Load', $params]
            );
        } else {
            $rows = DB::select(
                'EXEC sproc_PHP_VEModel @mode = ?',
                ['Load']
            );
        }

        return response()->json([
            'success' => true,
            'data' => $rows,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | UPSERT
    |--------------------------------------------------------------------------
    */
    public function upsert(Request $request)
    {
        $request->validate([
            'json_data' => 'required|json',
        ]);

        $rows = DB::select(
            'EXEC sproc_PHP_VEModel @mode = ?, @params = ?',
            [
                'Upsert',
                $request->input('json_data'),
            ]
        );

        $row = $rows[0] ?? null;

        return response()->json([
            'success' => (int) ($row->errorcount ?? 0) === 0,
            'errormsg' => (string) ($row->errormsg ?? ''),
            'data' => $rows,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | CHECK DUPLICATE
    |--------------------------------------------------------------------------
    | Composite duplicate:
    |   MAKE_CODE + MODEL_CODE
    */
    public function checkDuplicate(Request $request)
    {
        $validated = $request->validate([
            'json_data' => 'required|array',
            'json_data.makeCode' => 'required',
            'json_data.code' => 'required',
        ]);

        $params = json_encode([
            'json_data' => $validated['json_data'],
        ]);

        return response()->json([
            'success' => true,
            'data' => DB::select(
                'EXEC sproc_PHP_VEModel @mode = ?, @params = ?',
                [
                    'CheckDuplicate',
                    $params,
                ]
            ),
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | CHECK IN USED
    |--------------------------------------------------------------------------
    */
    public function checkInUsed(Request $request)
    {
        $validated = $request->validate([
            'json_data' => 'required|array',
            'json_data.makeCode' => 'required',
            'json_data.code' => 'required',
        ]);

        $params = json_encode([
            'json_data' => $validated['json_data'],
        ]);

        return response()->json([
            'success' => true,
            'data' => DB::select(
                'EXEC sproc_PHP_VEModel @mode = ?, @params = ?',
                [
                    'CheckInUsed',
                    $params,
                ]
            ),
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | DELETE
    |--------------------------------------------------------------------------
    */
    public function delete(Request $request)
    {
        $validated = $request->validate([
            'json_data' => 'required|array',
            'json_data.makeCode' => 'required',
            'json_data.code' => 'required',
        ]);

        $params = json_encode([
            'json_data' => $validated['json_data'],
        ]);

        $rows = DB::select(
            'EXEC sproc_PHP_VEModel @mode = ?, @params = ?',
            [
                'Delete',
                $params,
            ]
        );

        $row = $rows[0] ?? null;

        return response()->json([
            'success' => (int) ($row->errorcount ?? 0) === 0,
            'message' => (string) ($row->errormsg ?? 'Deleted successfully.'),
            'data' => $rows,
        ]);
    }
}
