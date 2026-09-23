<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class APVoucherController extends Controller
{
       
public function index(Request $request) {

    try {

        $request->validate([
            'json_data' => 'required|json',
        ]);

        $params = $request->get('json_data');
      
        $results = DB::select(
            'EXEC sproc_PHP_APV @mode = ?, @params = ?',
            ['get' ,$params] 
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

public function get(Request $request)
{
    try {

        /*
        |--------------------------------------------------------------------------
        | IMPORTANT
        |--------------------------------------------------------------------------
        | sproc_PHP_APV already wraps Get payload with:
        |
        | {"json_data": ... }
        |
        | So DO NOT wrap json_data here again.
        */

        $params = json_encode([
            'branchCode' => $request->query('branchCode', ''),
            'apvNo'      => $request->query('apvNo', ''),
            'direction'  => $request->query('direction', ''),
        ], JSON_UNESCAPED_UNICODE);

        Log::info('GET APV Params', [
            'branchCode' => $request->query('branchCode', ''),
            'apvNo'      => $request->query('apvNo', ''),
            'direction'  => $request->query('direction', ''),
            'params'     => $params,
        ]);

        $results = DB::select(
            'EXEC sproc_PHP_APV @mode = ?, @params = ?',
            [
                'Get',
                $params
            ]
        );

        Log::info('GET APV Result', [
            'results' => $results
        ]);

        if (empty($results)) {
            return response()->json([
                'success' => true,
                'data' => [
                    ['result' => null]
                ],
            ], 200);
        }

        return response()->json([
            'success' => true,
            'data' => $results,
        ], 200);

    } catch (\Throwable $e) {

        Log::error('GET APV Error', [
            'message' => $e->getMessage(),
            'trace'   => $e->getTraceAsString(),
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Failed to retrieve APV transaction.',
            'details' => $e->getMessage(),
        ], 500);
    }
}



public function upsert(Request $request)
    {
        $validated = $request->validate([
            'json_data' => 'required|array'
        ]);

        try {
            $params = json_encode(['json_data' => $validated['json_data']]);
            $mode = 'Upsert';

            // Call the stored procedure
            $result = DB::select('EXEC sproc_PHP_APV @mode = ?, @params = ?', [
                $mode,
                $params
            ]);

            return response()->json([
                'status' => 'success',
                'data' => $result
            ], 200);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error executing APV Upsert.',
                'details' => $e->getMessage()
            ], 500);
        }
    }

public function generateGL(Request $request)
    {
        try {
            // Expecting json_data to be passed from client
            $jsonData = $request->input('json_data');

            if (!$jsonData) {
                return response()->json(['error' => 'Missing json_data'], 400);
            }

            // Convert JSON to string format
            $jsonString = json_encode(['json_data' => $jsonData], JSON_UNESCAPED_UNICODE);

            // Execute the stored procedure
            $results = DB::select("EXEC sproc_PHP_APV @mode = ?, @params = ?", [
                'GenerateEntries',
                $jsonString
            ]);

            return response()->json([
                'status' => 'success',
                'data' => $results
            ]);

        } catch (\Exception $e) {
            Log::error('Error executing sproc_PHP_APV: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to generate entries.',
                'details' => $e->getMessage()
            ], 500);
        }
    }


public function load(Request $request)
{
    $jsonData = $request->all();
    $jsonString = json_encode($jsonData);

    try {
        $results = DB::select(
            'EXEC sproc_PHP_APV @mode = ?, @params = ?',
            ['Load', $jsonString]
        );

        $json = $results[0]->result ?? '[]';

        return response()->json([
            'success' => true,
            'data' => json_decode($json, true),
        ], 200);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => $e->getMessage(),
        ], 500);
    }
}

public function PostTransaction(Request $request)
{
    try {
        $validated = $request->validate(['json_data' => 'required|array']);
        $params = json_encode(['json_data' => $validated['json_data']], JSON_UNESCAPED_UNICODE);
        $result = DB::select('EXEC sproc_PHP_APV @mode = ?, @params = ?', ['Post', $params]);

        $message = $result[0]->result ?? 'No result returned from stored procedure';

        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $result
        ]);

    } catch (\Exception $e) {
        Log::error('PostTransaction Error: ' . $e->getMessage());
        return response()->json([
            'success' => false,
            'message' => 'Failed to post transaction',
            'error' => $e->getMessage()
        ], 500);
    }
}

public function cancel(Request $request)
{
        $validated = $request->validate([
            'json_data' => 'required|array'
        ]);

        try {
            $params = json_encode(['json_data' => $validated['json_data']]);
            $mode = 'Cancel';

            // Call the stored procedure
            $result = DB::select('EXEC sproc_PHP_APV @mode = ?, @params = ?', [
                $mode,
                $params
            ]);

            return response()->json([
                'status' => 'success',
                'data' => $result
            ], 200);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error executing SVI Upsert.',
                'details' => $e->getMessage()
            ], 500);
        }
}




public function history(Request $request) {

        $validated = $request->validate([
            'json_data' => 'required|array'
        ]);

        try {
            $params = json_encode(['json_data' => $validated['json_data']]);
            $mode = 'History';

            // Call the stored procedure
            $results = DB::select('EXEC sproc_PHP_APV @mode = ?, @params = ?', [
                $mode,
                $params
            ]);
       
         return response()->json([
                'status' => 'success',
                'data' => $results
            ], 200);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error executing APV Upsert.',
                'details' => $e->getMessage()
            ], 500);
    }

}

public function posting(Request $request)
{
    try {
        $results = DB::select(
            'EXEC sproc_PHP_APV @mode = ?',
            ['Posting']
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

public function finalize(Request $request)
{
    try {
        $validated = $request->validate([
            'json_data' => 'required|array'
        ]);

        $params = json_encode(['json_data' => $validated['json_data']]);

        $results = DB::select(
            'EXEC sproc_PHP_Posting_APV @mode = ?, @params = ?',
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

    public function getAPVRR_OpenSummary(Request $request) {

   $jsonString = $request->input('PARAMS');

    try {
        $payload = json_decode($jsonString, true) ?: [];
        $source = $payload['json_data']['source'] ?? $payload['source'] ?? '';

        if (strtoupper((string) $source) === 'PCV') {
            $rows = [];

            foreach (['sproc_PHP_RMRR', 'sproc_PHP_FGRR', 'sproc_PHP_MSRR'] as $procedure) {
                $result = DB::select(
                    "EXEC {$procedure} @mode = ?, @params = ?",
                    ['getAPVRR_OpenSummary', $jsonString]
                );

                $procedureRows = json_decode($result[0]->result ?? '[]', true);
                if (is_array($procedureRows)) {
                    $rows = array_merge($rows, $procedureRows);
                }
            }

            $results = [(object) ['result' => json_encode($rows)]];
        } else {
            $results = DB::select(
                'EXEC sproc_PHP_MSRR @mode = ?, @params = ?',
                ['getAPVRR_OpenSummary', $jsonString]
            );
        }

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

    public function getAPVReferenceSummary(Request $request)
    {
        try {
            $validated = $request->validate(['json_data' => 'required|array']);
            $params = json_encode(['json_data' => $validated['json_data']], JSON_UNESCAPED_UNICODE);

            $results = DB::select(
                'EXEC sproc_PHP_APV @mode = ?, @params = ?',
                ['GetAPVReferenceSummary', $params]
            );

            return response()->json([
                'success' => true,
                'data' => $results,
            ], 200);
        } catch (\Throwable $e) {
            Log::error('APV Reference Summary Error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch APV reference summary.',
                'details' => $e->getMessage(),
            ], 500);
        }
    }




private function splitReferenceIds($value): array
{
    if (is_array($value)) {
        $values = $value;
    } else {
        $values = explode(',', (string) ($value ?? ''));
    }

    return array_values(array_unique(array_filter(array_map(
        fn ($item) => trim((string) $item),
        $values
    ))));
}

private function getFGRRAPVOpenDetailRows(array $inputData): array
{
    $selectedIds = [];

    foreach (['selectedIds', 'selectedId', 'rrId', 'rrHdId', 'groupId', 'id'] as $key) {
        $selectedIds = array_merge($selectedIds, $this->splitReferenceIds($inputData[$key] ?? ''));
    }

    $selectedIds = array_values(array_unique(array_filter($selectedIds)));
    $branchCode = trim((string) ($inputData['branchCode'] ?? $inputData['BRANCH_CODE'] ?? ''));
    $vendCode = trim((string) ($inputData['vendCode'] ?? $inputData['VEND_CODE'] ?? ''));
    $rrNo = trim((string) ($inputData['rrNo'] ?? $inputData['RR_NO'] ?? ''));
    $poNo = trim((string) ($inputData['poNo'] ?? $inputData['PO_NO'] ?? ''));

    $query = DB::table('fgrr_hd as a')
        ->join('fgrr_dt1 as b', 'b.rr_id', '=', 'a.rr_id')
        ->leftJoin('fg_mast as c', 'c.item_code', '=', 'b.item_code')
        ->leftJoin('fg_categ as d', function ($join) {
            $join->on('d.categ_code', '=', DB::raw("COALESCE(NULLIF(b.categ_code, ''), c.categ_code)"));
        })
        ->leftJoin('vat_ref as v', 'v.vat_code', '=', 'b.vat_code')
        ->whereRaw("ISNULL(a.rr_cancelled, 'N') <> 'Y'")
        ->selectRaw("
            'FG' AS [type],
            'FG' AS invType,
            'FG' AS rrSource,
            a.rr_id AS rrId,
            a.rr_id AS groupId,
            a.branch_code AS branchCode,
            a.rr_no AS rrNo,
            CONVERT(varchar, a.rr_date, 101) AS rrDate,
            a.po_no AS poNo,
            a.vend_code AS vendCode,
            a.vend_name AS vendName,
            a.si_no AS siNo,
            CONVERT(varchar, a.si_date, 101) AS siDate,
            b.line_no AS lnNo,
            b.item_code AS itemCode,
            ISNULL(c.item_name, '') AS itemName,
            COALESCE(NULLIF(b.categ_code, ''), c.categ_code, '') AS categCode,
            b.uom_code AS uomCode,
            b.quantity AS quantity,
            b.unit_cost AS unitCost,
            b.unit_costfx AS unitCostFx,
            b.item_amount AS itemAmount,
            b.item_amount AS siAmount,
            b.item_amount AS amount,
            b.curr_code AS currCode,
            b.curr_rate AS currRate,
            b.fx_amount AS fxAmount,
            b.net_amount AS netAmount,
            b.vat_code AS vatCode,
            v.vat_name AS vatDesc,
            b.vat_amount AS vatAmount,
            b.rc_code AS rcCode,
            d.expacct_code AS drAcct,
            a.remarks AS remarks
        ");

    if ($branchCode !== '') {
        $query->where('a.branch_code', $branchCode);
    }

    if ($vendCode !== '') {
        $query->where('a.vend_code', $vendCode);
    }

    if (!empty($selectedIds)) {
        $query->whereIn('a.rr_id', $selectedIds);
    } elseif ($rrNo !== '') {
        $query->where('a.rr_no', $rrNo);
    } elseif ($poNo !== '') {
        $query->where('a.po_no', $poNo);
    }

    return $query
        ->orderBy('b.line_no')
        ->get()
        ->all();
}
private function extractStoredProcedureResultRows(array $results): array
{
    $rows = [];

    foreach ($results as $resultRow) {
        $row = is_object($resultRow) ? (array) $resultRow : (array) $resultRow;

        $raw = $row['result']
            ?? $row['RESULT']
            ?? $row['JsonResult']
            ?? null;

        if (is_string($raw) && trim($raw) !== '') {
            $decoded = json_decode($raw, true);

            if (json_last_error() === JSON_ERROR_NONE) {
                if (is_array($decoded)) {
                    $isList = $decoded === [] || array_keys($decoded) === range(0, count($decoded) - 1);
                    $rows = array_merge($rows, $isList ? $decoded : [$decoded]);
                }

                continue;
            }
        }

        if (!empty($row) && !array_key_exists('result', $row) && !array_key_exists('RESULT', $row)) {
            $rows[] = $row;
        }
    }

    return $rows;
}

public function getAPVRR_OpenDetail(Request $request)
{
    try {
        $inputData = $request->input('json_data', []);

        if (!is_array($inputData)) {
            return response()->json([
                'success' => false,
                'message' => 'json_data must be an object.',
            ], 422);
        }

        $params = json_encode([
            'json_data' => $inputData,
        ], JSON_UNESCAPED_UNICODE);

        $results = DB::select(
            'EXEC dbo.sproc_PHP_APV @mode = ?, @params = ?',
            ['getPORR_OpenDetail', $params]
        );

        $rows = $this->extractStoredProcedureResultRows($results);

        $referenceType = strtoupper(trim((string) (
            $inputData['type']
            ?? $inputData['invType']
            ?? $inputData['rrSource']
            ?? ''
        )));

        /*
        |--------------------------------------------------------------------------
        | FG FALLBACK
        |--------------------------------------------------------------------------
        */
        if (empty($rows) && in_array($referenceType, ['FG', 'FGRR'], true)) {
            $rows = array_map(
                fn ($row) => (array) $row,
                $this->getFGRRAPVOpenDetailRows($inputData)
            );
        }

        /*
        |--------------------------------------------------------------------------
        | PRESERVE RR TYPE
        |--------------------------------------------------------------------------
        */
        if ($referenceType !== '') {
            $rows = array_map(function ($row) use ($referenceType) {
                $data = (array) $row;

                $data['type'] = $data['type'] ?? $referenceType;
                $data['invType'] = $data['invType'] ?? $referenceType;
                $data['rrSource'] = $data['rrSource'] ?? $referenceType;
                $data['referenceSource'] = $data['referenceSource'] ?? 'RR';

                return $data;
            }, $rows);
        }

        return response()->json([
            'success' => true,
            'data' => $rows,
        ], 200);

    } catch (\Throwable $e) {
        Log::error('APV RR Open Detail Error', [
            'message' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Failed to fetch APV RR detail.',
            'details' => $e->getMessage(),
        ], 500);
    }
}

public function getAPVPO_OpenDetail(Request $request)
{
    try {
        $params = json_encode($request->all());

        $result = DB::select(
            'EXEC sproc_PHP_APV @mode = ?, @params = ?',
            [
                'getAPVPO_OpenDetail',
                $params,
            ]
        );

        return response()->json([
            'success' => true,
            'data' => $result,
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Failed to fetch PO reference details.',
            'error' => $e->getMessage(),
        ], 500);
    }
}

public function getAPVLC_OpenDetail(Request $request) {
    try {
        $validated = $request->validate(['json_data' => 'required|array']);
        $params = json_encode(['json_data' => $validated['json_data']], JSON_UNESCAPED_UNICODE);

        $results = DB::select(
            'EXEC sproc_PHP_APV @mode = ?, @params = ?',
            ['OpenAPVLC_OpenDetail', $params]
        );

        return response()->json([
            'success' => true,
            'data' => $results,
        ], 200);
    } catch (\Exception $e) {
        return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
    }
}
}

