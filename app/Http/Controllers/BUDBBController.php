<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BUDBBController extends Controller
{
    private string $sproc = 'sproc_PHP_BUDBB';

    private function execSproc(string $mode, ?string $params = null): array
    {
        if ($params === null) {
            return DB::select("EXEC {$this->sproc} @mode = ?", [$mode]);
        }

        return DB::select("EXEC {$this->sproc} @mode = ?, @params = ?", [$mode, $params]);
    }

    private function wrapJsonData(array $data): string
    {
        return json_encode(['json_data' => $data], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    private function getJsonDataPayload(Request $request): array
    {
        $payload = $request->input('json_data', $request->all());

        if (is_string($payload)) {
            $decoded = json_decode($payload, true);

            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                return $decoded['json_data'] ?? $decoded;
            }

            return [];
        }

        if (is_array($payload)) {
            return $payload['json_data'] ?? $payload;
        }

        return [];
    }

    private function normalizeSprocResult(array $rows): array
    {
        $first = $rows[0] ?? null;

        if (!$first) {
            return [
                'errorCount' => 0,
                'errorMsg' => '',
                'result' => null,
            ];
        }

        $errorCount = (int)(
            $first->errorCount ??
            $first->errorcount ??
            $first->ERRORCOUNT ??
            0
        );

        $errorMsg = (string)(
            $first->errorMsg ??
            $first->errormsg ??
            $first->ERRORMSG ??
            ''
        );

        $result = $first->result ?? $first->RESULT ?? null;

        return [
            'errorCount' => $errorCount,
            'errorMsg' => $errorMsg,
            'result' => $result,
        ];
    }

    private function responseFromSproc(array $rows, string $successMessage = 'Transaction saved successfully.')
    {
        $normalized = $this->normalizeSprocResult($rows);

        if ($normalized['errorCount'] > 0 || trim($normalized['errorMsg']) !== '') {
            return response()->json([
                'success' => false,
                'errorcount' => $normalized['errorCount'] ?: 1,
                'errormsg' => $normalized['errorMsg'],
                'errorCount' => $normalized['errorCount'] ?: 1,
                'errorMsg' => $normalized['errorMsg'],
                'data' => $rows,
            ], 200);
        }

        return response()->json([
            'success' => true,
            'message' => $successMessage,
            'data' => $rows,
        ], 200);
    }

    private function decodeResultJson(array $rows): array
    {
        $normalized = $this->normalizeSprocResult($rows);
        $raw = $normalized['result'];

        if (!is_string($raw) || trim($raw) === '') {
            return [];
        }

        $decoded = json_decode($raw, true);

        return is_array($decoded) ? $decoded : [];
    }

    private function detectUploadMode(array $rows, ?string $requestedMode = null): string
    {
        $requestedMode = trim((string)$requestedMode);

        if (in_array($requestedMode, ['UploadByRow', 'UploadByColumn'], true)) {
            return $requestedMode;
        }

        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }

            foreach (array_keys($row) as $key) {
                $cleanKey = preg_replace('/[\s_\.\/#\-]+/', '', trim((string)$key));

                if (preg_match('/^\d{6}$/', $cleanKey)) {
                    $month = (int)substr($cleanKey, 4, 2);

                    if ($month >= 1 && $month <= 12) {
                        return 'UploadByColumn';
                    }
                }
            }
        }

        return 'UploadByRow';
    }

    public function index(Request $request)
    {
        try {
            $params = $this->wrapJsonData([
                'branchCode' => $request->input('branchCode', ''),
                'startDate'  => $request->input('startDate', '1900-01-01'),
                'endDate'    => $request->input('endDate', '2999-12-31'),
                'userCode'   => $request->input('userCode', ''),
            ]);

            $results = $this->execSproc('Load', $params);

            return response()->json([
                'success' => true,
                'data' => $results,
            ], 200);
        } catch (\Exception $e) {
            Log::error('BUDBB load failed: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }


    
    public function get(Request $request)
    {
        $jsonData = $request->all();
        $jsonString = json_encode($jsonData);

        try {
            $results = DB::select(
                'EXEC sproc_PHP_BUDBB @mode = ?, @params = ?',
                ['Get', $jsonString]
            );

            return response()->json([
                'success' => true,
                'data' => $results,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }


    // public function get(Request $request)
    // {
    //     $request->validate([
    //         'documentNo' => 'nullable|string',
    //         'docNo' => 'nullable|string',
    //         'documentID' => 'nullable|string',
    //         'docId' => 'nullable|string',
    //         'direction' => 'nullable|string',
    //         'key' => 'nullable|string',
    //         'userCode' => 'nullable|string',
    //     ]);

    //     try {
    //         $payload = [
    //             'documentNo' => $request->input('documentNo', $request->input('docNo', '')),
    //             'docNo' => $request->input('docNo', $request->input('documentNo', '')),
    //             'documentID' => $request->input('documentID', $request->input('docId', '')),
    //             'docId' => $request->input('docId', $request->input('documentID', '')),
    //             'direction' => $request->input('direction', $request->input('key', '')),
    //             'userCode' => $request->input('userCode', ''),
    //         ];

    //         $results = $this->execSproc('Get', json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

    //         return response()->json([
    //             'success' => true,
    //             'data' => $results,
    //         ], 200);
    //     } catch (\Exception $e) {
    //         Log::error('BUDBB get failed: ' . $e->getMessage());

    //         return response()->json([
    //             'success' => false,
    //             'message' => $e->getMessage(),
    //         ], 500);
    //     }
    // }

    public function upsert(Request $request)
    {
        $request->validate([
            'json_data' => 'required',
        ]);

        try {
            $payload = $this->getJsonDataPayload($request);
            $params = $this->wrapJsonData($payload);
            $rows = $this->execSproc('Upsert', $params);

            return $this->responseFromSproc($rows, 'Budget Beginning saved successfully.');
        } catch (\Exception $e) {
            Log::error('BUDBB upsert failed: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to save Budget Beginning: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function cancel(Request $request)
    {
        $request->validate([
            'json_data' => 'required',
        ]);

        try {
            $params = $this->wrapJsonData($this->getJsonDataPayload($request));
            $rows = $this->execSproc('Cancel', $params);

            return $this->responseFromSproc($rows, 'Budget Beginning cancelled successfully.');
        } catch (\Exception $e) {
            Log::error('BUDBB cancel failed: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to cancel Budget Beginning: ' . $e->getMessage(),
            ], 500);
        }
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
                'EXEC sproc_PHP_Posting_BUDBB @mode = ?, @params = ?',
                ['Finalize', $params]
            );

            return response()->json([
                'success' => true,
                'data' => $results,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }


    public function history(Request $request)
    {
        try {
            $params = $this->wrapJsonData([
                'branchCode' => $request->input('branchCode', ''),
                'startDate'  => $request->input('startDate', '1900-01-01'),
                'endDate'    => $request->input('endDate', '2999-12-31'),
                'status'     => $request->input('status', 'All'),
                'userCode'   => $request->input('userCode', ''),
            ]);

            $results = $this->execSproc('History', $params);

            return response()->json([
                'success' => true,
                'data' => $results,
            ], 200);
        } catch (\Exception $e) {
            Log::error('BUDBB history failed: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function find(Request $request)
    {
        try {
            $params = $this->wrapJsonData([
                'branchCode' => $request->input('branchCode', ''),
                'userCode'   => $request->input('userCode', ''),
            ]);

            $results = $this->execSproc('Find', $params);

            return response()->json([
                'success' => true,
                'data' => $results,
            ], 200);
        } catch (\Exception $e) {
            Log::error('BUDBB find failed: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function posting(Request $request)
    {
        try {
            $params = $this->wrapJsonData([
                'branchCode' => $request->input('branchCode', ''),
                'userCode'   => $request->input('userCode', ''),
            ]);

            $results = $this->execSproc('Posting', $params);

            return response()->json([
                'success' => true,
                'data' => $results,
            ], 200);
        } catch (\Exception $e) {
            Log::error('BUDBB posting lookup failed: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function uploadExcel(Request $request)
    {
        $request->validate([
            'json_data' => 'required',
        ]);

        try {
            $payload = $this->getJsonDataPayload($request);
            $rowsFromReact = $payload['dt1'] ?? $payload['rows'] ?? [];

            if (!is_array($rowsFromReact) || count($rowsFromReact) === 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'No parsed budget rows received from React.',
                    'uploadMode' => $payload['uploadMode'] ?? '',
                    'rows' => [],
                    'errors' => [],
                    'errorCount' => 1,
                    'errorMsg' => 'No parsed budget rows received from React.',
                ], 200);
            }

            $uploadMode = $this->detectUploadMode($rowsFromReact, $payload['uploadMode'] ?? $payload['mode'] ?? null);

            $params = $this->wrapJsonData([
                'budgetYear' => $payload['budgetYear'] ?? date('Y'),
                'userCode' => $payload['userCode'] ?? '',
                'dt1' => $rowsFromReact,
            ]);

            $sprocRows = $this->execSproc($uploadMode, $params);
            $resultPayload = $this->decodeResultJson($sprocRows);

            $normalizedRows = $resultPayload['rows'] ?? [];
            $errors = $resultPayload['errors'] ?? [];
            $errorCount = (int)($resultPayload['errorCount'] ?? $resultPayload['errorcount'] ?? 0);
            $errorMsg = (string)($resultPayload['errorMsg'] ?? $resultPayload['errormsg'] ?? '');

            return response()->json([
                'success' => $errorCount === 0,
                'message' => $errorCount === 0
                    ? 'Excel rows validated successfully.'
                    : 'Excel rows have validation error(s).',
                'uploadMode' => $uploadMode,
                'rows' => $normalizedRows,
                'errors' => $errors,
                'errorCount' => $errorCount,
                'errorMsg' => $errorMsg,
                'data' => $sprocRows,
            ], 200);
        } catch (\Exception $e) {
            Log::error('BUDBB parsed Excel upload failed: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to validate parsed Excel rows: ' . $e->getMessage(),
                'rows' => [],
                'errors' => [],
                'errorCount' => 1,
                'errorMsg' => $e->getMessage(),
            ], 500);
        }
    }



    public function budOpenBalanceYTD(Request $request)
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
                'EXEC sproc_PHP_BUDBB @mode = ?, @params = ?',
                ['BUDOpenBalanceYTD', $params]
            );

            return response()->json([
                'success' => true,
                'data' => $results,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }


}
