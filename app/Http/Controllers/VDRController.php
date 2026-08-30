<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class VDRController extends Controller
{
    public function index(Request $request)
    {
        try {
            $request->validate([
                'json_data' => 'required|json',
            ]);

            $params = $request->get('json_data');
            $results = DB::select(
                'EXEC sproc_PHP_VDR @mode = ?, @params = ?',
                ['Get', $params]
            );

            return response()->json([
                'success' => true,
                'data' => $results,
            ], 200);
        } catch (\Throwable $e) {
            Log::error('VDR Get failed', ['message' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function get(Request $request)
    {
        $validated = $request->validate([
            'vdrNo' => 'nullable|string|required_without:direction',
            'branchCode' => 'required|string',
            'direction' => 'nullable|string',
        ]);
        $jsonString = json_encode($validated, JSON_UNESCAPED_UNICODE);

        try {
            $results = DB::select(
                'EXEC sproc_PHP_VDR @mode = ?, @params = ?',
                ['Get', $jsonString]
            );

            return response()->json([
                'success' => true,
                'data' => $results,
            ], 200);
        } catch (\Throwable $e) {
            Log::error('VDR Get failed', ['message' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function upsert(Request $request)
    {
        $request->validate([
            'json_data' => 'required|array',
            'json_data.branchCode' => 'required|string',
            'json_data.vdrDate' => 'required',
            'json_data.vsoId' => 'required|string',
            'json_data.dt1' => 'required|array|size:1',
            'json_data.dt1.0.veId' => 'required|string',
        ]);

        try {
            // Validation returns only fields that have explicit nested rules.
            // Use the original validated envelope so optional header/detail values
            // are not removed before the stored procedure receives the payload.
            $jsonData = $request->input('json_data');
            while (isset($jsonData['json_data']) && is_array($jsonData['json_data'])) {
                $jsonData = $jsonData['json_data'];
            }

            $params = json_encode([
                'json_data' => $jsonData,
            ], JSON_UNESCAPED_UNICODE);

            $results = DB::select(
                'EXEC sproc_PHP_VDR @mode = ?, @params = ?',
                ['Upsert', $params]
            );

            return response()->json([
                'success' => true,
                'status' => 'success',
                'mode' => 'Upsert',
                'data' => $results,
            ], 200);
        } catch (\Throwable $e) {
            Log::error('VDR Upsert failed', ['message' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'status' => 'error',
                'message' => 'Error executing VDR Upsert.',
                'details' => $e->getMessage(),
            ], 500);
        }
    }

    public function cancel(Request $request)
    {
        $validated = $request->validate([
            'json_data' => 'required|array',
        ]);

        try {
            $params = json_encode([
                'json_data' => $validated['json_data'],
            ], JSON_UNESCAPED_UNICODE);

            $results = DB::select(
                'EXEC sproc_PHP_VDR @mode = ?, @params = ?',
                ['Cancel', $params]
            );

            return response()->json([
                'success' => true,
                'status' => 'success',
                'mode' => 'Cancel',
                'data' => $results,
            ], 200);
        } catch (\Throwable $e) {
            Log::error('VDR Cancel failed', ['message' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'status' => 'error',
                'message' => 'Error executing VDR Cancel.',
                'details' => $e->getMessage(),
            ], 500);
        }
    }

    public function history(Request $request)
    {
        $validated = $request->validate([
            'json_data' => 'required|array',
        ]);

        try {
            $params = json_encode([
                'json_data' => $validated['json_data'],
            ], JSON_UNESCAPED_UNICODE);

            $results = DB::select(
                'EXEC sproc_PHP_VDR @mode = ?, @params = ?',
                ['History', $params]
            );

            return response()->json([
                'success' => true,
                'status' => 'success',
                'mode' => 'History',
                'data' => $results,
            ], 200);
        } catch (\Throwable $e) {
            Log::error('VDR History failed', ['message' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'status' => 'error',
                'message' => 'Error executing VDR History.',
                'details' => $e->getMessage(),
            ], 500);
        }
    }

    public function openVso(Request $request)
    {
        try {
            $payload = $request->query();
            $params = $request->query('PARAMS');

            if (is_string($params) && $params !== '') {
                $decoded = json_decode($params, true, 512, JSON_THROW_ON_ERROR);
                $payload = $decoded['json_data'] ?? $decoded;
            }

            $validated = validator($payload, [
                'branchCode' => 'required|string',
                'custCode' => 'nullable|string',
                'vsoId' => 'nullable|string',
            ])->validate();
            $params = json_encode(['json_data' => $validated], JSON_UNESCAPED_UNICODE);
            $results = DB::select('EXEC sproc_PHP_VDR @mode = ?, @params = ?', ['OpenVSO', $params]);

            return response()->json(['success' => true, 'data' => $results], 200);
        } catch (\Throwable $e) {
            Log::error('VDR OpenVSO failed', ['message' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function generateGL(Request $request)
    {
        try {
            $validated = $request->validate([
                'json_data' => 'required|array',
                'json_data.branchCode' => 'required|string',
                'json_data.vdrDate' => 'required',
                'json_data.custCode' => 'nullable|string',
                'json_data.dt1' => 'required|array|min:1',
            ]);

            $params = json_encode([
                'json_data' => $validated['json_data'],
            ], JSON_UNESCAPED_UNICODE);

            $results = DB::select(
                'EXEC sproc_PHP_VDR @mode = ?, @params = ?',
                ['GenerateEntries', $params]
            );

            return response()->json([
                'status' => 'success',
                'data' => $results,
            ]);
        } catch (\Throwable $e) {
            Log::error('VDR Generate Entries failed', ['message' => $e->getMessage()]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to generate VDR entries.',
                'details' => $e->getMessage(),
            ], 500);
        }
    }

    public function posting()
    {
        try {
            $results = DB::select(
                'EXEC sproc_PHP_VDR @mode = ?',
                ['Posting']
            );

            return response()->json([
                'success' => true,
                'data' => $results,
            ], 200);
        } catch (\Throwable $e) {
            Log::error('VDR Posting failed', ['message' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function finalize(Request $request)
    {
        $validated = $request->validate([
            'json_data' => 'required|array',
            'json_data.userCode' => 'required|string',
            'json_data.dt1' => 'required|array|min:1',
            'json_data.dt1.*.groupId' => 'required|string',
        ]);

        try {
            $params = json_encode([
                'json_data' => $validated['json_data'],
            ], JSON_UNESCAPED_UNICODE);

            $results = DB::select(
                'EXEC sproc_PHP_Posting_VDR @mode = ?, @params = ?',
                ['Finalize', $params]
            );

            if (! empty($results)) {
                $errorCount = (int) ($results[0]->errorCount ?? 0);
                $errorMsg = trim((string) ($results[0]->errorMsg ?? ''));

                if ($errorCount > 0) {
                    return response()->json([
                        'success' => false,
                        'status' => 'error',
                        'message' => $errorMsg ?: 'Unable to finalize Vehicle Delivery Receipt.',
                        'data' => $results,
                    ], 422);
                }
            }

            return response()->json([
                'success' => true,
                'status' => 'success',
                'mode' => 'Finalize',
                'data' => $results,
            ], 200);
        } catch (\Throwable $e) {
            Log::error('VDR Finalize failed', ['message' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function checkDuplicate(Request $request)
    {
        try {
            $params = json_encode([
                'json_data' => $request->all(),
            ], JSON_UNESCAPED_UNICODE);

            $results = DB::select(
                'EXEC sproc_PHP_VDR @mode = ?, @params = ?',
                ['CheckDuplicate', $params]
            );

            return response()->json([
                'success' => true,
                'data' => $results,
            ], 200);
        } catch (\Throwable $e) {
            Log::error('VDR CheckDuplicate failed', ['message' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
