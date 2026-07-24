<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

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
            'category' => $request->filled('category') ? trim((string) $request->category) : 'All',
        ];

        if ($includeStore) {
            $data['storeCode'] = $request->filled('storeCode')
                ? trim((string) $request->storeCode)
                : 'All';
        }

        return $data;
    }

    private function queryMode(
        Request $request,
        string $mode,
        string $successMessage,
        bool $includeStore = false
    ) {
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
        return response()->json([
            'message' => 'Commissary categories loaded successfully.',
            'data' => $this->execCommissarySproc('CategoryList'),
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

    public function getForecastMaterialNeededSummary(Request $request)
    {
        return $this->queryMode(
            $request,
            'QueryForecastMaterialNeededSummary',
            'Forecast material needed summary loaded successfully.',
            true
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

    public function getConfirmedMaterialNeededSummary(Request $request)
    {
        return $this->queryMode(
            $request,
            'QueryConfirmedMaterialNeededSummary',
            'Confirmed material needed summary loaded successfully.',
            true
        );
    }

    // Backward-compatible routes.
    public function getSummary(Request $request)
    {
        return $this->getConfirmedSummary($request);
    }

    public function getDetailed(Request $request)
    {
        return $this->getConfirmedDetailed($request);
    }

    public function sendConfirmedToSODR(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'startDate'    => 'required|date',
            'endDate'      => 'required|date|after_or_equal:startDate',
            'branchCode'   => 'required|string|max:10',
            'userCode'     => 'required|string|max:50',
            'category'     => 'nullable|string|max:100',
            'storeCode'    => 'nullable|string|max:100',
            'documentDate' => 'nullable|date',
            'whouseCode'   => 'nullable|string|max:10',
            'locCode'      => 'nullable|string|max:10',
            'soTranType'   => 'nullable|string|max:20',
            'drTranType'   => 'nullable|string|max:20',
            'customerCode' => 'required|string|max:50',
            'customerName' => 'nullable|string|max:200',
            'poNumber'     => 'nullable|string|max:100',
            'salesRepCode' => 'nullable|string|max:50',
            'remarks'      => 'nullable|string|max:2000',
            'selectedItems' => 'required|array|min:1',
            'selectedItems.*.storeCode' => 'required|string|max:100',
            'selectedItems.*.itemCode' => 'required|string|max:30',
            'selectedItems.*.deliveryDates' => 'required|array|min:1',
            'selectedItems.*.deliveryDates.*' => 'required|date',
            'selectedItems.*.quantity' => 'nullable|numeric|min:0',
        ]);

        if ($validator->fails()) {
            throw new HttpResponseException(response()->json([
                'message' => 'Invalid parameters for SO/DR integration.',
                'errors' => $validator->errors(),
            ], 422));
        }

        $jsonData = [
            'startDate' => Carbon::parse($request->startDate)->toDateString(),
            'endDate' => Carbon::parse($request->endDate)->toDateString(),
            'branchCode' => trim((string) $request->branchCode),
            'userCode' => trim((string) $request->userCode),
            'category' => $request->filled('category')
                ? trim((string) $request->category)
                : 'All',
            'storeCode' => $request->filled('storeCode')
                ? trim((string) $request->storeCode)
                : 'All',
            'documentDate' => $request->filled('documentDate')
                ? Carbon::parse($request->documentDate)->toDateString()
                : Carbon::now('Asia/Manila')->toDateString(),
            'whouseCode' => trim((string) $request->input('whouseCode', '')),
            'locCode' => trim((string) $request->input('locCode', '')),
            'soTranType' => trim((string) $request->input('soTranType', 'SO01')),
            'drTranType' => trim((string) $request->input('drTranType', 'DR01')),
            'customerCode' => trim((string) $request->customerCode),
            'customerName' => trim((string) $request->input('customerName', '')),
            'poNumber' => trim((string) $request->input('poNumber', '')),
            'salesRepCode' => trim((string) $request->input('salesRepCode', '')),
            'remarks' => trim((string) $request->input('remarks', '')),
            'selectedItems' => collect($request->input('selectedItems', []))
                ->map(function ($item) {
                    return [
                        'storeCode' => trim((string) ($item['storeCode'] ?? '')),
                        'itemCode' => trim((string) ($item['itemCode'] ?? '')),
                        'deliveryDates' => collect($item['deliveryDates'] ?? [])
                            ->map(fn ($date) => Carbon::parse($date)->toDateString())
                            ->unique()
                            ->values()
                            ->all(),
                        'quantity' => (float) ($item['quantity'] ?? 0),
                    ];
                })
                ->values()
                ->all(),
        ];

        $data = $this->execCommissarySproc('SendConfirmedToSODR', $jsonData);

        return response()->json([
            'message' => 'The selected confirmed items were consolidated into one SO and one DR successfully.',
            'documentCount' => count($data),
            'documents' => $data,
            'data' => $data,
        ]);
    }

    public function generateWorkOrders(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'startDate' => 'required|date',
            'endDate'   => 'required|date|after_or_equal:startDate',
        ]);

        if ($validator->fails()) {
            throw new HttpResponseException(response()->json([
                'message' => 'Invalid parameters for Work Order generation.',
                'errors'  => $validator->errors(),
            ], 422));
        }

        $jsonData = [
            'startDate' => Carbon::parse($request->startDate)->toDateString(),
            'endDate'   => Carbon::parse($request->endDate)->toDateString(),
        ];

        $data = $this->execCommissarySproc('GenerateWorkOrders', $jsonData);

        return response()->json([
            'message' => 'Work Orders generated successfully.',
            'data'    => $data,
        ]);
    }





}