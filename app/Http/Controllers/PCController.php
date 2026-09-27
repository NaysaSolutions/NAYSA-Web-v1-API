<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PCController extends Controller
{
    private function executePC(string $mode, array $jsonData)
    {
        $params = $mode === 'Get'
            ? json_encode($jsonData, JSON_UNESCAPED_UNICODE)
            : json_encode(['json_data' => $jsonData], JSON_UNESCAPED_UNICODE);

        return DB::select(
            'exec sproc_PHP_PC @mode = ?, @params = ?',
            [$mode, $params]
        );
    }



    public function getPC(Request $request)
    {
        try {
            $jsonData = $request->input('json_data', $request->all());

            return response()->json([
                'success' => true,
                'data' => $this->executePC('Get', $jsonData)
            ]);
        } catch (\Throwable $e) {
            Log::error('PC Get: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }



    

public function upsertPC(Request $request)
{
        $validated = $request->validate([
            'json_data' => 'required|array'
        ]);

        try {
            $params = json_encode(['json_data' => $validated['json_data']]);
            $mode = 'Upsert';

            // Call the stored procedure
            $result = DB::select('EXEC sproc_PHP_PC @mode = ?, @params = ?', [
                $mode,
                $params
            ]);

            return response()->json([
                'status' => 'success',
                'data' => $result
            ], 200);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error executing SVI Upsert.',
                'details' => $e->getMessage()
            ], 500);
        }
}





    public function getPCHistory(Request $request)
    {
        $validated = $request->validate([
            'json_data' => 'required|array'
        ]);

        try {
            return response()->json([
                'success' => true,
                'data' => $this->executePC('History', $validated['json_data'])
            ]);
        } catch (\Throwable $e) {
            Log::error('PC History: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }



    public function findPC(Request $request)
    {
        $validated = $request->validate([
            'json_data' => 'required|array'
        ]);

        try {
            return response()->json([
                'success' => true,
                'data' => $this->executePC('Find', $validated['json_data'])
            ]);
        } catch (\Throwable $e) {
            Log::error('PC Find: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }



    public function cancelPC(Request $request)
    {
        $validated = $request->validate([
            'json_data' => 'required|array'
        ]);

        try {
            return response()->json([
                'success' => true,
                'data' => $this->executePC('Cancel', $validated['json_data'])
            ]);
        } catch (\Throwable $e) {
            Log::error('PC Cancel: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }



    public function validatePCUpload(Request $request)
    {
        $validated = $request->validate([
            'json_data' => 'required|array'
        ]);

        try {
            return response()->json([
                'success' => true,
                'data' => $this->executePC('ValidateUpload', $validated['json_data'])
            ]);
        } catch (\Throwable $e) {
            Log::error('PC Validate Upload: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }



    public function getPCCountSheet(Request $request)
    {
        $validated = $request->validate([
            'json_data' => 'required|array'
        ]);

        try {
            return response()->json([
                'success' => true,
                'data' => $this->executePC('CountSheet', $validated['json_data'])
            ]);
        } catch (\Throwable $e) {
            Log::error('PC Count Sheet: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }



    public function finalizePC(Request $request)
    {
        $validated = $request->validate([
            'json_data' => 'required|array'
        ]);

        try {
            $params = json_encode([
                'json_data' => $validated['json_data']
            ], JSON_UNESCAPED_UNICODE);

            $result = DB::select(
                'exec sproc_PHP_Posting_PC @mode = ?, @params = ?',
                ['Finalize', $params]
            );

            return response()->json([
                'success' => true,
                'data' => $result
            ]);
        } catch (\Throwable $e) {
            Log::error('PC Finalize: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
}