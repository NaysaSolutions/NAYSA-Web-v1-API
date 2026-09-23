<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BankTypeController extends Controller
{
    /**
     * Normalize any supported request shape into:
     *
     * {
     *   "json_data": { ... }
     * }
     */
    private function buildParams(Request $request): string
    {
        /*
        |--------------------------------------------------------------------------
        | 1. json_data sent as object/array
        |--------------------------------------------------------------------------
        */
        $jsonData = $request->input('json_data');

        if (is_array($jsonData)) {
            return json_encode(
                ['json_data' => $jsonData],
                JSON_UNESCAPED_UNICODE
            );
        }

        /*
        |--------------------------------------------------------------------------
        | 2. json_data sent as JSON string
        |--------------------------------------------------------------------------
        */
        if (is_string($jsonData) && trim($jsonData) !== '') {
            $decoded = json_decode($jsonData, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \InvalidArgumentException(
                    'json_data is not valid JSON.'
                );
            }

            if (isset($decoded['json_data'])) {
                return json_encode(
                    $decoded,
                    JSON_UNESCAPED_UNICODE
                );
            }

            return json_encode(
                ['json_data' => $decoded],
                JSON_UNESCAPED_UNICODE
            );
        }

        /*
        |--------------------------------------------------------------------------
        | 3. PARAMS compatibility
        |--------------------------------------------------------------------------
        */
        $params = $request->input('PARAMS');

        if (is_string($params) && trim($params) !== '') {
            $decoded = json_decode($params, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \InvalidArgumentException(
                    'PARAMS is not valid JSON.'
                );
            }

            if (isset($decoded['json_data'])) {
                return json_encode(
                    $decoded,
                    JSON_UNESCAPED_UNICODE
                );
            }

            return json_encode(
                ['json_data' => $decoded],
                JSON_UNESCAPED_UNICODE
            );
        }

        /*
        |--------------------------------------------------------------------------
        | 4. Query string / ordinary request fields
        |--------------------------------------------------------------------------
        */
        return json_encode(
            [
                'json_data' => [
                    'bankTypeCode' =>
                        $request->input('bankTypeCode', ''),

                    'bankTypeName' =>
                        $request->input('bankTypeName', ''),

                    'active' =>
                        $request->input('active', ''),

                    'userCode' =>
                        $request->input('userCode', ''),
                ],
            ],
            JSON_UNESCAPED_UNICODE
        );
    }


    /**
     * Main Bank Type list
     */
    public function index(Request $request)
    {
        try {
            $results = DB::select(
                'EXEC dbo.sproc_PHP_BankRef @mode = ?, @params = ?',
                [
                    'Load',
                    $this->buildParams($request),
                ]
            );

            return response()->json([
                'success' => true,
                'data' => $results,
            ], 200);

        } catch (\Throwable $e) {

            Log::error('Bank Type Load Error', [
                'message' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }


    /**
     * Lookup used by Bank Master
     */
    public function lookup(Request $request)
    {
        try {
            $results = DB::select(
                'EXEC dbo.sproc_PHP_BankRef @mode = ?, @params = ?',
                [
                    'Lookup',
                    $this->buildParams($request),
                ]
            );

            return response()->json([
                'success' => true,
                'data' => $results,
            ], 200);

        } catch (\Throwable $e) {

            Log::error('Bank Type Lookup Error', [
                'message' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }


    /**
     * Retrieve one Bank Type
     */
    public function get(Request $request)
    {
        try {
            $request->validate([
                'bankTypeCode' => 'required|string',
            ]);

            $results = DB::select(
                'EXEC dbo.sproc_PHP_BankRef @mode = ?, @params = ?',
                [
                    'Get',
                    $this->buildParams($request),
                ]
            );

            return response()->json([
                'success' => true,
                'data' => $results,
            ], 200);

        } catch (\Throwable $e) {

            Log::error('Bank Type Get Error', [
                'message' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }


    /**
     * Duplicate validation
     */
    public function checkDuplicate(Request $request)
    {
        try {
            $results = DB::select(
                'EXEC dbo.sproc_PHP_BankRef @mode = ?, @params = ?',
                [
                    'CheckDuplicate',
                    $this->buildParams($request),
                ]
            );

            return response()->json([
                'success' => true,
                'data' => $results,
            ], 200);

        } catch (\Throwable $e) {

            Log::error('Bank Type Duplicate Check Error', [
                'message' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }


    /**
     * Insert / Update
     */
    public function upsert(Request $request)
    {
        try {
            $params = $this->buildParams($request);

            $results = DB::select(
                'EXEC dbo.sproc_PHP_BankRef @mode = ?, @params = ?',
                [
                    'Upsert',
                    $params,
                ]
            );

            return response()->json([
                'status' => 'success',
                'data' => $results,
            ], 200);

        } catch (\Throwable $e) {

            Log::error('Bank Type Upsert Error', [
                'message' => $e->getMessage(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to save Bank Type.',
                'details' => $e->getMessage(),
            ], 500);
        }
    }


    /**
     * Delete
     */
    public function delete(Request $request)
    {
        try {
            $results = DB::select(
                'EXEC dbo.sproc_PHP_BankRef @mode = ?, @params = ?',
                [
                    'Delete',
                    $this->buildParams($request),
                ]
            );

            return response()->json([
                'success' => true,
                'data' => $results,
                'message' => 'Deleted successfully.',
            ], 200);

        } catch (\Throwable $e) {

            Log::error('Bank Type Delete Error', [
                'message' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }


    /**
     * Check if Bank Type is already used by Bank Master
     */
    public function checkInUsed(Request $request)
    {
        try {
            $results = DB::select(
                'EXEC dbo.sproc_PHP_BankRef @mode = ?, @params = ?',
                [
                    'CheckInUsed',
                    $this->buildParams($request),
                ]
            );

            return response()->json([
                'success' => true,
                'data' => $results,
            ], 200);

        } catch (\Throwable $e) {

            Log::error('Bank Type Check In Use Error', [
                'message' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}