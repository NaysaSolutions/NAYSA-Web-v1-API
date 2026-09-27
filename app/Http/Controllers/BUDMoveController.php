<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BUDMoveController extends Controller
{
    private string $sproc = 'sproc_PHP_BUDMove';

    private function executeMode(Request $request, string $mode)
    {
        try {
            $jsonData = $request->input('json_data', []);

            if (!is_array($jsonData)) {
                $jsonData = [];
            }

            $params = json_encode(
                ['json_data' => $jsonData],
                JSON_UNESCAPED_UNICODE
            );

            $results = DB::select(
                "exec dbo.{$this->sproc} @mode = ?, @params = ?",
                [$mode, $params]
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

    public function budOpenBalanceYTD(Request $request)
    {
        return $this->executeMode($request, 'BUDOpenBalanceYTD');
    }

    public function budTop1OpenTempBalance(Request $request)
    {
        return $this->executeMode($request, 'BUDTop1OpenTempBalance');
    }

    public function budgetQuery(Request $request)
    {
        return $this->executeMode($request, 'BUDQuerySummary');
    }

    public function budgetMonthlyComparative(Request $request)
    {
        return $this->executeMode($request, 'BUDMonthlyComparative');
    }

    public function budgetIncomeStatementYTD(Request $request)
    {
        return $this->executeMode($request, 'BUDIncomeStatementYTD');
    }

    public function budgetGLAccountComparativeYTD(Request $request)
    {
        return $this->executeMode($request, 'BUDGLAccountComparativeYTD');
    }
}
