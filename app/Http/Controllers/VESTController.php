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
     * reads modelYr/csNo. Normalize VE_ID aliases as well so the selected
     * VEFIFO_LOC vehicle identity is persisted in VEST_DT1.VE_ID.
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

            if (!isset($row['veId']) || $row['veId'] === null || $row['veId'] === '') {
                $row['veId'] = $row['ve_id']
                    ?? $row['VE_ID']
                    ?? $row['VeId']
                    ?? $row['uniqueKey']
                    ?? '';
            }

            // VEST_DT1.VE_ID is NVARCHAR(100).
            if ($row['veId'] !== null) {
                $row['veId'] = substr((string) $row['veId'], 0, 100);
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

            // Use the same request envelope as the working FGST finalize method.
            $params = json_encode(['json_data' => $validated['json_data']], JSON_UNESCAPED_UNICODE);

            $results = DB::select(
                'EXEC sproc_PHP_Posting_VEST @mode = ?, @params = ?',
                ['Finalize', $params]
            );

            /*
             * A posting helper called inside the SQL procedure may emit an
             * empty/unrelated result set before the final VEST result. When
             * that happens, verify the document status instead of returning
             * a silent success with an empty data array.
             */
            $validResults = array_values(array_filter($results, static function ($row) {
                return is_object($row)
                    && (property_exists($row, 'result')
                        || property_exists($row, 'errorMsg')
                        || property_exists($row, 'errorCount'));
            }));

            if (count($validResults) === 0) {
                $groupIds = $this->getFinalizeGroupIds($validated['json_data']);
                $posted = $this->getPostedVESTRows($groupIds);

                if (count($groupIds) > 0 && count($posted) === count($groupIds)) {
                    return response()->json([[
                        'result' => 'The following VEST Transactions have been posted successfully.',
                        'errorMsg' => '',
                        'errorCount' => 0,
                    ]], 200);
                }

                $message = 'sproc_PHP_Posting_VEST returned no VEST result row and the selected transaction remains unposted.';

                Log::error('VEST Finalize returned no result row.', [
                    'groupIds' => $groupIds,
                    'rawResultCount' => count($results),
                ]);

                return $this->postingErrorResponse($message);
            }

            /*
             * useHandlePostTran reads response.data[0].result/errorMsg.
             * Return the SQL rows directly; do not wrap them in { success, data }.
             */
            foreach ($validResults as $row) {
                if (!property_exists($row, 'result')) {
                    $row->result = '';
                }

                if (!property_exists($row, 'errorMsg')) {
                    $row->errorMsg = '';
                }

                if (!property_exists($row, 'errorCount')) {
                    $row->errorCount = $row->errorMsg !== '' ? 1 : 0;
                }

                // Some versions of useHandlePostTran display only `result`.
                // Mirror the SQL error there so the actual failure is not hidden.
                if (
                    trim((string) $row->result) === ''
                    && trim((string) $row->errorMsg) !== ''
                ) {
                    $row->result = $row->errorMsg;
                }
            }

            return response()->json($validResults, 200);
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
                    $groupIds = $this->getFinalizeGroupIds($request->input('json_data', []));
                    $posted = $this->getPostedVESTRows($groupIds);

                    if (count($groupIds) > 0 && count($posted) === count($groupIds)) {
                        return response()->json([[
                            'result' => 'The following VEST Transactions have been posted successfully.',
                            'errorMsg' => '',
                            'errorCount' => 0,
                            'warning' => 'SQL Server returned a null aggregate warning, but the VEST transaction was already posted successfully.',
                        ]], 200);
                    }
                } catch (\Throwable $verifyError) {
                    Log::warning('VEST finalize warning verification failed.', [
                        'message' => $verifyError->getMessage(),
                    ]);
                }
            }

            Log::error('VEST Finalize failed.', [
                'message' => $message,
                'groupIds' => $this->getFinalizeGroupIds($request->input('json_data', [])),
            ]);

            /*
             * Return the SQL error through the normal posting result contract.
             * The shared useHandlePostTran helper can then display errorMsg
             * instead of only reporting a generic Axios/HTTP failure.
             */
            return $this->postingErrorResponse($message);
        }
    }

    private function getFinalizeGroupIds($payload): array
    {
        if (!is_array($payload)) {
            return [];
        }

        $rows = $payload['dt1']
            ?? $payload['selectedData']
            ?? $payload['selectedRows']
            ?? $payload['data']
            ?? [];

        if (!is_array($rows)) {
            return [];
        }

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

            if ($groupId !== null && trim((string) $groupId) !== '') {
                $groupIds[] = trim((string) $groupId);
            }
        }

        return array_values(array_unique($groupIds));
    }

    private function getPostedVESTRows(array $groupIds): array
    {
        if (count($groupIds) === 0) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($groupIds), '?'));

        return DB::select(
            "SELECT vest_id, vest_no, branch_code, vest_status
             FROM vest_hd
             WHERE vest_id IN ($placeholders)
               AND ISNULL(vest_status, '') = 'F'",
            $groupIds
        );
    }

    private function postingErrorResponse(string $message)
    {
        return response()->json([[
            'result' => $message,
            'errorMsg' => $message,
            'errorCount' => 1,
        ]], 200);
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
