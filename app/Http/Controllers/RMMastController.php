<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RMMastController extends Controller
{

    public function index(Request $request)
    {
        try {
            $results = DB::select(
                'EXEC sproc_PHP_RMMast @mode = ?',
                ['Load']
            );

            return response()->json([
                'success' => true,
                'data'    => $results,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Return RM Item Code generation mode from HS_DOC through sproc_PHP_RMMast.
     * Expected values: System / Auto / Manual
     */
    public function generationMode(Request $request)
    {
        try {
            $results = DB::select(
                'EXEC sproc_PHP_RMMast @mode = ?, @params = ?',
                ['GenerationMode', null]
            );

            return response()->json([
                'success' => true,
                'data'    => $results,
            ], 200);
        } catch (\Exception $e) {
            Log::error('RM generation mode failed: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }


    /**
     * Return the next RM Item Code for Auto mode.
     * This only previews/assigns the code on Add; it does not save a record.
     */
    public function generateCode(Request $request)
    {
        try {
            $results = DB::select(
                'EXEC sproc_PHP_RMMast @mode = ?, @params = ?',
                ['GenerateCode', null]
            );

            return response()->json([
                'success' => true,
                'data'    => $results,
            ], 200);
        } catch (\Exception $e) {
            Log::error('RM generate code failed: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }



    public function lookup(Request $request)
    {
        $raw = $request->input('PARAMS');

        $decoded = json_decode($raw, true);

        if (!is_array($decoded)) {
            $decoded = [];
        }

        if (!array_key_exists('search', $decoded)) {
            $decoded['search'] = '';
        }
        if (!array_key_exists('searchMode', $decoded)) {
            $decoded['searchMode'] = 'contains';
        }
        if (!array_key_exists('filter', $decoded)) {
            $decoded['filter'] = 'ActiveAll';
        }

        $params = json_encode($decoded);

        try {
            $results = DB::select(
                'EXEC sproc_PHP_RMMast @mode = ?, @params = ?',
                ['Lookup', $params]
            );

            return response()->json([
                'success' => true,
                'data'    => $results,
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
            'ITEM_CODE' => 'required|string',
        ]);

        $params = $request->input('ITEM_CODE');

        try {
            $results = DB::select(
                'EXEC sproc_PHP_RMMast @mode = ?, @params = ?',
                ['get', $params]
            );

            return response()->json([
                'success' => true,
                'data'    => $results,
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
            $params = $request->all();

            if (is_array($params) || is_object($params)) {
                $params = json_encode($params);
            }

            if (!is_string($params) || !json_validate($params)) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Invalid JSON data provided.',
                ], 400);
            }

            $result = DB::select(
                'EXEC sproc_PHP_RMMast @params = :json_data, @mode = :mode',
                [
                    'json_data' => $params,
                    'mode'      => 'Upsert',
                ]
            );

            return response()->json([
                'status'  => 'success',
                'message' => 'Transaction saved successfully.',
                'data'    => $result,
            ], 200);

        } catch (\Exception $e) {
            Log::error('RM Master save failed:', ['error' => $e->getMessage()]);
            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to save transaction: ' . $e->getMessage(),
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
                'EXEC sproc_PHP_RMMast @mode = ?, @params = ?',
                ['CheckDuplicate', $params]
            );

            return response()->json([
                'success' => true,
                'data'    => $results,
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
                'EXEC sproc_PHP_RMMast @mode = ?, @params = ?',
                ['CheckInUsed', $params]
            );

            return response()->json([
                'success' => true,
                'data'    => $results,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }


    public function delete(Request $request)
    {
        $request->validate([
            'json_data' => 'required|array',
        ]);

        try {
            $params = json_encode(['json_data' => $request->json_data]);

            $results = DB::select(
                'EXEC sproc_PHP_RMMast @mode = ?, @params = ?',
                ['Delete', $params]
            );

            return response()->json([
                'success' => true,
                'data'    => $results,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

}