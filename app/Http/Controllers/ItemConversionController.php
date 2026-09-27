<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ItemConversionController extends Controller
{
    public function index(Request $request)
    {
        try {
            $invType = $request->query('invType');
            $itemCode = $request->query('itemCode');
            $mode = $invType && $itemCode ? 'ItemLookup' : 'Load';
            $params = $mode === 'ItemLookup'
                ? json_encode(['json_data' => [
                    'invType' => $invType,
                    'itemCode' => $itemCode,
                ]])
                : null;

            $results = DB::select(
                'EXEC sproc_PHP_ItemConversion @mode = ?, @params = ?',
                [$mode, $params]
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

    public function lookup(Request $request)
    {
        $paramsString = $request->input('PARAMS');
        $params = json_decode($paramsString, true);

        try {
            $results = DB::select(
                'EXEC sproc_PHP_ItemConversion @mode = ?, @params = ?',
                ['Lookup', $params['search']]
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
        $request->validate([
            'ITEM_UOM_CONV_ID' => 'required|string',
        ]);

        $params = $request->input('ITEM_UOM_CONV_ID');

        try {
            $results = DB::select(
                'EXEC sproc_PHP_ItemConversion @mode = ?, @params = ?',
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

    public function upsert(Request $request)
    {
        try {
            $request->validate([
                'json_data' => 'required|json',
            ]);

            $params = $request->get('json_data');

            $results = DB::select('EXEC sproc_PHP_ItemConversion @params = :json_data, @mode = :mode', [
                'json_data' => $params,
                'mode' => 'upsert',
            ]);

            return response()->json([
                'status' => 'success',
                'data' => $results,
            ], 200);
        } catch (\Exception $e) {
            Log::error('Saving failed:', ['error' => $e->getMessage()]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to save transaction: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function delete(Request $request)
    {
        try {
            $validated = $request->validate([
                'json_data' => 'required|array',
            ]);

            $params = json_encode(['json_data' => $validated['json_data']]);

            $results = DB::select(
                'EXEC sproc_PHP_ItemConversion @mode = ?, @params = ?',
                ['Delete', $params]
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

    public function checkInUsed(Request $request)
    {
        $validated = $request->validate([
            'json_data' => 'required|array',
        ]);

        $params = json_encode(['json_data' => $validated['json_data']]);

        try {
            $results = DB::select(
                'EXEC sproc_PHP_ItemConversion @mode = ?, @params = ?',
                ['CheckInUsed', $params]
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

    public function checkDuplicate(Request $request)
    {
        $validated = $request->validate([
            'json_data' => 'required|array',
        ]);

        $params = json_encode(['json_data' => $validated['json_data']]);

        try {
            $results = DB::select(
                'EXEC sproc_PHP_ItemConversion @mode = ?, @params = ?',
                ['CheckDuplicate', $params]
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

    public function convert(Request $request)
    {
        try {
            $validated = $request->validate([
                'json_data' => 'required|array',
            ]);

            $params = json_encode(['json_data' => $validated['json_data']]);

            $results = DB::select(
                'EXEC sproc_PHP_ItemConversion @mode = ?, @params = ?',
                ['Convert', $params]
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
}
