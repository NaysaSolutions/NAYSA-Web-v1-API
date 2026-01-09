<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class GLBalanceController extends Controller
{   


    
public function getGLInquiry(Request $request)
    { 
     try {

        $dataArray = $request->input('json_data');
        $jsonString = json_encode($dataArray);
           

        $results = DB::select(
            'EXEC sproc_PHP_GL_Inq @_mode =?, @_params =? ',
            ['GL_Inq', $jsonString] 
        );
        return response()->json([
            'success' => true,
            'data' => $results,
        ], 200);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => $e->getMessage(),
            'trace' => $e->getTraceAsString()

        ], 500);
    }
    }




 
public function getSLInquiry(Request $request)
    { 
     try {

        $dataArray = $request->input('json_data');
        $jsonString = json_encode($dataArray);
           

        $results = DB::select(
            'EXEC sproc_PHP_GL_Inq @_mode =?, @_params =? ',
            ['SL_Inq', $jsonString] 
        );
        return response()->json([
            'success' => true,
            'data' => $results,
        ], 200);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => $e->getMessage(),
            'trace' => $e->getTraceAsString()

        ], 500);
    }
    }





public function getTBSummary(Request $request)
    { 
     try {

        $dataArray = $request->input('json_data');
        $jsonString = json_encode($dataArray);
           

        $results = DB::select(
            'EXEC sproc_PHP_GL_Inq @_mode =?, @_params =? ',
            ['Tribal_Summary', $jsonString] 
        );
        return response()->json([
            'success' => true,
            'data' => $results,
        ], 200);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => $e->getMessage(),
            'trace' => $e->getTraceAsString()

        ], 500);
    }
    }




}
