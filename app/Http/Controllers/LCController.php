<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use App\Mail\GenericApiMail;

class LCController extends Controller
{
    public function index(Request $request)
    {
        try {
            $request->validate([
                'json_data' => 'required|json',
            ]);

            $params = $request->get('json_data');

            $results = DB::select(
                'EXEC sproc_PHP_LC @mode = ?, @params = ?',
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


    public function get(Request $request)
    {
        $jsonData = $request->all();
        $jsonString = json_encode($jsonData);

        try {
            $results = DB::select(
                'EXEC sproc_PHP_LC @mode = ?, @params = ?',
                ['Get', $jsonString]
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
        $validated = $request->validate([
            'json_data' => 'required|array'
        ]);

        try {
            $params = json_encode(['json_data' => $validated['json_data']]);

            $result = DB::select(
                'EXEC sproc_PHP_LC @mode = ?, @params = ?',
                ['Upsert', $params]
            );

            $r0 = $result[0] ?? null;
            $errorcount = (int)($r0->errorcount ?? $r0->errorCount ?? 0);
            $errormsg = (string)($r0->errormsg ?? $r0->errorMsg ?? '');

            if ($errorcount > 0) {
                return response()->json([
                    'success' => false,
                    'status' => 'error',
                    'errorcount' => $errorcount,
                    'errormsg' => $errormsg,
                    'data' => $result,
                ], 200);
            }

            return response()->json([
                'success' => true,
                'status' => 'success',
                'data' => $result,
            ], 200);
        } catch (\Throwable $e) {
            Log::error('LC Upsert failed: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'status' => 'error',
                'message' => 'Error executing LC Upsert.',
                'details' => $e->getMessage()
            ], 500);
        }
    }


    public function cancel(Request $request)
    {
        $validated = $request->validate([
            'json_data' => 'required|array'
        ]);

        try {
            $params = json_encode(['json_data' => $validated['json_data']]);

            $result = DB::select(
                'EXEC sproc_PHP_LC @mode = ?, @params = ?',
                ['Cancel', $params]
            );

            return response()->json([
                'success' => true,
                'status' => 'success',
                'data' => $result
            ], 200);
        } catch (\Throwable $e) {
            Log::error('LC Cancel failed: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'status' => 'error',
                'message' => 'Error executing LC Cancel.',
                'details' => $e->getMessage()
            ], 500);
        }
    }


    public function post(Request $request)
    {
        $validated = $request->validate([
            'json_data' => 'required|array'
        ]);

        try {
            $params = json_encode(['json_data' => $validated['json_data']]);

            $result = DB::select(
                'EXEC sproc_PHP_LC @mode = ?, @params = ?',
                ['Post', $params]
            );

            return response()->json([
                'success' => true,
                'status' => 'success',
                'data' => $result
            ], 200);
        } catch (\Throwable $e) {
            Log::error('LC Post failed: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'status' => 'error',
                'message' => 'Error executing LC Post.',
                'details' => $e->getMessage()
            ], 500);
        }
    }


    public function history(Request $request)
    {
        $validated = $request->validate([
            'json_data' => 'required|array'
        ]);

        try {
            $params = json_encode(['json_data' => $validated['json_data']]);

            $results = DB::select(
                'EXEC sproc_PHP_LC @mode = ?, @params = ?',
                ['History', $params]
            );

            return response()->json([
                'success' => true,
                'status' => 'success',
                'data' => $results
            ], 200);
        } catch (\Throwable $e) {
            Log::error('LC History failed: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'status' => 'error',
                'message' => 'Error executing LC History.',
                'details' => $e->getMessage()
            ], 500);
        }
    }


    public function find(Request $request)
    {
        $validated = $request->validate([
            'json_data' => 'required|array'
        ]);

        try {
            $params = json_encode(['json_data' => $validated['json_data']]);

            $results = DB::select(
                'EXEC sproc_PHP_LC @mode = ?, @params = ?',
                ['Find', $params]
            );

            return response()->json([
                'success' => true,
                'data' => $results,
            ], 200);
        } catch (\Throwable $e) {
            Log::error('LC Find failed: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

 public function posting(Request $request)
    {
        try {
            $results = DB::select(
                'EXEC sproc_PHP_LC @mode = ?',
                ['Posting']
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



    public function finalize(Request $request)
    {
        try {
            $validated = $request->validate([
                'json_data' => 'required|array',
            ]);

            $params = json_encode(['json_data' => $validated['json_data']], JSON_UNESCAPED_UNICODE);

            $results = DB::select(
                'EXEC sproc_PHP_Posting_LC @mode = ?, @params = ?',
                ['Finalize', $params]
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
 

public function getRRLC_OpenSummary(Request $request) {

   $jsonString = $request->input('PARAMS');

    try {
        $results = DB::select(
            'EXEC sproc_PHP_LC @mode = ?, @params = ?',
            ['GetRRLC_OpenSummary' ,$jsonString] 
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




public function getRRLC_Selected(Request $request) {

    $jsonData = $request->all(); 
    $jsonString = json_encode($jsonData); 

    try {
        $results = DB::select(
            'EXEC sproc_PHP_LC @mode = ?, @params = ?',
            ['GetRRLC_Selected' ,$jsonString] 
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
