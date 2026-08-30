<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class VSOController extends Controller
{
    public function index(Request $request)
    {
        try {
            $request->validate([
                'json_data' => 'required|json',
            ]);

            $params = $request->get('json_data');
            $results = DB::select(
                'EXEC sproc_PHP_VSO @mode = ?, @params = ?',
                ['Get', $params]
            );

            return response()->json([
                'success' => true,
                'data' => $results,
            ], 200);
        } catch (\Throwable $e) {
            Log::error('VSO Get failed', ['message' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function get(Request $request)
    {
        $jsonData = $request->all();
        $jsonString = json_encode($jsonData, JSON_UNESCAPED_UNICODE);

        try {
            $results = DB::select(
                'EXEC sproc_PHP_VSO @mode = ?, @params = ?',
                ['Get', $jsonString]
            );

            return response()->json([
                'success' => true,
                'data' => $results,
            ], 200);
        } catch (\Throwable $e) {
            Log::error('VSO Get failed', ['message' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function upsert(Request $request)
    {
        $validated = $request->validate([
            'json_data' => 'required|array',
        ]);

        try {
            $params = json_encode([
                'json_data' => $validated['json_data'],
            ], JSON_UNESCAPED_UNICODE);

            $results = DB::select(
                'EXEC sproc_PHP_VSO @mode = ?, @params = ?',
                ['Upsert', $params]
            );

            return response()->json([
                'success' => true,
                'status' => 'success',
                'mode' => 'Upsert',
                'data' => $results,
            ], 200);
        } catch (\Throwable $e) {
            Log::error('VSO Upsert failed', ['message' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'status' => 'error',
                'message' => 'Error executing VSO Upsert.',
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
                'EXEC sproc_PHP_VSO @mode = ?, @params = ?',
                ['Cancel', $params]
            );

            return response()->json([
                'success' => true,
                'status' => 'success',
                'mode' => 'Cancel',
                'data' => $results,
            ], 200);
        } catch (\Throwable $e) {
            Log::error('VSO Cancel failed', ['message' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'status' => 'error',
                'message' => 'Error executing VSO Cancel.',
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
                'EXEC sproc_PHP_VSO @mode = ?, @params = ?',
                ['History', $params]
            );

            return response()->json([
                'success' => true,
                'status' => 'success',
                'mode' => 'History',
                'data' => $results,
            ], 200);
        } catch (\Throwable $e) {
            Log::error('VSO History failed', ['message' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'status' => 'error',
                'message' => 'Error executing VSO History.',
                'details' => $e->getMessage(),
            ], 500);
        }
    }

    public function load(Request $request)
    {
        $validated = $request->validate([
            'json_data' => 'required|array',
        ]);

        try {
            $params = json_encode([
                'json_data' => $validated['json_data'],
            ], JSON_UNESCAPED_UNICODE);

            $results = DB::select(
                'EXEC sproc_PHP_VSO @mode = ?, @params = ?',
                ['Load', $params]
            );

            return response()->json([
                'success' => true,
                'status' => 'success',
                'mode' => 'Load',
                'data' => $results,
            ], 200);
        } catch (\Throwable $e) {
            Log::error('VSO Load failed', ['message' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'status' => 'error',
                'message' => 'Error executing VSO Load.',
                'details' => $e->getMessage(),
            ], 500);
        }
    }

    public function find(Request $request)
    {
        $validated = $request->validate([
            'json_data' => 'required|array',
        ]);

        try {
            $params = json_encode([
                'json_data' => $validated['json_data'],
            ], JSON_UNESCAPED_UNICODE);

            $results = DB::select(
                'EXEC sproc_PHP_VSO @mode = ?, @params = ?',
                ['Find', $params]
            );

            return response()->json([
                'success' => true,
                'status' => 'success',
                'mode' => 'Find',
                'data' => $results,
            ], 200);
        } catch (\Throwable $e) {
            Log::error('VSO Find failed', ['message' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'status' => 'error',
                'message' => 'Error executing VSO Find.',
                'details' => $e->getMessage(),
            ], 500);
        }
    }
}
