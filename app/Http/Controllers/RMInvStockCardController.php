<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class RMInvStockCardController extends Controller
{
    /**
     * Calls dbo.sproc_PHP_RMStockCard_Inq and returns the same API shape:
     * {
     *   "data": { ... }
     * }
     */
    private function callSproc(Request $request, string $mode)
    {
        try {
            $params = $this->buildSprocParams($request);

            // Parameter order follows the sproc declaration:
            // @params NVARCHAR(MAX), @mode NVARCHAR(MAX)
            $rows = DB::select('EXEC dbo.sproc_PHP_RMStockCard_Inq ?, ?', [
                $params,
                $mode,
            ]);

            $data = [];

            if (!empty($rows)) {
                $firstRow = (array) $rows[0];

                // The sproc returns one row / one column named [data].
                $rawData = $firstRow['data'] ?? reset($firstRow);

                if (is_string($rawData)) {
                    $decoded = json_decode($rawData, true);
                    $data = json_last_error() === JSON_ERROR_NONE ? ($decoded ?? []) : $rawData;
                } else {
                    $data = $rawData ?? [];
                }
            }

            $data = $this->normalizeSprocData($data, $mode);

            return response()->json([
                'data' => $data,
            ]);
        } catch (Throwable $e) {
            Log::error('RMInvStockCardController sproc failed', [
                'mode' => $mode,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'message' => 'Unable to load inventory stock card inquiry.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }


    /**
     * Normalizes balance responses so the React UI can read details/allocated
     * by item code while still accepting the sproc's grouped JSON array shape.
     */
    private function normalizeSprocData($data, string $mode)
    {
        $mode = strtolower($mode);

        if (!is_array($data) || !in_array($mode, ['fifobalance', 'locationbalance'], true)) {
            return $data;
        }

        foreach (['details', 'allocated'] as $key) {
            if (!isset($data[$key]) || !is_array($data[$key])) {
                $data[$key] = [];
                continue;
            }

            // Already keyed by item code.
            if (array_keys($data[$key]) !== range(0, count($data[$key]) - 1)) {
                continue;
            }

            $mapped = [];
            foreach ($data[$key] as $group) {
                if (!is_array($group)) {
                    continue;
                }

                $itemCode = $group['itemCode'] ?? $group['itemNo'] ?? null;
                if ($itemCode === null || $itemCode === '') {
                    continue;
                }

                $mapped[(string) $itemCode] = isset($group['rows']) && is_array($group['rows'])
                    ? $group['rows']
                    : [];
            }

            $data[$key] = $mapped;
        }

        $data['summary'] = isset($data['summary']) && is_array($data['summary']) ? $data['summary'] : [];

        return $data;
    }

    /**
     * Accepts either:
     * - PARAMS={"json_data":{...}} from query string
     * - params={"json_data":{...}}
     * - raw JSON body {"json_data":{...}}
     * - normal request fields like branchCode, itemNo, dateFrom, dateTo, etc.
     */
    private function buildSprocParams(Request $request): string
    {
        $payload = null;

        $paramsInput = $request->input('PARAMS', $request->input('params'));

        if (!empty($paramsInput)) {
            if (is_array($paramsInput)) {
                $payload = $paramsInput;
            } else {
                $decoded = json_decode((string) $paramsInput, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    $payload = $decoded;
                }
            }
        }

        if ($payload === null) {
            $body = $request->all();

            // Avoid passing transport/controller-only fields to the sproc.
            unset($body['PARAMS'], $body['params'], $body['mode']);

            if (isset($body['json_data']) && is_array($body['json_data'])) {
                $payload = [
                    'json_data' => $body['json_data'],
                ];
            } else {
                $payload = [
                    'json_data' => $body,
                ];
            }
        }

        // The sproc expects root json_data.
        if (!isset($payload['json_data']) || !is_array($payload['json_data'])) {
            $payload = [
                'json_data' => $payload,
            ];
        }

        return json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    public function setup(Request $request)
    {
        return $this->callSproc($request, 'setup');
    }

    public function fifoBalance(Request $request)
    {
        return $this->callSproc($request, 'fifoBalance');
    }

    public function locationBalance(Request $request)
    {
        return $this->callSproc($request, 'locationBalance');
    }

    public function stockCard(Request $request)
    {
        return $this->callSproc($request, 'stockCard');
    }

    public function stockStatus(Request $request)
    {
        return $this->callSproc($request, 'stockStatus');
    }
}
