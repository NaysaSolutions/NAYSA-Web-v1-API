<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ItemBrandMatrixController extends Controller
{
    /**
     * Matches:
     * GET /api/itemBrandMatrix?mode=Load_itemBrandMatrix&itemCode=RM00000001
     */
    public function load(Request $request)
    {
        $request->validate([
            'itemCode' => 'required|string|max:50',
        ]);

        $itemCode = $request->query('itemCode');

        $rows = DB::table('ITEMBRAND_MATCHING as ibm')
            ->leftJoin('USERS as ur', 'ibm.REGISTERED_BY', '=', 'ur.USER_CODE')
            ->leftJoin('USERS as uu', 'ibm.UPDATED_BY', '=', 'uu.USER_CODE')
            ->where('ibm.ITEM_CODE', $itemCode)
            ->orderBy('ibm.ITEM_CODE')
            ->orderBy('ibm.BRAND_CODE')
            ->selectRaw('
                ibm.ITEM_CODE as itemCode,
                ibm.BRAND_CODE as brandCode,
                1 as value,
                COALESCE(ur.USER_NAME, ibm.REGISTERED_BY) as registeredBy,
                ibm.REGISTERED_DATE as registeredDate,
                COALESCE(uu.USER_NAME, ibm.UPDATED_BY) as lastUpdatedBy,
                ibm.UPDATED_DATE as lastUpdatedDate
            ')
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                [
                    'result' => $rows->toJson(),
                ],
            ],
        ], 200);
    }

    /**
     * Matches ItemBrandMatrix.jsx payload:
     * {
     *   "json_data": {
     *     "itemCode": "RM00000001",
     *     "brandCodes": ["ALA", "AR"],
     *     "userCode": "ADMIN"
     *   }
     * }
     */
    public function upsert(Request $request)
    {
        $validated = $request->validate([
            'json_data' => 'required|array',
            'json_data.itemCode' => 'required|string|max:50',
            'json_data.brandCodes' => 'nullable|array',
            'json_data.brandCodes.*' => 'nullable|string|max:250',
            'json_data.userCode' => 'nullable|string|max:50',
        ]);

        $data = $validated['json_data'];

        $itemCode = $data['itemCode'];
        $brandCodes = $data['brandCodes'] ?? [];
        $userCode = $data['userCode'] ?? 'ADMIN';

        DB::beginTransaction();

        try {
            // Replace all brand mappings for this item.
            DB::table('ITEMBRAND_MATCHING')
                ->where('ITEM_CODE', $itemCode)
                ->delete();

            $now = DB::raw('dbo.fnGetDate()');

            foreach ($brandCodes as $brandCode) {
                $brandCode = trim((string) $brandCode);

                if ($brandCode === '') {
                    continue;
                }

                DB::table('ITEMBRAND_MATCHING')->insert([
                    'ITEM_CODE' => $itemCode,
                    'BRAND_CODE' => $brandCode,
                    'REGISTERED_BY' => $userCode,
                    'REGISTERED_DATE' => $now,
                ]);
            }

            DB::commit();

            return response()->json([
                'errormsg' => '',
                'errorcount' => 0,
            ], 200);
        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'errormsg' => $e->getMessage(),
                'errorcount' => 1,
            ], 500);
        }
    }
}
