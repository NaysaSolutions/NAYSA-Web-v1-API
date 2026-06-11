<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PRInqController extends Controller
{
   public function getPRInquiry(Request $request)
{
    try {
        $jsonData = $request->query("json_data", []);

        $params = [
            "json_data" => [
                "branchCode"     => $jsonData["branchCode"] ?? "",
                "itemCode"       => $jsonData["itemCode"] ?? "",
                "prStatus"       => $jsonData["prStatus"] ?? "",
                "startingCutoff" => $jsonData["startingCutoff"] ?? "",
                "endingCutoff"   => $jsonData["endingCutoff"] ?? "",
                "rcCode"         => $jsonData["rcCode"] ?? "",
                "vendCode"       => $jsonData["vendCode"] ?? "",
                "invType"        => $jsonData["invType"] ?? "",
            ]
        ];

        $jsonString = json_encode($params);

        $result = DB::select(
            "EXEC dbo.sproc_PHP_PR_Inq @_params = ?",
            [$jsonString]
        );

        $raw = $result[0]->result ?? "[]";
        $data = json_decode($raw, true);

        return response()->json([
            "success" => true,
            "data" => $data
        ]);

    } catch (\Throwable $e) {
        return response()->json([
            "success" => false,
            "message" => $e->getMessage(),
            "file" => $e->getFile(),
            "line" => $e->getLine()
        ], 500);
    }
}
}