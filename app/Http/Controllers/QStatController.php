<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class QStatController extends Controller
{
    
    
public function lookup(Request $request) {

    $request->validate([
        'PARAMS' => 'required',
    ]);
    $params = $request->input('PARAMS');

    try {
        $results = DB::select(
            'EXEC sproc_PHP_QStatRef @mode = ?, @params = ?',
            ['Lookup' ,$params] 
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
