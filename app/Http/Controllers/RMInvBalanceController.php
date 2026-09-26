<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;


class RMInvBalanceController extends Controller
{
  


public function getInvLookup(Request $request)
{
    try {
        $jsonString = $request->input('PARAMS');

        if (empty($jsonString)) {
            $payload = $request->all();

            $jsonString = json_encode([
                'json_data' => [
                    'search' => $payload['search'] ?? null,
                    'filter' => $payload['filter'] ?? 'ActiveAll',
                    'docType' => $payload['docType'] ?? 'PRRM',
                    'searchMode' => $payload['searchMode'] ?? 'part',
                    'branchCode' => $payload['branchCode'] ?? null,
                    'whouseCode' => $payload['whouseCode'] ?? null,
                    'locCode' => $payload['locCode'] ?? null,
                    'tranType' => $payload['tranType'] ?? null,
                ],
            ]);
        }

        if (is_array($jsonString)) {
            $jsonString = json_encode($jsonString);
        }

        Log::info('RM Item Lookup Params', [
            'params' => $jsonString,
        ]);

        $results = DB::select(
            'EXEC sproc_PHP_INVLookup_RM @params = ?',
            [$jsonString]
        );

        return response()->json([
            'success' => true,
            'data' => $results,
        ], 200);

    } catch (\Exception $e) {
        Log::error('RM Item Lookup Error', [
            'message' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ]);

        return response()->json([
            'success' => false,
            'message' => $e->getMessage(),
        ], 500);
    }
}



}
