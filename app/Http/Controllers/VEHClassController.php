<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class VEHClassController extends Controller
{
    public function index(Request $request)
    {
        return response()->json([
            "ok" => true,
            "message" => "VEHClass have been retrieved.",
            "data" => DB::select(
                'EXEC sproc_PHP_VEHClass @mode = ?',
                ['Load']
            ),
        ], 200);
    }

    public function upsert(Request $request)
    {
        $request->validate([
            'json_data' => 'required|json',
        ]);

        $rows = DB::select(
            'EXEC sproc_PHP_VEHClass @mode = ?, @params = ?',
            [
                'Upsert',
                $request->input('json_data'),
            ]
        );

        $row = $rows[0] ?? null;

        return response()->json([
            'oks' =>
                (int) ($row->errorcount ?? 0) === 0,

            'message' =>
                (string) ($row->errormsg ?? ''),

            'data' => $rows,
        ]);
    }

    public function checkDuplicate(Request $request)
    {
        $validated = $request->validate([
            'json_data' => 'required|array',
        ]);

        $params = json_encode(
            [
                'json_data' =>
                    $validated['json_data'],
            ],
            JSON_UNESCAPED_UNICODE
        );

        return response()->json([
            'success' => true,
            'data' => DB::select(
                'EXEC sproc_PHP_VEHClass @mode = ?, @params = ?',
                [
                    'CheckDuplicate',
                    $params,
                ]
            ),
        ]);
    }

    public function checkInUsed(Request $request)
    {
        $validated = $request->validate([
            'json_data' => 'required|array',
        ]);

        $params = json_encode(
            [
                'json_data' =>
                    $validated['json_data'],
            ],
            JSON_UNESCAPED_UNICODE
        );

        try {
            $rows = DB::select(
                'EXEC sproc_PHP_VEHClass @mode = ?, @params = ?',
                [
                    'CheckInUsed',
                    $params,
                ]
            );

            $row = $rows[0] ?? null;

            /*
             * Important:
             * An Invalid mode / SQL business error must NOT be interpreted
             * by the frontend as "not in use".
             */
            $errorCount =
                (int) ($row->errorcount ?? 0);

            $errorMessage =
                trim(
                    (string) (
                        $row->errormsg ??
                        ''
                    )
                );

            if (
                $errorCount > 0 ||
                $errorMessage !== ''
            ) {
                return response()->json([
                    'success' => false,
                    'message' =>
                        $errorMessage !== ''
                            ? $errorMessage
                            : 'CheckInUsed failed.',
                    'data' => $rows,
                ], 422);
            }

            $result =
                isset($row->result)
                    ? trim(
                        (string) $row->result
                    )
                    : null;

            if (
                $result !== '0' &&
                $result !== '1'
            ) {
                return response()->json([
                    'success' => false,
                    'message' =>
                        'Invalid CheckInUsed result returned by sproc_PHP_VEHClass.',
                    'data' => $rows,
                ], 500);
            }

            return response()->json([
                'success' => true,
                'data' => $rows,
            ]);
        } catch (\Throwable $e) {
            Log::error(
                'VEHClass CheckInUsed Error',
                [
                    'message' =>
                        $e->getMessage(),
                    'params' =>
                        $params,
                ]
            );

            return response()->json([
                'success' => false,
                'message' =>
                    $e->getMessage(),
                'data' => [],
            ], 500);
        }
    }

    public function delete(Request $request)
    {
        $validated = $request->validate([
            'json_data' => 'required|array',
        ]);

        $params = json_encode(
            [
                'json_data' =>
                    $validated['json_data'],
            ],
            JSON_UNESCAPED_UNICODE
        );

        try {
            $rows = DB::select(
                'EXEC sproc_PHP_VEHClass @mode = ?, @params = ?',
                [
                    'Delete',
                    $params,
                ]
            );

            $row = $rows[0] ?? null;

            $errorCount =
                (int) (
                    $row->errorcount ??
                    0
                );

            $message =
                (string) (
                    $row->errormsg ??
                    ''
                );

            return response()->json([
                'success' =>
                    $errorCount === 0,

                'message' =>
                    $message !== ''
                        ? $message
                        : (
                            $errorCount === 0
                                ? 'Deleted successfully.'
                                : 'Delete failed.'
                        ),

                'data' => $rows,
            ]);
        } catch (\Throwable $e) {
            Log::error(
                'VEHClass Delete Error',
                [
                    'message' =>
                        $e->getMessage(),
                    'params' =>
                        $params,
                ]
            );

            return response()->json([
                'success' => false,
                'message' =>
                    $e->getMessage(),
                'data' => [],
            ], 500);
        }
    }
}
