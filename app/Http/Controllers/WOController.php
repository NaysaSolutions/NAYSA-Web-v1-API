<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Throwable;

class WOController extends Controller
{
    public function index()
    {
        return view('app');
    }

    /**
     * sproc_PHP_WO signature:
     *   EXEC dbo.sproc_PHP_WO @mode, @params
     *
     * Important:
     * - For Get, the sproc itself wraps @params into { json_data: ... }.
     * - For other modes, this controller sends { json_data: payload }.
     */
    private function runWO(string $mode, array $payload = [], bool $wrapJsonData = true): array
    {
        $params = $wrapJsonData
            ? json_encode(['json_data' => $payload], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
            : json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        $rows = DB::select(
            'EXEC dbo.sproc_PHP_WO @mode = ?, @params = ?',
            [$mode, $params]
        );

        return $this->normalizeResult($mode, $rows);
    }

    private function normalizeResult(string $mode, array $rows): array
    {
        if (count($rows) === 0) {
            return ['success' => false, 'message' => 'No response from stored procedure.'];
        }

        $first = (array) $rows[0];

        if (isset($first['errorCount']) && (int) $first['errorCount'] > 0) {
            return [
                'success' => false,
                'message' => (string) ($first['errorMsg'] ?? 'Transaction failed.'),
                'errorCount' => (int) $first['errorCount'],
            ];
        }

        // Upsert returns direct columns: woNo, woId.
        if (strcasecmp($mode, 'Upsert') === 0 && isset($first['woNo'])) {
            return [
                'success' => true,
                'message' => 'Work Order saved successfully.',
                'woNo' => $first['woNo'] ?? '',
                'woId' => $first['woId'] ?? '',
            ];
        }

        // Post / Cancel return direct result = Success.
        if (in_array($mode, ['Post', 'Cancel'], true)) {
            $direct = (string) ($first['result'] ?? '');
            if (strcasecmp($direct, 'Success') === 0) {
                return [
                    'success' => true,
                    'message' => $mode === 'Post'
                        ? 'Work Order posted successfully.'
                        : 'Work Order cancelled successfully.',
                ];
            }
        }

        $raw = $first['result'] ?? null;

        if ($raw === null || $raw === '') {
            return ['success' => true, 'rows' => $rows];
        }

        $decoded = json_decode((string) $raw, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            if (strcasecmp((string) $raw, 'Success') === 0) {
                return ['success' => true, 'message' => 'Success'];
            }

            return [
                'success' => false,
                'message' => 'Stored procedure returned invalid JSON.',
                'raw' => $raw,
            ];
        }

        if (strcasecmp($mode, 'Get') === 0) {
            // sproc returns { result: null } when not found, otherwise the header object with dt1.
            if (isset($decoded['result']) && $decoded['result'] === null) {
                return ['success' => false, 'message' => 'Work Order was not found.'];
            }

            return [
                'success' => true,
                'hd' => $decoded,
                'dt1' => $decoded['dt1'] ?? [],
                'dtApp' => $decoded['dtApp'] ?? [],
            ];
        }

        if (in_array($mode, ['BuildFromBOM', 'LoadBOM'], true)) {
            return [
                'success' => true,
                'hd' => $decoded,
                'dt1' => $decoded['dt1'] ?? [],
            ];
        }

        if (strcasecmp($mode, 'History') === 0) {
            $history = $decoded[0] ?? [];
            return [
                'success' => true,
                'summary' => $history['WO_Summary'] ?? [],
                'detail' => $history['WO_Detail'] ?? [],
                'raw' => $decoded,
            ];
        }

        // Load, Find, FindBOM return JSON arrays.
        if (is_array($decoded) && array_is_list($decoded)) {
            return ['success' => true, 'rows' => $decoded];
        }

        return ['success' => true, 'data' => $decoded];
    }

    private function payload(Request $request): array
    {
        $data = $request->input('json_data', $request->all());
        return is_array($data) ? $data : [];
    }

    private function withUser(Request $request, array $payload): array
    {
        $payload['userCode'] = $payload['userCode']
            ?? $payload['userId']
            ?? optional($request->user())->user_code
            ?? optional($request->user())->name
            ?? '';

        $payload['userId'] = $payload['userId'] ?? $payload['userCode'];

        return $payload;
    }

    public function get(Request $request): JsonResponse
    {
        try {
            // Get mode expects raw JSON object. The sproc wraps it into { json_data: ... }.
            return response()->json($this->runWO('Get', $this->payload($request), false));
        } catch (Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function load(Request $request): JsonResponse
    {
        try {
            return response()->json($this->runWO('Load', $this->payload($request)));
        } catch (Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function history(Request $request): JsonResponse
    {
        try {
            return response()->json($this->runWO('History', $this->payload($request)));
        } catch (Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function find(Request $request): JsonResponse
    {
        try {
            return response()->json($this->runWO('Find', $this->payload($request)));
        } catch (Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function findBOM(Request $request): JsonResponse
    {
        try {
            return response()->json($this->runWO('FindBOM', $this->payload($request)));
        } catch (Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function loadBOM(Request $request): JsonResponse
    {
        try {
            return response()->json($this->runWO('BuildFromBOM', $this->withUser($request, $this->payload($request))));
        } catch (Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function upsert(Request $request): JsonResponse
    {
        try {
            return response()->json($this->runWO('Upsert', $this->withUser($request, $this->payload($request))));
        } catch (Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function posting(Request $request): JsonResponse
    {
        try {
            return response()->json($this->runWO('Post', $this->withUser($request, $this->payload($request))));
        } catch (Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function cancel(Request $request): JsonResponse
    {
        try {
            return response()->json($this->runWO('Cancel', $this->withUser($request, $this->payload($request))));
        } catch (Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}
