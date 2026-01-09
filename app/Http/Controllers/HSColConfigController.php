<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class HSColConfigController extends Controller
{
    public function get(Request $request) {

    $request->validate([
        'endpoint' => 'required|string',
    ]);

    $params = $request->input('endpoint');

    try {
        $results = DB::select(
            'EXEC sproc_PHP_HSColConfig @mode = ?, @params = ?',
            ['get' ,$params] 
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
