<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BUDAUController extends Controller
{
    private string $sproc = 'sproc_PHP_BUDAU';

    private function execBudau(string $mode, string $params = '{}'): array
    {
        return DB::select("EXEC {$this->sproc} @mode = ?, @params = ?", [
            $mode,
            $params,
        ]);
    }

    private function getJsonDataParams(Request $request): string
    {
        $validated = $request->validate([
            'json_data' => 'required|array',
        ]);

        return json_encode(
            ['json_data' => $validated['json_data']],
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        );
    }

    public function index(Request $request)
    {
        try {
            $request->validate([
                'json_data' => 'required|json',
            ]);

            $params = $request->get('json_data');
            $results = $this->execBudau('Load', $params);

            return response()->json([
                'success' => true,
                'data' => $results,
            ], 200);
        } catch (\Exception $e) {
            Log::error('BUDAU load failed', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function get(Request $request)
    {
        $jsonData = $request->all();
        $jsonString = json_encode($jsonData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        try {
            $results = $this->execBudau('Get', $jsonString);

            return response()->json([
                'success' => true,
                'data' => $results,
            ], 200);
        } catch (\Exception $e) {
            Log::error('BUDAU get failed', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function upsert(Request $request)
    {
        try {
            $params = $this->getJsonDataParams($request);
            $result = $this->execBudau('Upsert', $params);

            return response()->json([
                'status' => 'success',
                'data' => $result,
            ], 200);
        } catch (\Throwable $e) {
            Log::error('BUDAU upsert failed', ['error' => $e->getMessage()]);

            return response()->json([
                'status' => 'error',
                'message' => 'Error executing BUDAU Upsert.',
                'details' => $e->getMessage(),
            ], 500);
        }
    }

    public function cancel(Request $request)
    {
        try {
            $params = $this->getJsonDataParams($request);
            $result = $this->execBudau('Cancel', $params);

            return response()->json([
                'status' => 'success',
                'data' => $result,
            ], 200);
        } catch (\Throwable $e) {
            Log::error('BUDAU cancel failed', ['error' => $e->getMessage()]);

            return response()->json([
                'status' => 'error',
                'message' => 'Error executing BUDAU Cancel.',
                'details' => $e->getMessage(),
            ], 500);
        }
    }

    public function history(Request $request)
    {
        try {
            $params = $this->getJsonDataParams($request);
            $results = $this->execBudau('History', $params);

            return response()->json([
                'status' => 'success',
                'data' => $results,
            ], 200);
        } catch (\Throwable $e) {
            Log::error('BUDAU history failed', ['error' => $e->getMessage()]);

            return response()->json([
                'status' => 'error',
                'message' => 'Error executing BUDAU History.',
                'details' => $e->getMessage(),
            ], 500);
        }
    }

    public function posting(Request $request)
    {
        try {
            $params = '{}';

            if ($request->has('json_data')) {
                $jsonData = $request->get('json_data');

                $params = is_array($jsonData)
                    ? json_encode(['json_data' => $jsonData], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
                    : $jsonData;
            }

            $results = $this->execBudau('Posting', $params);

            return response()->json([
                'success' => true,
                'data' => $results,
            ], 200);
        } catch (\Exception $e) {
            Log::error('BUDAU posting list failed', ['error' => $e->getMessage()]);

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
                'json_data' => 'required|array',
            ]);

            $params = json_encode(
                ['json_data' => $validated['json_data']],
                JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
            );

            $results = DB::select(
                'EXEC sproc_PHP_Posting_BUDAU @mode = ?, @params = ?',
                ['Finalize', $params]
            );

            return response()->json([
                'success' => true,
                'data' => $results,
            ], 200);
        } catch (\Exception $e) {
            Log::error('BUDAU finalize failed', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function find(Request $request)
    {
        try {
            $params = $this->getJsonDataParams($request);
            $results = $this->execBudau('Find', $params);

            return response()->json([
                'status' => 'success',
                'data' => $results,
            ], 200);
        } catch (\Throwable $e) {
            Log::error('BUDAU find failed', ['error' => $e->getMessage()]);

            return response()->json([
                'status' => 'error',
                'message' => 'Error executing BUDAU Find.',
                'details' => $e->getMessage(),
            ], 500);
        }
    }
}
