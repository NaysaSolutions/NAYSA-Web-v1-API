<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RMPRController extends Controller
{
    public function index(Request $request)
    {
        try {
            $request->validate([
                'json_data' => 'required|json',
            ]);

            $params = $request->get('json_data');

            $results = DB::select(
                'EXEC sproc_PHP_RMPR @mode = ?, @params = ?',
                ['Get', $params]
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
        $jsonData = $request->all();
        $jsonString = json_encode($jsonData);

        try {
            $results = DB::select(
                'EXEC sproc_PHP_RMPR @mode = ?, @params = ?',
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

    public function getWO(Request $request)
    {
        $jsonData = $request->all();
        $jsonString = json_encode($jsonData);

        try {
            $results = DB::select(
                'EXEC sproc_PHP_RMPR @mode = ?, @params = ?',
                ['SearchWO', $jsonString]
            );

            $decoded = [];

            if (!empty($results) && isset($results[0]->result)) {
                $decoded = json_decode($results[0]->result, true) ?? [];
            }

            return response()->json([
                'success' => true,
                'data' => $decoded,
            ], 200);
        } catch (\Throwable $e) {
            Log::error('Error executing RMPR SearchWO: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error executing RMPR SearchWO.',
                'details' => $e->getMessage(),
            ], 500);
        }
    }

    public function searchWO(Request $request)
    {
        return $this->getWO($request);
    }

    public function upsert(Request $request)
    {
        $validated = $request->validate([
            'json_data' => 'required|array'
        ]);

        try {
            $params = json_encode([
                'json_data' => $validated['json_data']
            ], JSON_UNESCAPED_UNICODE);

            $result = DB::select(
                'EXEC sproc_PHP_RMPR @mode = ?, @params = ?',
                ['Upsert', $params]
            );

            return response()->json([
                'success' => true,
                'status'  => 'success',
                'mode'    => 'Upsert',
                'data'    => $result
            ], 200);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'status'  => 'error',
                'message' => 'Error executing RMPR Upsert.',
                'details' => $e->getMessage()
            ], 500);
        }
    }

    public function generateGL(Request $request)
    {
        try {
            $jsonData = $request->input('json_data');

            if (!$jsonData) {
                return response()->json(['error' => 'Missing json_data'], 400);
            }

            $jsonString = json_encode(['json_data' => $jsonData], JSON_UNESCAPED_UNICODE);

            $results = DB::select(
                'EXEC sproc_PHP_RMPR @mode = ?, @params = ?',
                ['GenerateEntries', $jsonString]
            );

            return response()->json([
                'status' => 'success',
                'data' => $results
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error executing sproc_PHP_RMPR GenerateEntries: ' . $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to generate entries.',
                'details' => $e->getMessage()
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

            $result = DB::select(
                'EXEC sproc_PHP_RMPR @mode = ?, @params = ?',
                [$mode, $params]
            );

            return response()->json([
                'status' => 'success',
                'data' => $result
            ], 200);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error executing RMPR Cancel.',
                'details' => $e->getMessage()
            ], 500);
        }
    }

    public function history(Request $request)
    {
        $validated = $request->validate([
            'json_data' => 'required|array'
        ]);

        try {
            $params = json_encode(['json_data' => $validated['json_data']]);
            $mode = 'History';

            $results = DB::select(
                'EXEC sproc_PHP_RMPR @mode = ?, @params = ?',
                [$mode, $params]
            );

            return response()->json([
                'status' => 'success',
                'data' => $results
            ], 200);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error executing RMPR History.',
                'details' => $e->getMessage()
            ], 500);
        }
    }

    public function posting(Request $request)
    {
        try {
            $results = DB::select(
                'EXEC sproc_PHP_RMPR @mode = ?',
                ['Posting']
            );

            $decoded = [];

            if (!empty($results) && isset($results[0]->result)) {
                $decoded = json_decode($results[0]->result, true) ?? [];
            }

            return response()->json([
                'success' => true,
                'data' => $decoded,
            ], 200);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function finalize(Request $request)
    {
        try {
            Log::info('🟠 RMPR FINALIZE RAW REQUEST', [
                'all' => $request->all()
            ]);

            $validated = $request->validate([
                'json_data' => 'required|array'
            ]);

            $params = json_encode([
                'json_data' => $validated['json_data']
            ]);

            $results = DB::select(
                'EXEC sproc_PHP_Posting_RMPR @mode = ?, @params = ?, @userCode = ?',
                ['Finalize', $params, $request->user()->USER_CODE ?? $request->input('json_data.userCode', 'ADMIN')]
            );

            /*
             * useHandlePostTran expects a posting summary in data[0].result.
             * If the sproc returns errorMsg/errorCount only, normalize it here
             * so the frontend will not show "No posting summary returned".
             */
            if (empty($results)) {
                $results = [
                    (object) [
                        'result' => 'No RMPR posting summary returned from sproc_PHP_Posting_RMPR.',
                        'errorMsg' => 'No RMPR posting summary returned from sproc_PHP_Posting_RMPR.',
                        'errorCount' => 1,
                    ]
                ];
            } elseif (!isset($results[0]->result)) {
                $message = $results[0]->errorMsg
                    ?? $results[0]->message
                    ?? 'No RMPR posting summary returned from sproc_PHP_Posting_RMPR.';

                $results[0]->result = $message;
            }

            return response()->json([
                'success' => true,
                'data' => $results,
            ], 200);
        } catch (\Exception $e) {
            Log::error('❌ RMPR FINALIZE ERROR', [
                'message' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function find(Request $request)
    {
        $validated = $request->validate([
            'json_data' => 'required|array'
        ]);

        try {
            $params = json_encode(['json_data' => $validated['json_data']]);
            $mode = 'Find';

            $results = DB::select(
                'EXEC sproc_PHP_RMPR @mode = ?, @params = ?',
                [$mode, $params]
            );

            return response()->json([
                'status' => 'success',
                'data' => $results
            ], 200);
        } catch (\Throwable $e) {
            Log::error('Error executing RMPR Find: ' . $e->getMessage());

            return response()->json([
                'status'  => 'error',
                'message' => 'Error executing RMPR Find.',
                'details' => $e->getMessage()
            ], 500);
        }
    }

    public function generateEntries(Request $request)
    {
        return $this->generateGL($request);
    }

    public function queryHistory(Request $request)
    {
        return $this->history($request);
    }

    public function postingList(Request $request)
    {
        return $this->posting($request);
    }

    public function finalizePosting(Request $request)
    {
        return $this->finalize($request);
    }
}
