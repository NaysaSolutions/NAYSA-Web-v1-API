<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PriceCommController extends Controller
{
    public function get(Request $request)
    {
        return $this->executeSelect('Get', $request->all());
    }

    public function history(Request $request)
    {
        return $this->executeSelect('History', $request->all());
    }

    public function upsert(Request $request)
    {
        $validated = $request->validate([
            'json_data' => ['required', 'array'],
            'json_data.priceCategCode' => ['required', 'string', 'max:40'],
            'json_data.effectivityDate' => ['required', 'date'],
            'json_data.dt1' => ['required', 'array'],
            'json_data.dt1.*.itemCode' => ['required', 'string', 'max:50'],
            'json_data.dt1.*.price' => ['nullable', 'numeric', 'min:0'],
        ]);

        return $this->executeSelect('Upsert', [
            'json_data' => $validated['json_data'],
        ]);
    }

    public function delete(Request $request)
    {
        $validated = $request->validate([
            'json_data' => ['required', 'array'],
            'json_data.pmId' => ['nullable', 'string', 'max:50'],
            'json_data.priceCategCode' => ['nullable', 'string', 'max:40'],
            'json_data.effectivityDate' => ['nullable', 'date'],
        ]);

        return $this->executeSelect('Delete', [
            'json_data' => $validated['json_data'],
        ]);
    }

    private function executeSelect(string $mode, array $payload)
    {
        try {
            $rows = DB::select(
                'EXEC sproc_PHP_PriceComm @mode = ?, @params = ?',
                [$mode, json_encode($payload)]
            );

            $first = $rows[0] ?? null;
            $errorCount = (int) ($first->errorcount ?? 0);

            return response()->json([
                'success' => $errorCount === 0,
                'message' => (string) ($first->errormsg ?? ''),
                'data' => $rows,
            ], 200);
        } catch (\Throwable $exception) {
            return response()->json([
                'success' => false,
                'message' => $exception->getMessage(),
            ], 500);
        }
    }
}
