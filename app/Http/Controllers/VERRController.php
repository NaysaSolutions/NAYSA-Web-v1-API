<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class VERRController extends Controller
{
    public function index(Request $request)
    {
        try {
            $request->validate([
                'json_data' => 'required|json',
            ]);

            $params = $request->get('json_data');
            $results = DB::select(
                'EXEC sproc_PHP_VERR @mode = ?, @params = ?',
                ['Get', $params]
            );

            return response()->json([
                'success' => true,
                'data' => $results,
            ], 200);
        } catch (\Throwable $e) {
            Log::error('VERR Get failed', ['message' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function get(Request $request)
    {
        $jsonData = $request->all();
        $jsonString = json_encode($jsonData, JSON_UNESCAPED_UNICODE);

        try {
            $results = DB::select(
                'EXEC sproc_PHP_VERR @mode = ?, @params = ?',
                ['Get', $jsonString]
            );

            return response()->json([
                'success' => true,
                'data' => $results,
            ], 200);
        } catch (\Throwable $e) {
            Log::error('VERR Get failed', ['message' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }



    public function upsert(Request $request)
    {
        $validated = $request->validate([
            'json_data' => 'required|array',
        ]);

        try {
            $params = json_encode([
                'json_data' => $validated['json_data'],
            ], JSON_UNESCAPED_UNICODE);

            $results = DB::select(
                'EXEC sproc_PHP_VERR @mode = ?, @params = ?',
                ['Upsert', $params]
            );

            return response()->json([
                'success' => true,
                'status' => 'success',
                'mode' => 'Upsert',
                'data' => $results,
            ], 200);
        } catch (\Throwable $e) {
            Log::error('VERR Upsert failed', ['message' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'status' => 'error',
                'message' => 'Error executing VERR Upsert.',
                'details' => $e->getMessage(),
            ], 500);
        }
    }


    
    public function generateGL(Request $request)
    {
        $validated = $request->validate([
            'json_data' => 'required|array',
        ]);

        try {
            $params = json_encode([
                'json_data' => $validated['json_data'],
            ], JSON_UNESCAPED_UNICODE);

            $results = DB::select(
                'EXEC sproc_PHP_VERR @mode = ?, @params = ?',
                ['GenerateEntries', $params]
            );

            return response()->json([
                'success' => true,
                'status' => 'success',
                'mode' => 'Upsert',
                'data' => $results,
            ], 200);
        } catch (\Throwable $e) {
            Log::error('VERR Upsert failed', ['message' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'status' => 'error',
                'message' => 'Error executing VERR Upsert.',
                'details' => $e->getMessage(),
            ], 500);
        }
    }


    public function cancel(Request $request)
    {
        $validated = $request->validate([
            'json_data' => 'required|array',
        ]);

        try {
            $params = json_encode([
                'json_data' => $validated['json_data'],
            ], JSON_UNESCAPED_UNICODE);

            $results = DB::select(
                'EXEC sproc_PHP_VERR @mode = ?, @params = ?',
                ['Cancel', $params]
            );

            return response()->json([
                'success' => true,
                'status' => 'success',
                'mode' => 'Cancel',
                'data' => $results,
            ], 200);
        } catch (\Throwable $e) {
            Log::error('VERR Cancel failed', ['message' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'status' => 'error',
                'message' => 'Error executing VERR Cancel.',
                'details' => $e->getMessage(),
            ], 500);
        }
    }

    public function history(Request $request)
    {
        $validated = $request->validate([
            'json_data' => 'required|array',
        ]);

        try {
            $params = json_encode([
                'json_data' => $validated['json_data'],
            ], JSON_UNESCAPED_UNICODE);

            $results = DB::select(
                'EXEC sproc_PHP_VERR @mode = ?, @params = ?',
                ['History', $params]
            );

            return response()->json([
                'success' => true,
                'status' => 'success',
                'mode' => 'History',
                'data' => $results,
            ], 200);
        } catch (\Throwable $e) {
            Log::error('VERR History failed', ['message' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'status' => 'error',
                'message' => 'Error executing VERR History.',
                'details' => $e->getMessage(),
            ], 500);
        }
    }

    public function posting()
    {
        try {
            $results = DB::select(
                'EXEC sproc_PHP_VERR @mode = ?',
                ['Posting']
            );

            $decoded = [];

            if (! empty($results) && isset($results[0]->result)) {
                $decoded = json_decode($results[0]->result, true) ?? [];
            }

            return response()->json([
                'success' => true,
                'data' => $decoded,
            ], 200);
        } catch (\Throwable $e) {
            Log::error('VERR Posting failed', ['message' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function checkDuplicate(Request $request)
    {
        try {
            $params = json_encode([
                'json_data' => $request->all(),
            ], JSON_UNESCAPED_UNICODE);

            $results = DB::select(
                'EXEC sproc_PHP_VERR @mode = ?, @params = ?',
                ['CheckDuplicate', $params]
            );

            return response()->json([
                'success' => true,
                'data' => $results,
            ], 200);
        } catch (\Throwable $e) {
            Log::error('VERR CheckDuplicate failed', ['message' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }


      public function finalize(Request $request)
    {
        $validated = $request->validate([
            'json_data' => 'required|array',
        ]);

        try {
            $params = json_encode([
                'json_data' => $validated['json_data'],
            ], JSON_UNESCAPED_UNICODE);

            /*
             * IMPORTANT:
             * Posting/Finalize belongs to the dedicated posting procedure.
             * Do NOT call sproc_PHP_VERR with @mode = 'Finalize'.
             */
            $results = DB::select(
                'EXEC sproc_PHP_Posting_VERR @mode = ?, @params = ?',
                ['Finalize', $params]
            );

            // sproc_PHP_Posting_VERR may return a controlled validation error row.
            if (! empty($results)) {
                $first = $results[0];
                $errorCount = (int) ($first->errorCount ?? 0);
                $errorMsg = trim((string) ($first->errorMsg ?? ''));

                if ($errorCount > 0) {
                    return response()->json([
                        'success' => false,
                        'status' => 'error',
                        'message' => $errorMsg !== ''
                            ? $errorMsg
                            : 'Unable to finalize Vehicle Receiving Report.',
                        'data' => $results,
                    ], 422);
                }
            }

            return response()->json([
                'success' => true,
                'status' => 'success',
                'mode' => 'Finalize',
                'data' => $results,
            ], 200);
        } catch (\Throwable $e) {
            Log::error('VERR Finalize failed', [
                'message' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }


}
