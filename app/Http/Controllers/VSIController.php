<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class VSIController extends Controller
{
    public function index(Request $request)
    {
        try {
            $request->validate([
                'json_data' => 'required|json',
            ]);

            $params = $request->get('json_data');
            $results = DB::select(
                'EXEC sproc_PHP_VSI @mode = ?, @params = ?',
                ['Get', $params]
            );

            return response()->json([
                'success' => true,
                'data' => $results,
            ], 200);
        } catch (\Throwable $e) {
            Log::error('VSI Get failed', ['message' => $e->getMessage()]);

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
                'EXEC sproc_PHP_VSI @mode = ?, @params = ?',
                ['Get', $jsonString]
            );

            return response()->json([
                'success' => true,
                'data' => $results,
            ], 200);
        } catch (\Throwable $e) {
            Log::error('VSI Get failed', ['message' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function upsert(Request $request)
    {
        $request->validate([
            'json_data' => 'required|array',
            'json_data.branchCode' => 'required|string',
            'json_data.vsiDate' => 'required',
        ]);

        try {
            $params = json_encode([
                'json_data' => $request->input('json_data'),
            ], JSON_UNESCAPED_UNICODE);

            $results = DB::select(
                'EXEC sproc_PHP_VSI @mode = ?, @params = ?',
                ['Upsert', $params]
            );

            return response()->json([
                'success' => true,
                'status' => 'success',
                'mode' => 'Upsert',
                'data' => $results,
            ], 200);
        } catch (\Throwable $e) {
            Log::error('VSI Upsert failed', ['message' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'status' => 'error',
                'message' => 'Error executing VSI Upsert.',
                'details' => $e->getMessage(),
            ], 500);
        }
    }

    public function openVDR(Request $request)
    {
        $validated = $request->validate([
            'branchCode' => 'required|string',
        ]);

        try {
            $params = json_encode(['json_data' => $validated], JSON_UNESCAPED_UNICODE);
            $results = DB::select(
                'EXEC sproc_PHP_VSI @mode = ?, @params = ?',
                ['OpenVDR', $params]
            );

            return response()->json(['success' => true, 'data' => $results], 200);
        } catch (\Throwable $e) {
            Log::error('VSI OpenVDR failed', ['message' => $e->getMessage()]);

            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function generateEntries(Request $request)
    {
        $request->validate(['json_data' => 'required|array']);

        try {
            $params = json_encode(['json_data' => $request->input('json_data')], JSON_UNESCAPED_UNICODE);
            $results = DB::select(
                'EXEC sproc_PHP_VSI @mode = ?, @params = ?',
                ['GenerateEntries', $params]
            );

            return response()->json([
                'success' => true,
                'status' => 'success',
                'data' => $results,
            ], 200);
        } catch (\Throwable $e) {
            Log::error('VSI GenerateEntries failed', ['message' => $e->getMessage()]);

            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
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
                'EXEC sproc_PHP_VSI @mode = ?, @params = ?',
                ['Cancel', $params]
            );

            return response()->json([
                'success' => true,
                'status' => 'success',
                'mode' => 'Cancel',
                'data' => $results,
            ], 200);
        } catch (\Throwable $e) {
            Log::error('VSI Cancel failed', ['message' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'status' => 'error',
                'message' => 'Error executing VSI Cancel.',
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
                'EXEC sproc_PHP_VSI @mode = ?, @params = ?',
                ['History', $params]
            );

            return response()->json([
                'success' => true,
                'status' => 'success',
                'mode' => 'History',
                'data' => $results,
            ], 200);
        } catch (\Throwable $e) {
            Log::error('VSI History failed', ['message' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'status' => 'error',
                'message' => 'Error executing VSI History.',
                'details' => $e->getMessage(),
            ], 500);
        }
    }

    public function posting()
    {
        try {
            $results = DB::select(
                'EXEC sproc_PHP_VSI @mode = ?',
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
            Log::error('VSI Posting failed', ['message' => $e->getMessage()]);

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
            'json_data.userCode' => 'required|string',
            'json_data.dt1' => 'required|array|min:1',
            'json_data.dt1.*.groupId' => 'required|string',
        ]);

        try {
            $params = json_encode(['json_data' => $validated['json_data']], JSON_UNESCAPED_UNICODE);
            $results = DB::select(
                'EXEC sproc_PHP_Posting_VSI @mode = ?, @params = ?',
                ['Finalize', $params]
            );

            if (! empty($results)) {
                $errorCount = (int) ($results[0]->errorCount ?? 0);
                $errorMsg = trim((string) ($results[0]->errorMsg ?? ''));
                if ($errorCount > 0) {
                    return response()->json([
                        'success' => false,
                        'status' => 'error',
                        'message' => $errorMsg ?: 'Unable to finalize Vehicle Sales Invoice.',
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
            Log::error('VSI Finalize failed', ['message' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'status' => 'error',
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
                'EXEC sproc_PHP_VSI @mode = ?, @params = ?',
                ['CheckDuplicate', $params]
            );

            return response()->json([
                'success' => true,
                'data' => $results,
            ], 200);
        } catch (\Throwable $e) {
            Log::error('VSI CheckDuplicate failed', ['message' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
