<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BUDCLController extends Controller
{
    private string $sproc = 'sproc_PHP_BUDCL';
    private string $postingSproc = 'sproc_PHP_Posting_BUDCL';
    private string $budMoveSproc = 'sproc_PHP_BUDMove';

    private function execBudcl(string $mode, string $params = '{}'): array
    {
        return DB::select("EXEC {$this->sproc} @mode = ?, @params = ?", [
            $mode,
            $params,
        ]);
    }

    private function execBudMove(string $mode, string $params = '{}'): array
    {
        return DB::select("EXEC {$this->budMoveSproc} @mode = ?, @params = ?", [
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
            $results = $this->execBudcl('Load', $params);

            return response()->json([
                'success' => true,
                'data' => $results,
            ], 200);
        } catch (\Exception $e) {
            Log::error('BUDCL load failed', ['error' => $e->getMessage()]);

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
            $results = $this->execBudcl('Get', $jsonString);

            return response()->json([
                'success' => true,
                'data' => $results,
            ], 200);
        } catch (\Exception $e) {
            Log::error('BUDCL get failed', ['error' => $e->getMessage()]);

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
            $result = $this->execBudcl('Upsert', $params);

            return response()->json([
                'status' => 'success',
                'data' => $result,
            ], 200);
        } catch (\Throwable $e) {
            Log::error('BUDCL upsert failed', ['error' => $e->getMessage()]);

            return response()->json([
                'status' => 'error',
                'message' => 'Error executing BUDCL Upsert.',
                'details' => $e->getMessage(),
            ], 500);
        }
    }

    public function cancel(Request $request)
    {
        try {
            $params = $this->getJsonDataParams($request);
            $result = $this->execBudcl('Cancel', $params);

            return response()->json([
                'status' => 'success',
                'data' => $result,
            ], 200);
        } catch (\Throwable $e) {
            Log::error('BUDCL cancel failed', ['error' => $e->getMessage()]);

            return response()->json([
                'status' => 'error',
                'message' => 'Error executing BUDCL Cancel.',
                'details' => $e->getMessage(),
            ], 500);
        }
    }

    public function history(Request $request)
    {
        try {
            $params = $this->getJsonDataParams($request);
            $results = $this->execBudcl('History', $params);

            return response()->json([
                'status' => 'success',
                'data' => $results,
            ], 200);
        } catch (\Throwable $e) {
            Log::error('BUDCL history failed', ['error' => $e->getMessage()]);

            return response()->json([
                'status' => 'error',
                'message' => 'Error executing BUDCL History.',
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

            $results = $this->execBudcl('Posting', $params);

            return response()->json([
                'success' => true,
                'data' => $results,
            ], 200);
        } catch (\Exception $e) {
            Log::error('BUDCL posting list failed', ['error' => $e->getMessage()]);

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
                "EXEC {$this->postingSproc} @mode = ?, @params = ?",
                ['Finalize', $params]
            );

            return response()->json([
                'success' => true,
                'data' => $results,
            ], 200);
        } catch (\Exception $e) {
            Log::error('BUDCL finalize failed', ['error' => $e->getMessage()]);

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
            $results = $this->execBudcl('Find', $params);

            return response()->json([
                'status' => 'success',
                'data' => $results,
            ], 200);
        } catch (\Throwable $e) {
            Log::error('BUDCL find failed', ['error' => $e->getMessage()]);

            return response()->json([
                'status' => 'error',
                'message' => 'Error executing BUDCL Find.',
                'details' => $e->getMessage(),
            ], 500);
        }
    }


    
    public function budgetBalance(Request $request)
    {
        try {
            $params = $this->getJsonDataParams($request);
            $results = $this->execBudMove('BUDTop1OpenTempBalance', $params);

            return response()->json([
                'status' => 'success',
                'data' => $results,
            ], 200);
        } catch (\Throwable $e) {
            Log::error('BUDCL budget balance failed', ['error' => $e->getMessage()]);

            return response()->json([
                'status' => 'error',
                'message' => 'Error executing BUDCL Budget Balance.',
                'details' => $e->getMessage(),
            ], 500);
        }
    }
}
