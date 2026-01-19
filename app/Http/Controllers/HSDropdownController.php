<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;


class HSDropdownController extends Controller
{
    
public function get(Request $request)
{
    // ✅ wrap to match sproc: $.json_data.dropdownColumn / $.json_data.docCode
    $payload = [
        "json_data" => [
            "dropdownColumn" => $request->input("dropdownColumn"),
            "docCode"        => $request->input("docCode"),
        ]
    ];

    $jsonString = json_encode($payload);

    try {
        $results = DB::select(
            'EXEC sproc_PHP_HSDropdown @mode = ?, @params = ?',
            ['get', $jsonString]
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
