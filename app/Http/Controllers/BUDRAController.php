<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BUDRAController extends Controller
{
    private string $sproc = 'sproc_PHP_BUDRA';

    private function execBudra(string $mode, string $params): array
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

        return json_encode(['json_data' => $validated['json_data']], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    public function index(Request $request)
    {
        try {
            $request->validate([
                'json_data' => 'required|json',
            ]);

            $params = $request->get('json_data');
            $results = $this->execBudra('Load', $params);

            return response()->json([
                'success' => true,
                'data' => $results,
            ], 200);
        } catch (\Exception $e) {
            Log::error('BUDRA load failed', ['error' => $e->getMessage()]);

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
            $results = $this->execBudra('Get', $jsonString);

            return response()->json([
                'success' => true,
                'data' => $results,
            ], 200);
        } catch (\Exception $e) {
            Log::error('BUDRA get failed', ['error' => $e->getMessage()]);

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
            $result = $this->execBudra('Upsert', $params);

            return response()->json([
                'status' => 'success',
                'data' => $result,
            ], 200);
        } catch (\Throwable $e) {
            Log::error('BUDRA upsert failed', ['error' => $e->getMessage()]);

            return response()->json([
                'status' => 'error',
                'message' => 'Error executing BUDRA Upsert.',
                'details' => $e->getMessage(),
            ], 500);
        }
    }

    public function cancel(Request $request)
    {
        try {
            $params = $this->getJsonDataParams($request);
            $result = $this->execBudra('Cancel', $params);

            return response()->json([
                'status' => 'success',
                'data' => $result,
            ], 200);
        } catch (\Throwable $e) {
            Log::error('BUDRA cancel failed', ['error' => $e->getMessage()]);

            return response()->json([
                'status' => 'error',
                'message' => 'Error executing BUDRA Cancel.',
                'details' => $e->getMessage(),
            ], 500);
        }
    }

    public function history(Request $request)
    {
        try {
            $params = $this->getJsonDataParams($request);
            $results = $this->execBudra('History', $params);

            return response()->json([
                'status' => 'success',
                'data' => $results,
            ], 200);
        } catch (\Throwable $e) {
            Log::error('BUDRA history failed', ['error' => $e->getMessage()]);

            return response()->json([
                'status' => 'error',
                'message' => 'Error executing BUDRA History.',
                'details' => $e->getMessage(),
            ], 500);
        }
    }


    
 public function posting(Request $request)
    {
        try {
            $results = DB::select(
                'EXEC sproc_PHP_BUDRA @mode = ?',
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
                'json_data' => 'required|array',
            ]);

            $params = json_encode(['json_data' => $validated['json_data']], JSON_UNESCAPED_UNICODE);

            $results = DB::select(
                'EXEC sproc_PHP_Posting_BUDRA @mode = ?, @params = ?',
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

    public function find(Request $request)
    {
        try {
            $params = $this->getJsonDataParams($request);
            $results = $this->execBudra('Find', $params);

            return response()->json([
                'status' => 'success',
                'data' => $results,
            ], 200);
        } catch (\Throwable $e) {
            Log::error('BUDRA find failed', ['error' => $e->getMessage()]);

            return response()->json([
                'status' => 'error',
                'message' => 'Error executing BUDRA Find.',
                'details' => $e->getMessage(),
            ], 500);
        }
    }
}
