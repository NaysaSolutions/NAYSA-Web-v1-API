<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;



class SLMasterController extends Controller
{
    public function lookup(Request $request) {
        $paramsString = $request->input('PARAMS');
        $params = json_decode($paramsString, true);
       
        try {
            $results = DB::select(
                'EXEC sproc_PHP_SLMast @mode = ?, @params = ?',
                ['Lookup' ,$params['search']] 
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




    
public function get(Request $request) {

    $request->validate([
        'SL_CODE' => 'required|string',
    ]);

    $params = $request->input('SL_CODE');


    try {
        $results = DB::select(
            'EXEC sproc_PHP_SLMast @mode = ?, @params = ?',
            ['Get' ,$params] 
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
