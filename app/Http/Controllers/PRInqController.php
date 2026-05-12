<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PRInqController extends Controller
{
    /**
     * Get PR Inquiry data by calling the stored procedure.
     * Matches the logic of the working POInqController.
     */
    public function getPRInquiry(Request $request)
    {
        try {
            // 1. Extract the json_data object from the request.
            // Using input() works for both query strings (GET) and body payloads (POST).
            $dataArray = $request->input('json_data');

            // 2. Wrap the data in the 'json_data' key.
            // Your SQL procedure (sproc_PHP_PR_Inq) specifically looks for this key.
            $jsonString = json_encode([
                'json_data' => $dataArray
            ]);

            // 3. Execute the stored procedure.
            // Using the same format as the working PO controller.
            $results = DB::select(
                'EXEC dbo.sproc_PHP_PR_Inq @_params = ?',
                [$jsonString]
            );

            // 4. Return the response.
            // Note: Your SQL procedure returns a single row with a 'result' column containing JSON.
            return response()->json([
                'success' => true,
                'data' => $results,
            ], 200);

        } catch (\Throwable $e) {
            // Catching Throwable handles both Exceptions and Errors.
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ], 500);
        }
    }
}