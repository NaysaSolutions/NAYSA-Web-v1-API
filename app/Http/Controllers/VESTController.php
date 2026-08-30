<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class VESTController extends Controller
{
    public function index(Request $request)
    {
        try {
            $allData = $request->all();
            $payload = isset($allData['json_data']) ? $allData['json_data'] : $allData;
            $params = json_encode(['json_data' => $payload], JSON_UNESCAPED_UNICODE);

            $results = DB::select(
                'EXEC sproc_PHP_VEST @mode = ?, @params = ?',
                ['get', $params]
            );

            return response()->json([
                'success' => true,
                'data' => $results,
            ], 200);
        } catch (\Throwable $e) {
            Log::error('VEST Get failed', [
                'message' => $e->getMessage(),
                'request' => $request->all(),
            ]);

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
                'EXEC sproc_PHP_VEST @mode = ?, @params = ?',
                ['Get', $jsonString]
            );

            return response()->json([
                'success' => true,
                'data' => $results,
            ], 200);
        } catch (\Throwable $e) {
            Log::error('VEST Get failed', [
                'message' => $e->getMessage(),
                'request' => $request->all(),
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function posting(Request $request)
    {
        try {
            $results = DB::select(
                'EXEC sproc_PHP_VEST @mode = ?',
                ['Posting']
            );

            // Keep the same response shape as FGST.
            // VEST.jsx expects response.data[0].result to contain the JSON string.
            return response()->json([
                'success' => true,
                'data' => $results,
            ], 200);
        } catch (\Throwable $e) {
            Log::error('VEST Posting failed', [
                'message' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function upsert(Request $request)
    {
        try {
            $allData = $request->all();
            $payload = isset($allData['json_data']) ? $allData['json_data'] : $allData;
            $payload = $this->normalizeUpsertPayload($payload);
            $params = json_encode(['json_data' => $payload], JSON_UNESCAPED_UNICODE);

            Log::info('VEST Upsert Params:', [
                'params' => $params,
            ]);

            $result = DB::select(
                'EXEC sproc_PHP_VEST @mode = ?, @params = ?',
                ['Upsert', $params]
            );

            return response()->json([
                'status' => 'success',
                'data' => $result,
            ], 200);
        } catch (\Throwable $e) {
            Log::error('Error executing VEST Upsert:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request' => $request->all(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Error executing VEST Upsert.',
                'details' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Normalize frontend aliases into the field names expected by sproc_PHP_VEST.
     *
     * VEST.jsx currently sends modelYear/chassisNo while the stored procedure
     * reads modelYr/csNo. Keep both aliases so retrieval/editing remains tolerant.
     */
    private function normalizeUpsertPayload($payload)
    {
        if (!is_array($payload)) {
            return $payload;
        }

        if (!isset($payload['dt1']) || !is_array($payload['dt1'])) {
            return $payload;
        }

        foreach ($payload['dt1'] as &$row) {
            if (!is_array($row)) {
                continue;
            }

            if (!isset($row['modelYr']) || $row['modelYr'] === null || $row['modelYr'] === '') {
                $row['modelYr'] = $row['modelYear'] ?? $row['model_yr'] ?? '';
            }

            if (!isset($row['csNo']) || $row['csNo'] === null || $row['csNo'] === '') {
                $row['csNo'] = $row['chassisNo'] ?? $row['cs_no'] ?? '';
            }

            // VEST_DT1.UNIQUE_KEY is NVARCHAR(40), so preserve up to 40 characters.
            if (isset($row['uniqueKey']) && $row['uniqueKey'] !== null) {
                $row['uniqueKey'] = substr((string) $row['uniqueKey'], 0, 40);
            }

            // VEST_DT1.OPERATION is NVARCHAR(1).
            if (isset($row['operation']) && $row['operation'] !== null) {
                $row['operation'] = substr((string) $row['operation'], 0, 1);
            }
        }

        unset($row);

        return $payload;
    }

    public function finalize(Request $request)
    {
        try {
            $validated = $request->validate([
                'json_data' => 'required|array',
            ]);

            $params = json_encode(
                ['json_data' => $validated['json_data']],
                JSON_UNESCAPED_UNICODE
            );

            $results = DB::select(
                'EXEC sproc_PHP_Posting_VEST @mode = ?, @params = ?',
                ['Finalize', $params]
            );

            return response()->json([
                'success' => true,
                'data' => $results,
            ], 200);
        } catch (\Throwable $e) {
            $message = $e->getMessage();

            /*
             * Same protection used by FGST: SQL Server/ODBC can surface
             * "Null value is eliminated by an aggregate" as an exception
             * even after the posting procedure has already completed.
             */
            if (
                str_contains($message, 'SQLSTATE[01003]') ||
                str_contains($message, 'Null value is eliminated by an aggregate')
            ) {
                try {
                    $payload = $request->input('json_data', []);
                    $rows = $payload['dt1']
                        ?? $payload['selectedData']
                        ?? $payload['selectedRows']
                        ?? $payload['data']
                        ?? [];

                    if (is_array($rows) && count($rows) > 0) {
                        $groupIds = [];

                        foreach ($rows as $row) {
                            if (!is_array($row)) {
                                continue;
                            }

                            $groupId = $row['groupId']
                                ?? $row['vestId']
                                ?? $row['documentID']
                                ?? $row['docId']
                                ?? null;

                            if ($groupId) {
                                $groupIds[] = $groupId;
                            }
                        }

                        $groupIds = array_values(array_unique($groupIds));

                        if (count($groupIds) > 0) {
                            $placeholders = implode(',', array_fill(0, count($groupIds), '?'));

                            $posted = DB::select(
                                "SELECT vest_id, vest_no, branch_code, vest_status
                                 FROM vest_hd
                                 WHERE vest_id IN ($placeholders)
                                   AND ISNULL(vest_status, '') = 'F'",
                                $groupIds
                            );

                            if (count($posted) === count($groupIds)) {
                                return response()->json([
                                    'success' => true,
                                    'warning' => 'SQL Server returned a null aggregate warning, but the VEST transaction was already posted successfully.',
                                    'data' => [[
                                        'result' => 'The following VEST Transactions have been posted successfully.',
                                    ]],
                                    'posted' => $posted,
                                ], 200);
                            }
                        }
                    }
                } catch (\Throwable $verifyError) {
                    Log::warning('VEST finalize warning verification failed.', [
                        'message' => $verifyError->getMessage(),
                    ]);
                }
            }

            Log::error('VEST Finalize failed', [
                'message' => $message,
                'request' => $request->all(),
            ]);

            return response()->json([
                'success' => false,
                'message' => $message,
            ], 500);
        }
    }

    public function generateGL(Request $request)
    {
        try {
            $allData = $request->all();
            $payload = isset($allData['json_data']) ? $allData['json_data'] : $allData;

            if (!$payload) {
                return response()->json([
                    'error' => 'Missing json_data payload',
                ], 400);
            }

            $payload = $this->normalizeUpsertPayload($payload);
            $jsonString = json_encode(['json_data' => $payload], JSON_UNESCAPED_UNICODE);

            $results = DB::select(
                'EXEC sproc_PHP_VEST @mode = ?, @params = ?',
                ['GenerateEntries', $jsonString]
            );

            return response()->json([
                'status' => 'success',
                'data' => $results,
            ], 200);
        } catch (\Throwable $e) {
            Log::error('Error executing sproc_PHP_VEST GenerateEntries:', [
                'message' => $e->getMessage(),
                'request' => $request->all(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to generate VEST entries.',
                'details' => $e->getMessage(),
            ], 500);
        }
    }

    public function cancel(Request $request)
    {
        try {
            $allData = $request->all();
            $payload = isset($allData['json_data']) ? $allData['json_data'] : $allData;
            $params = json_encode(['json_data' => $payload], JSON_UNESCAPED_UNICODE);

            $result = DB::select(
                'EXEC sproc_PHP_VEST @mode = ?, @params = ?',
                ['Cancel', $params]
            );

            return response()->json([
                'status' => 'success',
                'data' => $result,
            ], 200);
        } catch (\Throwable $e) {
            Log::error('VEST Cancel failed', [
                'message' => $e->getMessage(),
                'request' => $request->all(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Error executing VEST Cancel.',
                'details' => $e->getMessage(),
            ], 500);
        }
    }

    public function history(Request $request)
    {
        try {
            $allData = $request->all();
            $payload = isset($allData['json_data']) ? $allData['json_data'] : $allData;
            $params = json_encode(['json_data' => $payload], JSON_UNESCAPED_UNICODE);

            $results = DB::select(
                'EXEC sproc_PHP_VEST @mode = ?, @params = ?',
                ['History', $params]
            );

            return response()->json([
                'status' => 'success',
                'data' => $results,
            ], 200);
        } catch (\Throwable $e) {
            Log::error('VEST History failed', [
                'message' => $e->getMessage(),
                'request' => $request->all(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Error executing VEST History.',
                'details' => $e->getMessage(),
            ], 500);
        }
    }

    public function find(Request $request)
    {
        try {
            $allData = $request->all();
            $payload = isset($allData['json_data']) ? $allData['json_data'] : $allData;
            $params = json_encode(['json_data' => $payload], JSON_UNESCAPED_UNICODE);

            $results = DB::select(
                'EXEC sproc_PHP_VEST @mode = ?, @params = ?',
                ['Find', $params]
            );

            return response()->json([
                'status' => 'success',
                'data' => $results,
            ], 200);
        } catch (\Throwable $e) {
            Log::error('VEST Find failed', [
                'message' => $e->getMessage(),
                'request' => $request->all(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Error executing VEST Find.',
                'details' => $e->getMessage(),
            ], 500);
        }
    }

    // Kept for backward compatibility with the existing checkVESTDuplicate route.
    public function checkDuplicate(Request $request)
    {
        try {
            $params = json_encode([
                'json_data' => $request->all(),
            ], JSON_UNESCAPED_UNICODE);

            $results = DB::select(
                'EXEC sproc_PHP_VEST @mode = ?, @params = ?',
                ['CheckDuplicate', $params]
            );

            return response()->json([
                'success' => true,
                'data' => $results,
            ], 200);
        } catch (\Throwable $e) {
            Log::error('VEST CheckDuplicate failed', [
                'message' => $e->getMessage(),
                'request' => $request->all(),
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

}
