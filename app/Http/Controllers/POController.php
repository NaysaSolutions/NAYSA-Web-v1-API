<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use App\Mail\GenericApiMail;

class POController extends Controller
{

    public function index(Request $request)
    {

        try {

            $request->validate([
                'json_data' => 'required|json',
            ]);

            $params = $request->get('json_data');

            $results = DB::select(
                'EXEC sproc_PHP_PO @mode = ?, @params = ?',
                ['get', $params]
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
            // Support both formats:
            // 1. Flat query params: ?poNo=xxx&branchCode=xxx&key=xxx
            // 2. json_data wrapper (array): { json_data: { poNo, branchCode, key } }
            // 3. json_data wrapper (JSON string): { json_data: '{"poNo":...}' }
            $all = $request->all();

            if (isset($all['json_data'])) {
                $inner = $all['json_data'];
                // If it's already an array (sent as object), wrap it for the sproc normalizer
                $jsonString = is_array($inner)
                    ? json_encode(['json_data' => $inner])
                    : json_encode(['json_data' => json_decode($inner, true)]);
            } else {
                // Flat query-string params — pass directly
                $jsonString = json_encode($all);
            }

            $results = DB::select(
                'EXEC sproc_PHP_PO @mode = ?, @params = ?',
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



    public function upsert(Request $request)
    {
        $validated = $request->validate([
            'json_data' => 'required|array'
        ]);

        try {
            $params = json_encode(['json_data' => $validated['json_data']]);
            $mode = 'Upsert';

            // Call the stored procedure
            $result = DB::select('EXEC sproc_PHP_PO @mode = ?, @params = ?', [
                $mode,
                $params
            ]);

            return response()->json([
                'status' => 'success',
                'data' => $result
            ], 200);
        } catch (\Throwable $e) {
            $rawMessage = $e->getMessage();

            $cleanMessage = $rawMessage;

            if (str_contains($cleanMessage, '[SQL Server]')) {
                $cleanMessage = substr($cleanMessage, strrpos($cleanMessage, '[SQL Server]') + strlen('[SQL Server]'));
            }

            $cleanMessage = preg_replace('/\s*\(SQL:.*$/s', '', $cleanMessage);
            $cleanMessage = trim($cleanMessage);

            return response()->json([
                'status' => 'error',
                'message' => $cleanMessage ?: 'Error executing PO Upsert.',
                'details' => $rawMessage
            ], 422);
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
            $result = DB::select('EXEC sproc_PHP_PO @mode = ?, @params = ?', [
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
                'message' => 'Error executing PR Upsert.',
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
            $results = DB::select('EXEC sproc_PHP_PO @mode = ?, @params = ?', [
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
                'message' => 'Error executing PR Upsert.',
                'details' => $e->getMessage()
            ], 500);
    }

}



    public function updatePrFromPo(Request $request)
    {
        try {
            $branchCode = $request->input('branchCode');
            $poId       = $request->input('poId');
            $userCode   = $request->input('userCode');

            if (!$branchCode || !$poId) {
                return response()->json([
                    'success' => false,
                    'message' => 'branchCode and poId are required.'
                ], 400);
            }

            // Build JSON for sproc
            $params = json_encode([
                'json_data' => [
                    'branchCode' => $branchCode,
                    'poId'       => $poId,
                    'userCode'   => $userCode
                ]
            ], JSON_UNESCAPED_UNICODE);

            // Call the UPDATE mode in sproc_PHP_PO
            DB::connection('tenant')->select(
                'EXEC sproc_PHP_PO @mode = ?, @params = ?',
                ['Update', $params]
            );

            return response()->json([
                'success' => true,
                'message' => 'PR quantities and statuses updated successfully.'
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update PR from PO.',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    public function getPOOpen(Request $request)
    {
        Log::info('getPOOpen request', $request->all());

        $mode = $request->input('mode', 'Header');

        $rules = [
            'mode'       => 'required|string|in:Header,Detail',
            'branchCode' => 'nullable|string|max:10',
            'poTranType' => 'nullable|string|max:10',
            'poId'       => 'nullable|string|max:40',
        ];

        if ($mode === 'Detail') {
            $rules['poId'] = 'required|string|max:40';
        }

        $data = $request->validate($rules);

        $mode       = $data['mode'];
        $branchCode = $data['branchCode'] ?? null;
        $poTranType = $data['poTranType'] ?? null;
        $poId       = $data['poId'] ?? null;

        try {
            Log::info('getPOOpen calling sproc', [
                'mode'       => $mode,
                'branchCode' => $branchCode,
                'poTranType' => $poTranType,
                'poId'       => $poId,
            ]);

            $rows = DB::select(
                'EXEC sproc_PHP_PO_Open @mode = ?, @branchCode = ?, @poTranType = ?, @poId = ?',
                [$mode, $branchCode, $poTranType, $poId]
            );

            // Convert stdClass[] to array[]
            $rows = array_map(fn($r) => (array) $r, $rows);

            // Normalize numeric strings to numbers (Detail mode has lots of decimals)
            $numericKeys = [
                'PO_QUANTITY',
                'UOM_QTY2',
                'UNIT_COST',
                'FX_AMOUNT',
                'GROSS_AMOUNT',
                'DISC_RATE',
                'DISC_AMOUNT',
                'NET_AMOUNT',
                'VAT_AMOUNT',
                'ITEM_AMOUNT',
                'RR_QTY',
                'PR_BALANCE',
                'QTY_BALANCE', // if you added this in sproc
            ];

            if ($mode === 'Detail') {
                foreach ($rows as &$row) {
                    foreach ($numericKeys as $k) {
                        if (array_key_exists($k, $row) && $row[$k] !== null && $row[$k] !== '') {
                            // cast to float; FE can format as needed
                            $row[$k] = (float) $row[$k];
                        }
                    }

                    // Make sure GUID stays string (SQL sometimes returns it as a binary-ish object depending on driver)
                    if (isset($row['PO_ID'])) $row['PO_ID'] = (string) $row['PO_ID'];

                    // Optional: Normalize date string fields to YYYY-MM-DD for FE inputs
                    foreach (['PO_DATE', 'DEL_DATE'] as $dk) {
                        if (!empty($row[$dk])) {
                            $row[$dk] = substr((string)$row[$dk], 0, 10);
                        }
                    }
                }
                unset($row);
            } else {
                // Header mode: keep PoId string + shorten dates if present
                foreach ($rows as &$row) {
                    if (isset($row['PoId'])) $row['PoId'] = (string) $row['PoId'];
                    foreach (['PoDate', 'DelDate', 'DateStamp'] as $dk) {
                        if (!empty($row[$dk])) {
                            $row[$dk] = substr((string)$row[$dk], 0, 10);
                        }
                    }
                }
                unset($row);
            }

            return response()->json([
                'success' => true,
                'mode'    => $mode,
                'count'   => count($rows),
                'data'    => $rows,
            ], 200);
        } catch (\Throwable $e) {
            Log::error('getPOOpen failed', [
                'error' => $e->getMessage(),
                'line'  => $e->getLine(),
                'file'  => $e->getFile(),
            ]);

            return response()->json([
                'success' => false,
                'mode'    => $mode,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

public function getPORR_OpenSummary(Request $request) {

   $jsonString = $request->input('PARAMS');

    try {
        $results = DB::select(
            'EXEC sproc_PHP_PO @mode = ?, @params = ?',
            ['getPORR_OpenSummary' ,$jsonString] 
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




public function getPORR_OpenDetail(Request $request) {
    
    $jsonString = $request->input('json_data');

    try {
        $results = DB::select(
            'EXEC sproc_PHP_PO @mode = ?, @params = ?',
            ['getPORR_OpenDetail' ,$jsonString] 
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



public function getPOApproval(Request $request) {

   $jsonString = $request->input('PARAMS');

    try {
        $results = DB::select(
            'EXEC sproc_PHP_PO @mode = ?, @params = ?',
            ['GetApproval' ,$jsonString] 
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




public function approvePO(Request $request)
{
    $validated = $request->validate([
        'json_data' => 'required|array'
    ]);

    try {
        $params = json_encode([
            'json_data' => $validated['json_data']
        ]);

        $mode = 'Approve';

        // Execute stored procedure
        $result = DB::select(
            'EXEC sproc_PHP_PO @mode = ?, @params = ?',
            [$mode, $params]
        );

        // Default rows
        $rows = $result;

        /*
         * Handle JSON string response from SP
         * Example:
         * [
         *   (object)[
         *      'result' => '[{"emailTo":"...","subject":"...","body":"..."}]'
         *   ]
         * ]
         */
        if (isset($result[0]) && is_object($result[0])) {

            $firstRow = (array) $result[0];

            if (count($firstRow) === 1) {

                $firstValue = reset($firstRow);

                if (is_string($firstValue)) {

                    $decoded = json_decode($firstValue);

                    if (
                        json_last_error() === JSON_ERROR_NONE &&
                        is_array($decoded)
                    ) {
                        $rows = $decoded;
                    }
                }
            }
        }

        /*
         * If SP returns:
         * SELECT 'Success' AS result
         * then skip email sending
         */
        if (
            count($rows) === 1 &&
            isset($rows[0]->result) &&
            $rows[0]->result === 'Success'
        ) {
            return response()->json([
                'status' => 'success',
                'message' => 'PO approved successfully.',
                'data' => $rows,
                'mail_summary' => [
                    'sent_count' => 0,
                    'failed_count' => 0,
                    'sent' => [],
                    'failed' => [],
                ]
            ], 200);
        }

        $sentEmails = [];
        $failedEmails = [];

        foreach ($rows as $row) {

            $row = (object) $row;

            $emailTo = $row->emailTo ?? null;
            $subject = $row->subject ?? null;
            $body    = $row->body ?? null;

            // Validate required fields
            if (empty($emailTo) || empty($subject) || empty($body)) {

                $failedEmails[] = [
                    'emailTo' => $emailTo,
                    'reason' => 'Missing emailTo, subject, or body'
                ];

                continue;
            }

            // Validate email format
            if (!filter_var($emailTo, FILTER_VALIDATE_EMAIL)) {

                $failedEmails[] = [
                    'emailTo' => $emailTo,
                    'reason' => 'Invalid email address'
                ];

                continue;
            }

            try {

                // Send email
                Mail::to($emailTo)
                    ->send(new GenericApiMail($subject, $body));

                $sentEmails[] = [
                    'emailTo' => $emailTo,
                    'subject' => $subject
                ];

            } catch (\Throwable $mailException) {

                $failedEmails[] = [
                    'emailTo' => $emailTo,
                    'reason' => $mailException->getMessage()
                ];
            }
        }

        return response()->json([
            'status' => 'success',
            'data' => $rows,
            'mail_summary' => [
                'sent_count' => count($sentEmails),
                'failed_count' => count($failedEmails),
                'sent' => $sentEmails,
                'failed' => $failedEmails,
            ]
        ], 200);

    } catch (\Throwable $e) {

        return response()->json([
            'status' => 'error',
            'message' => 'Error executing PR Approval.',
            'details' => $e->getMessage()
        ], 500);
    }
}

public function getPOAPVSummary(Request $request)
{
    try {
        $payload = [
            'json_data' => [
                'branchCode' => $request->input('branchCode', ''),
                'vendCode'   => $request->input('vendCode', ''),
            ],
        ];

        $params = json_encode($payload);

        $result = DB::connection('tenant')->select(
            "EXEC dbo.sproc_PHP_PO @mode = ?, @params = ?",
            [
                'GetPOAPV_Summary',
                $params,
            ]
        );

        return response()->json($result);
    } catch (\Throwable $e) {
        return response()->json([
            'message' => 'Error fetching PO APV advance reference.',
            'error' => $e->getMessage(),
        ], 500);
    }
}

public function getFGPORR_OpenSummary(Request $request)
{
    try {

        /*
        |--------------------------------------------------------------------------
        | Accept all supported request formats
        |--------------------------------------------------------------------------
        |
        | 1. GET:
        |    ?branchCode=HO&vendCode=PR000001
        |
        | 2. PARAMS:
        |    {"branchCode":"HO","vendCode":"PR000001"}
        |
        | 3. PARAMS:
        |    {"json_data":{"branchCode":"HO","vendCode":"PR000001"}}
        |
        */

        $rawParams = $request->input('PARAMS');

        $decodedParams = [];

        if (is_string($rawParams) && trim($rawParams) !== '') {
            $decoded = json_decode($rawParams, true);

            if (
                json_last_error() === JSON_ERROR_NONE &&
                is_array($decoded)
            ) {
                $decodedParams = $decoded;
            }
        } elseif (is_array($rawParams)) {
            $decodedParams = $rawParams;
        }


        /* =========================================================
           GET BRANCH CODE
        ========================================================= */
        $branchCode =
            $request->query('branchCode')
            ?? $request->input('branchCode')
            ?? data_get($decodedParams, 'json_data.branchCode')
            ?? data_get($decodedParams, 'branchCode')
            ?? '';


        /* =========================================================
           GET VENDOR CODE
        ========================================================= */
        $vendCode =
            $request->query('vendCode')
            ?? $request->input('vendCode')
            ?? data_get($decodedParams, 'json_data.vendCode')
            ?? data_get($decodedParams, 'vendCode')
            ?? '';


        /* =========================================================
           NORMALIZED STORED PROCEDURE PARAMETERS
        ========================================================= */
        $params = json_encode([
            'json_data' => [
                'branchCode' => trim((string) $branchCode),
                'vendCode'   => trim((string) $vendCode),
            ]
        ], JSON_UNESCAPED_UNICODE);


        Log::info('getFGPORR_OpenSummary', [
            'branchCode' => $branchCode,
            'vendCode'   => $vendCode,
            'params'     => $params,
        ]);


        /* =========================================================
           EXECUTE PO SPROC
        ========================================================= */
        $results = DB::connection('tenant')->select(
            'EXEC dbo.sproc_PHP_PO @mode = ?, @params = ?',
            [
                'GetFGPORR_OpenSummary',
                $params
            ]
        );


        return response()->json([
            'success' => true,
            'data'    => $results,
        ], 200);


    } catch (\Throwable $e) {

        Log::error('getFGPORR_OpenSummary failed', [
            'message' => $e->getMessage(),
            'line'    => $e->getLine(),
            'file'    => $e->getFile(),
            'request' => $request->all(),
        ]);


        return response()->json([
            'success' => false,
            'message' => 'Failed to load open FG Purchase Orders.',
            'error'   => $e->getMessage(),
        ], 500);
    }
}

public function getFGPORR_OpenDetail(Request $request)
{
    $jsonString = $request->input('json_data');

    try {

        $results = DB::connection('tenant')->select(
            'EXEC dbo.sproc_PHP_PO @mode = ?, @params = ?',
            [
                'GetFGPORR_OpenDetail',
                $jsonString
            ]
        );

        return response()->json([
            'success' => true,
            'data' => $results,
        ], 200);

    } catch (\Throwable $e) {

        Log::error('getFGPORR_OpenDetail failed', [
            'params' => $jsonString,
            'error' => $e->getMessage(),
        ]);

        return response()->json([
            'success' => false,
            'message' => $e->getMessage(),
        ], 500);
    }
}


public function getRMPORR_OpenSummary(Request $request)
{
    $jsonString = $request->input('PARAMS');

    try {
        $results = DB::connection('tenant')->select(
            'EXEC dbo.sproc_PHP_PO @mode = ?, @params = ?',
            ['GetRMPORR_OpenSummary', $jsonString]
        );

        return response()->json([
            'success' => true,
            'data' => $results,
        ], 200);
    } catch (\Throwable $e) {
        Log::error('getRMPORR_OpenSummary failed', [
            'params' => $jsonString,
            'error' => $e->getMessage(),
        ]);

        return response()->json([
            'success' => false,
            'message' => $e->getMessage(),
        ], 500);
    }
}


public function getRMPORR_OpenDetail(Request $request)
{
    $jsonString = $request->input('json_data');

    try {
        $results = DB::connection('tenant')->select(
            'EXEC dbo.sproc_PHP_PO @mode = ?, @params = ?',
            ['GetRMPORR_OpenDetail', $jsonString]
        );

        return response()->json([
            'success' => true,
            'data' => $results,
        ], 200);
    } catch (\Throwable $e) {
        Log::error('getRMPORR_OpenDetail failed', [
            'params' => $jsonString,
            'error' => $e->getMessage(),
        ]);

        return response()->json([
            'success' => false,
            'message' => $e->getMessage(),
        ], 500);
    }
}


public function getVEPORR_OpenSummary(Request $request)
{
    $jsonString = $request->input('PARAMS');

    try {
        $results = DB::connection('tenant')->select(
            'EXEC dbo.sproc_PHP_PO @mode = ?, @params = ?',
            ['GetVEPORR_OpenSummary', $jsonString]
        );

        return response()->json([
            'success' => true,
            'data' => $results,
        ], 200);
    } catch (\Throwable $e) {
        Log::error('getVEPORR_OpenSummary failed', [
            'params' => $jsonString,
            'error' => $e->getMessage(),
        ]);

        return response()->json([
            'success' => false,
            'message' => $e->getMessage(),
        ], 500);
    }
}


public function getVEPORR_OpenDetail(Request $request)
{
    $jsonString = $request->input('json_data');

    try {
        $results = DB::connection('tenant')->select(
            'EXEC dbo.sproc_PHP_PO @mode = ?, @params = ?',
            ['GetVEPORR_OpenDetail', $jsonString]
        );

        return response()->json([
            'success' => true,
            'data' => $results,
        ], 200);
    } catch (\Throwable $e) {
        Log::error('getVEPORR_OpenDetail failed', [
            'params' => $jsonString,
            'error' => $e->getMessage(),
        ]);

        return response()->json([
            'success' => false,
            'message' => $e->getMessage(),
        ], 500);
    }
}


}






