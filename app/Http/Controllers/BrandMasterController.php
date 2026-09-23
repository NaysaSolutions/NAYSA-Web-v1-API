<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BrandMasterController extends Controller
{
    /**
     * LOAD ALL BRAND MASTER RECORDS
     */
    public function index(Request $request)
    {
        try {
            $results = DB::select(
                'EXEC sproc_PHP_BrandMast @mode = ?',
                ['Load']
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


    /**
     * LOOKUP
     * Returns ACTIVE = Y records from the SPROC
     */
    public function lookup(Request $request)
    {
        try {
            $params = $request->input('PARAMS');

            $results = DB::select(
                'EXEC sproc_PHP_BrandMast @mode = ?, @params = ?',
                ['Lookup', $params]
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


    /**
     * GET SINGLE BRAND RECORD
     *
     * Expected:
     * BRAND_CODE
     */
    public function get(Request $request)
    {
        $validated = $request->validate([
            'BRAND_CODE' => 'required|string|max:50',
        ]);

        try {
            $params = json_encode([
                'json_data' => [
                    'brandCode' => $validated['BRAND_CODE'],
                ]
            ]);

            $results = DB::select(
                'EXEC sproc_PHP_BrandMast @mode = ?, @params = ?',
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


    /**
     * UPSERT BRAND MASTER
     *
     * Expected JSON:
     *
     * {
     *     "json_data": {
     *         "brandCode": "BRAND001",
     *         "brandName": "Brand Name",
     *         "active": "Y",
     *         "userCode": "AGA",
     *         "itemCodes": ["ITEM001", "ITEM002"]
     *     }
     * }
     */
    public function upsert(Request $request)
    {
        $request->validate([
            'json_data' => 'required|array',
            'json_data.brandCode' => 'required|string|max:50',
            'json_data.brandName' => 'required|string|max:250',
            'json_data.active' => 'nullable|string|max:1',
            'json_data.userCode' => 'nullable|string|max:50',
            'json_data.itemCodes' => 'nullable|array',
            'json_data.itemCodes.*' => 'nullable|string|max:50',
        ]);

        $data = $request->json_data;

        try {
            $params = json_encode([
                'json_data' => $data
            ]);

            $result = DB::select(
                'EXEC sproc_PHP_BrandMast @mode = ?, @params = ?',
                ['Upsert', $params]
            );

            return response()->json([
                'errormsg' => $result[0]->errormsg ?? '',
                'errorcount' => $result[0]->errorcount ?? 0,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'errormsg' => $e->getMessage(),
                'errorcount' => 1,
            ], 500);
        }
    }


    /**
     * LOAD ITEM / BRAND MATCHING
     */
    public function itemBrandMatching(Request $request)
    {
        $validated = $request->validate([
            'brandCode' => 'required|string|max:50',
        ]);

        try {
            $params = json_encode([
                'json_data' => [
                    'brandCode' => $validated['brandCode'],
                ]
            ]);

            $results = DB::select(
                'EXEC sproc_PHP_BrandMast @mode = ?, @params = ?',
                ['Load_itemBrandMatrix', $params]
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


    /**
     * UPSERT ITEM / BRAND MATCHING ONLY
     */
    public function upsertItemBrandMatching(Request $request)
    {
        $request->validate([
            'json_data' => 'required|array',
            'json_data.brandCode' => 'required|string|max:50',
            'json_data.itemCodes' => 'nullable|array',
            'json_data.itemCodes.*' => 'nullable|string|max:50',
            'json_data.userCode' => 'nullable|string|max:50',
        ]);

        try {
            $params = json_encode([
                'json_data' => $request->json_data
            ]);

            $result = DB::select(
                'EXEC sproc_PHP_BrandMast @mode = ?, @params = ?',
                ['UpsertItemBrandMatrix', $params]
            );

            return response()->json([
                'errormsg' => $result[0]->errormsg ?? '',
                'errorcount' => $result[0]->errorcount ?? 0,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'errormsg' => $e->getMessage(),
                'errorcount' => 1,
            ], 500);
        }
    }


    /**
     * CHECK IF RECORD IS IN USE
     *
     * Currently the SPROC always returns false / 0
     * because no dependent transaction table was provided.
     */
    public function checkInUsed(Request $request)
    {
        $validated = $request->validate([
            'json_data' => 'required|array',
            'json_data.brandCode' => 'required|string|max:50',
        ]);

        $params = json_encode($validated);

        try {
            $results = DB::select(
                'EXEC sproc_PHP_BrandMast @mode = ?, @params = ?',
                ['CheckInUsed', $params]
            );

            $raw = $results[0]->result ?? '{"result":"0"}';
            $decoded = json_decode($raw, true);

            return response()->json([
                'success' => true,
                'isInUsed' => ($decoded['result'] ?? '0') === '1',
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }


    /**
     * CHECK DUPLICATE
     *
     * Duplicate is based on BRAND_CODE
     */
    public function checkDuplicate(Request $request)
    {
        $validated = $request->validate([
            'json_data' => 'required|array',
            'json_data.brandCode' => 'required|string|max:50',
        ]);

        $params = json_encode($validated);

        try {
            $results = DB::select(
                'EXEC sproc_PHP_BrandMast @mode = ?, @params = ?',
                ['CheckDuplicate', $params]
            );

            $raw = $results[0]->result ?? '{"result":"0"}';
            $decoded = json_decode($raw, true);

            return response()->json([
                'success' => true,
                'result' => $decoded['result'] ?? '0',
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }


    /**
     * DELETE BRAND MASTER RECORD
     */
    public function delete(Request $request)
    {
        $validated = $request->validate([
            'json_data' => 'required|array',
            'json_data.brandCode' => 'required|string|max:50',
            'json_data.userCode' => 'nullable|string|max:50',
        ]);

        $data = $validated['json_data'];

        try {
            $params = json_encode([
                'json_data' => $data
            ]);

            $result = DB::select(
                'EXEC sproc_PHP_BrandMast @mode = ?, @params = ?',
                ['Delete', $params]
            );

            $errorCount = $result[0]->errorcount ?? 0;
            $errorMsg = $result[0]->errormsg ?? '';

            if ($errorCount > 0) {
                return response()->json([
                    'success' => false,
                    'message' => $errorMsg,
                ], 400);
            }

            return response()->json([
                'success' => true,
                'message' => 'Deleted successfully.',
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
