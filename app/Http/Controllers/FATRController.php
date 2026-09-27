<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FATRController extends Controller
{
    public function index(Request $request)
    {
        try {
            $request->validate([
                'json_data' => 'required|json',
            ]);

            $params = $request->get('json_data');

            $results = DB::select(
                'EXEC sproc_PHP_FATR @mode = ?, @params = ?',
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
        $jsonData = $request->all();
        $jsonString = json_encode($jsonData);

        try {
            $results = DB::select(
                'EXEC sproc_PHP_FATR @mode = ?, @params = ?',
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

    public function posting(Request $request)
    {
        try {
            $results = DB::select(
                'EXEC sproc_PHP_FATR @mode = ?',
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

    public function upsert(Request $request)
    {
        $validated = $request->validate([
            'json_data' => 'required|array',
        ]);

        try {
            $params = json_encode(['json_data' => $validated['json_data']], JSON_UNESCAPED_UNICODE);

            $result = DB::select(
                'EXEC sproc_PHP_FATR @mode = ?, @params = ?',
                ['Upsert', $params]
            );

            return response()->json([
                'status' => 'success',
                'data' => $result,
            ], 200);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error executing FATR Upsert.',
                'details' => $e->getMessage(),
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
                'EXEC sproc_PHP_Posting_FATR @mode = ?, @params = ?',
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

    public function generateGL(Request $request)
    {
        try {
            $jsonData = $request->input('json_data');

            if (!$jsonData) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Missing json_data.',
                ], 400);
            }

            $params = json_encode(['json_data' => $jsonData], JSON_UNESCAPED_UNICODE);

            $results = DB::select(
                'EXEC sproc_PHP_FATR @mode = ?, @params = ?',
                ['GenerateEntries', $params]
            );

            return response()->json([
                'status' => 'success',
                'data' => $results,
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error executing sproc_PHP_FATR GenerateEntries: ' . $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to generate FATR GL entries.',
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
            $params = json_encode(['json_data' => $validated['json_data']], JSON_UNESCAPED_UNICODE);

            $result = DB::select(
                'EXEC sproc_PHP_FATR @mode = ?, @params = ?',
                ['Cancel', $params]
            );

            return response()->json([
                'status' => 'success',
                'data' => $result,
            ], 200);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error executing FATR Cancel.',
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
            $params = json_encode(['json_data' => $validated['json_data']], JSON_UNESCAPED_UNICODE);

            $results = DB::select(
                'EXEC sproc_PHP_FATR @mode = ?, @params = ?',
                ['History', $params]
            );

            return response()->json([
                'status' => 'success',
                'data' => $results,
            ], 200);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error executing FATR History.',
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
            $params = json_encode(['json_data' => $validated['json_data']], JSON_UNESCAPED_UNICODE);

            $results = DB::select(
                'EXEC sproc_PHP_FATR @mode = ?, @params = ?',
                ['Find', $params]
            );

            return response()->json([
                'status' => 'success',
                'data' => $results,
            ], 200);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error executing FATR Find.',
                'details' => $e->getMessage(),
            ], 500);
        }
    }

    public function assetLookup(Request $request)
    {
        try {
            $params = $request->get('PARAMS') ?: json_encode(['json_data' => $request->all()], JSON_UNESCAPED_UNICODE);

            $results = DB::select(
                'EXEC sproc_PHP_FAAssetInquiry @mode = ?, @params = ?',
                ['Lookup', $params]
            );

            return response()->json([
                'success' => true,
                'data' => $results,
            ], 200);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error executing FA Asset Lookup.',
                'details' => $e->getMessage(),
            ], 500);
        }
    }

    public function assetInquiry(Request $request)
    {
        try {
            $params = $request->get('PARAMS') ?: json_encode(['json_data' => $request->all()], JSON_UNESCAPED_UNICODE);

            $results = DB::select(
                'EXEC sproc_PHP_FAAssetInquiry @mode = ?, @params = ?',
                ['Inquiry', $params]
            );

            return response()->json([
                'success' => true,
                'data' => $results,
            ], 200);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error executing FA Asset Inquiry.',
                'details' => $e->getMessage(),
            ], 500);
        }
    }
}
