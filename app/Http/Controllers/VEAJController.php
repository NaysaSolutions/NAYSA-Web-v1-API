<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class VEAJController extends Controller
{
    public function index(Request $request)
    {
        try {
            $request->validate([
                'json_data' => 'required|json',
            ]);

            $params = $request->get('json_data');
            $results = DB::select(
                'EXEC sproc_PHP_VEAJ @mode = ?, @params = ?',
                ['Get', $params]
            );

            return response()->json([
                'success' => true,
                'data' => $results,
            ], 200);
        } catch (\Throwable $e) {
            Log::error('VEAJ Get failed', ['message' => $e->getMessage()]);

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
                'EXEC sproc_PHP_VEAJ @mode = ?, @params = ?',
                ['Get', $jsonString]
            );

            return response()->json([
                'success' => true,
                'data' => $results,
            ], 200);
        } catch (\Throwable $e) {
            Log::error('VEAJ Get failed', ['message' => $e->getMessage()]);

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
                'EXEC sproc_PHP_VEAJ @mode = ?, @params = ?',
                ['Upsert', $params]
            );

            return response()->json([
                'success' => true,
                'status' => 'success',
                'mode' => 'Upsert',
                'data' => $results,
            ], 200);
        } catch (\Throwable $e) {
            Log::error('VEAJ Upsert failed', ['message' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'status' => 'error',
                'message' => 'Error executing VEAJ Upsert.',
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
                'EXEC sproc_PHP_VEAJ @mode = ?, @params = ?',
                ['Cancel', $params]
            );

            return response()->json([
                'success' => true,
                'status' => 'success',
                'mode' => 'Cancel',
                'data' => $results,
            ], 200);
        } catch (\Throwable $e) {
            Log::error('VEAJ Cancel failed', ['message' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'status' => 'error',
                'message' => 'Error executing VEAJ Cancel.',
                'details' => $e->getMessage(),
            ], 500);
        }
    }

    public function generateGL(Request $request)
    {
        try {
            $jsonData = $request->input('json_data');

            if (! $jsonData) {
                return response()->json(['error' => 'Missing json_data'], 400);
            }

            $params = json_encode([
                'json_data' => $jsonData,
            ], JSON_UNESCAPED_UNICODE);

            $results = DB::select(
                'EXEC sproc_PHP_VEAJ @mode = ?, @params = ?',
                ['GenerateEntries', $params]
            );

            return response()->json([
                'status' => 'success',
                'data' => $results,
            ], 200);
        } catch (\Throwable $e) {
            Log::error('VEAJ GenerateEntries failed', ['message' => $e->getMessage()]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to generate VEAJ accounting entries.',
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
                'EXEC sproc_PHP_VEAJ @mode = ?, @params = ?',
                ['History', $params]
            );

            return response()->json([
                'success' => true,
                'status' => 'success',
                'mode' => 'History',
                'data' => $results,
            ], 200);
        } catch (\Throwable $e) {
            Log::error('VEAJ History failed', ['message' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'status' => 'error',
                'message' => 'Error executing VEAJ History.',
                'details' => $e->getMessage(),
            ], 500);
        }
    }

    public function posting()
    {
        try {
            $results = DB::select(
                'EXEC sproc_PHP_VEAJ @mode = ?',
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
            Log::error('VEAJ Posting failed', ['message' => $e->getMessage()]);

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

            $results = DB::select(
                'EXEC sproc_PHP_Posting_VEAJ @mode = ?, @params = ?',
                ['Finalize', $params]
            );

            if (! empty($results) && isset($results[0]->errorCount) && (int) $results[0]->errorCount > 0) {
                return response()->json([
                    'success' => false,
                    'message' => $results[0]->errorMsg ?? 'Unable to post VEAJ.',
                    'data' => $results,
                ], 422);
            }

            return response()->json([
                'success' => true,
                'data' => $results,
            ], 200);
        } catch (\Throwable $e) {
            Log::error('VEAJ Finalize failed', ['message' => $e->getMessage()]);

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
                'EXEC sproc_PHP_VEAJ @mode = ?, @params = ?',
                ['CheckDuplicate', $params]
            );

            return response()->json([
                'success' => true,
                'data' => $results,
            ], 200);
        } catch (\Throwable $e) {
            Log::error('VEAJ CheckDuplicate failed', ['message' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function validateUpload(Request $request)
    {
        $validated = $request->validate([
            'json_data' => 'required|array',
        ]);

        try {
            $params = json_encode([
                'json_data' => $validated['json_data'],
            ], JSON_UNESCAPED_UNICODE);

            $results = DB::select(
                'EXEC sproc_PHP_VEAJ @mode = ?, @params = ?',
                ['ValidateUpload', $params]
            );

            $rawResult = $results[0]->result ?? $results[0]->Result ?? null;
            $decodedResult = is_string($rawResult) ? json_decode($rawResult, true) : $rawResult;

            return response()->json([
                'status' => 'success',
                'result' => $decodedResult,
                'data' => $results,
            ], 200);
        } catch (\Throwable $e) {
            Log::error('VEAJ ValidateUpload failed', ['message' => $e->getMessage()]);

            return response()->json([
                'status' => 'error',
                'message' => 'Error validating VEAJ upload.',
                'details' => $e->getMessage(),
            ], 500);
        }
    }

    public function checkBBUploaded(Request $request)
    {
        try {
            $params = json_decode($request->PARAMS ?? '{}', true);
            $payload = [
                'json_data' => [
                    'branchCode' => $params['branchCode'] ?? '',
                ],
            ];

            $results = DB::select(
                'EXEC sproc_PHP_VEAJ @mode = ?, @params = ?',
                ['CheckBBUploaded', json_encode($payload, JSON_UNESCAPED_UNICODE)]
            );

            $rawResult = $results[0]->result ?? '{}';
            $decodedResult = json_decode($rawResult, true);

            return response()->json([
                'success' => true,
                'result' => $decodedResult,
            ], 200);
        } catch (\Throwable $e) {
            Log::error('VEAJ CheckBBUploaded failed', ['message' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
