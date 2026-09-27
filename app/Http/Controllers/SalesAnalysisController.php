<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Throwable;

class SalesAnalysisController extends Controller
{
    public function dashboard(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'branchCode' => ['nullable', 'string', 'max:40'],
            'custCode' => ['nullable', 'string', 'max:40'],
            'areaCode' => ['nullable', 'string', 'max:40'],
            'salesRepCode' => ['nullable', 'string', 'max:40'],
            'itemCode' => ['nullable', 'string', 'max:80'],
            'categoryCode' => ['nullable', 'string', 'max:80'],
            'startDate' => ['nullable', 'date'],
            'endDate' => ['nullable', 'date', 'after_or_equal:startDate'],
            'groupBy' => ['nullable', 'in:DAY,WEEK,MONTH,QUARTER,YEAR'],
            'topN' => ['nullable', 'integer', 'min:5', 'max:50'],
        ]);

        $params = json_encode(
            ['json_data' => array_merge([
                'branchCode' => '',
                'custCode' => '',
                'areaCode' => '',
                'salesRepCode' => '',
                'itemCode' => '',
                'categoryCode' => '',
                'groupBy' => 'MONTH',
                'topN' => 10,
            ], $validated)],
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        );

        try {
            $rows = DB::connection('tenant')->select(
                'EXEC dbo.sproc_PHP_Sales_Analysis @mode = ?, @params = ?',
                ['Dashboard', $params]
            );

            $raw = $rows[0]->result ?? '{}';
            $data = is_string($raw) ? json_decode($raw, true) : $raw;

            if (!is_array($data)) {
                return response()->json([
                    'message' => 'The Sales Analysis procedure returned invalid JSON.',
                    'data' => null,
                ], 500);
            }

            return response()->json($data);
        } catch (Throwable $exception) {
            report($exception);

            return response()->json([
                'message' => 'Failed to load Sales Analysis.',
                'error' => config('app.debug') ? $exception->getMessage() : null,
            ], 500);
        }
    }
}
