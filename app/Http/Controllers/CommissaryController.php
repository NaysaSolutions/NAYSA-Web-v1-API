<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Carbon\Carbon;

class CommissaryController extends Controller
{
    private function execCommissarySproc(string $mode, array $jsonData = []): array
    {
        $params = json_encode([
            'json_data' => $jsonData,
        ]);

        $rows = DB::connection('tenant')->select(
            'EXEC dbo.sproc_PHP_CommissaryForecast @mode = ?, @params = ?',
            [$mode, $params]
        );

        if (count($rows) === 0) {
            return [];
        }

        if (!isset($rows[0]->result)) {
            return json_decode(json_encode($rows), true) ?: [];
        }

        $decoded = json_decode($rows[0]->result, true);

        if ($decoded === null && $rows[0]->result !== null && $rows[0]->result !== '') {
            throw new HttpResponseException(response()->json([
                'message' => 'Invalid JSON returned by Commissary Forecast procedure.',
                'raw' => $rows[0]->result,
            ], 422));
        }

        $sprocError = null;

        if (is_array($decoded)) {
            if (isset($decoded['errorNumber'])) {
                $sprocError = $decoded;
            } elseif (isset($decoded[0]) && is_array($decoded[0]) && isset($decoded[0]['errorNumber'])) {
                $sprocError = $decoded[0];
            }
        }

        if ($sprocError) {
            throw new HttpResponseException(response()->json([
                'message' => $sprocError['errorMessage'] ?? 'Commissary Forecast procedure error.',
                'errors' => $sprocError,
            ], 422));
        }

        return $decoded ?: [];
    }

    private function getValidatedQuery(Request $request, bool $includeStore = false): array
    {
        $rules = [
            'startDate' => 'required|date',
            'endDate' => 'required|date|after_or_equal:startDate',
            'category' => 'nullable|string',
        ];

        if ($includeStore) {
            $rules['storeCode'] = 'nullable|string';
        }

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            throw new HttpResponseException(response()->json([
                'message' => 'Invalid commissary query parameters.',
                'errors' => $validator->errors(),
            ], 422));
        }

        $data = [
            'startDate' => Carbon::parse($request->startDate)->toDateString(),
            'endDate' => Carbon::parse($request->endDate)->toDateString(),
            'category' => $request->filled('category') ? $request->category : 'All',
        ];

        if ($includeStore) {
            $data['storeCode'] = $request->filled('storeCode') ? $request->storeCode : 'All';
        }

        return $data;
    }

    private function queryMode(Request $request, string $mode, string $successMessage, bool $includeStore = false)
    {
        $data = $this->execCommissarySproc(
            $mode,
            $this->getValidatedQuery($request, $includeStore)
        );

        return response()->json([
            'message' => $successMessage,
            'data' => $data,
        ]);
    }

    public function getCategories(Request $request)
    {
        $data = $this->execCommissarySproc('CategoryList');

        return response()->json([
            'message' => 'Commissary categories loaded successfully.',
            'data' => $data,
        ]);
    }

    public function getForecastSummary(Request $request)
    {
        return $this->queryMode(
            $request,
            'QueryForecastSummary',
            'Forecast summary loaded successfully.'
        );
    }

    public function getForecastDetailed(Request $request)
    {
        return $this->queryMode(
            $request,
            'QueryForecastDetailed',
            'Forecast detail loaded successfully.',
            true
        );
    }

    public function getForecastMaterialNeeded(Request $request)
    {
        return $this->queryMode(
            $request,
            'QueryForecastMaterialNeeded',
            'Forecast material needed loaded successfully.'
        );
    }

    public function getConfirmedSummary(Request $request)
    {
        return $this->queryMode(
            $request,
            'QueryConfirmedSummary',
            'Confirmed summary loaded successfully.'
        );
    }

    public function getConfirmedDetailed(Request $request)
    {
        return $this->queryMode(
            $request,
            'QueryConfirmedDetailed',
            'Confirmed detail loaded successfully.',
            true
        );
    }

    public function getConfirmedMaterialNeeded(Request $request)
    {
        return $this->queryMode(
            $request,
            'QueryConfirmedMaterialNeeded',
            'Confirmed material needed loaded successfully.'
        );
    }

    /**
     * Backward-compatible endpoints.
     * Existing /commissary/summary and /commissary/detailed now show confirmed qty only.
     */
    public function getSummary(Request $request)
    {
        return $this->getConfirmedSummary($request);
    }

    public function getDetailed(Request $request)
    {
        return $this->getConfirmedDetailed($request);
    }
}
