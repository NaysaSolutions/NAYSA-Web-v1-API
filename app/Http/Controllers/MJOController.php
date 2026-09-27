<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class MJOController extends Controller
{


    public function get(Request $request)
    {
        try {
            $validated = $request->validate([
                'branchCode' => 'required|string',
                'mjoNo'      => 'nullable|string',
                'direction'  => 'nullable|string',
            ]);

            if (
                empty(trim((string) ($validated['mjoNo'] ?? ''))) &&
                empty(trim((string) ($validated['direction'] ?? '')))
            ) {
                return response()->json([
                    'success' => false,
                    'message' => 'MJO No. or retrieval direction is required.',
                ], 422);
            }

            $params = json_encode(
                $validated,
                JSON_UNESCAPED_UNICODE
            );

            $results = DB::select(
                'EXEC sproc_PHP_MJO @mode = ?, @params = ?',
                ['Get', $params]
            );

            return response()->json([
                'success' => true,
                'data'    => $results,
            ], 200);

        } catch (\Throwable $e) {
            Log::error('MJO Get failed', [
                'message' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }


    public function upsert(Request $request)
    {
        $validated = $request->validate([
            'json_data' => 'required|array',
        ]);

        try {
            $params = json_encode(
                [
                    'json_data' =>
                        $validated['json_data'],
                ],
                JSON_UNESCAPED_UNICODE
            );

            $results = DB::select(
                'EXEC sproc_PHP_MJO @mode = ?, @params = ?',
                ['Upsert', $params]
            );

            $row = $results[0] ?? null;

            $errorCount = (int) (
                $row->errorCount ??
                $row->errorcount ??
                0
            );

            $errorMessage = (string) (
                $row->errorMsg ??
                $row->errormsg ??
                ''
            );

            if ($errorCount > 0) {
                return response()->json([
                    'success' => false,
                    'status'  => 'error',
                    'message' => $errorMessage ?: 'MJO validation failed.',
                    'data'    => $results,
                ], 422);
            }

            return response()->json([
                'success' => true,
                'status'  => 'success',
                'data'    => $results,
            ], 200);

        } catch (\Throwable $e) {
            Log::error('MJO Upsert failed', [
                'message' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'status'  => 'error',
                'message' => $e->getMessage(),
                'details' => $e->getMessage(),
            ], 500);
        }
    }


    public function cancel(Request $request)
    {
        $validated = $request->validate([
            'json_data'            => 'required|array',
            'json_data.branchCode' => 'required|string',
            'json_data.mjoNo'      => 'required|string',
            'json_data.userCode'   => 'required|string',
            'json_data.password'   => 'required|string',
            'json_data.reason'     => 'required|string',
        ]);

        try {
            $cancelData = $validated['json_data'];
            $userCode   = strtoupper(trim($cancelData['userCode']));
            $password   = $cancelData['password'];

            $user = DB::table('users')
                ->whereRaw('UPPER(LTRIM(RTRIM(USER_CODE))) = ?', [$userCode])
                ->first();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'status'  => 'error',
                    'message' => 'User not found.',
                ], 422);
            }

            $userRow        = array_change_key_case((array) $user, CASE_LOWER);
            $storedPassword = (string) ($userRow['password'] ?? '');

            if (!$storedPassword || !Hash::check($password, $storedPassword)) {
                return response()->json([
                    'success' => false,
                    'status'  => 'error',
                    'message' => 'Invalid password.',
                ], 422);
            }

            // Password is used only for authorization and is never forwarded to SQL.
            unset($cancelData['password']);
            $cancelData['userCode'] = $userCode;

            $params = json_encode(
                ['json_data' => $cancelData],
                JSON_UNESCAPED_UNICODE
            );

            $results = DB::select(
                'EXEC sproc_PHP_MJO @mode = ?, @params = ?',
                ['Cancel', $params]
            );

            $row = $results[0] ?? null;

            $errorCount = (int) (
                $row->errorCount ??
                $row->errorcount ??
                0
            );

            $errorMessage = (string) (
                $row->errorMsg ??
                $row->errormsg ??
                ''
            );

            if ($errorCount > 0) {
                return response()->json([
                    'success' => false,
                    'status'  => 'error',
                    'message' => $errorMessage ?: 'Unable to cancel MJO.',
                    'data'    => $results,
                ], 422);
            }

            return response()->json([
                'success' => true,
                'status'  => 'success',
                'data'    => $results,
            ], 200);

        } catch (\Throwable $e) {
            Log::error('MJO Cancel failed', [
                'message' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'status'  => 'error',
                'message' => 'Error executing MJO Cancel.',
                'details' => $e->getMessage(),
            ], 500);
        }
    }


    public function history(Request $request)
    {
        $validated = $request->validate([
            'json_data' => 'required|array',
        ]);

        try {
            $params = json_encode(
                [
                    'json_data' =>
                        $validated['json_data'],
                ],
                JSON_UNESCAPED_UNICODE
            );

            $results = DB::select(
                'EXEC sproc_PHP_MJO @mode = ?, @params = ?',
                ['History', $params]
            );

            return response()->json([
                'success' => true,
                'status'  => 'success',
                'data'    => $results,
            ], 200);

        } catch (\Throwable $e) {
            Log::error('MJO History failed', [
                'message' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'status'  => 'error',
                'message' => 'Error retrieving MJO History.',
                'details' => $e->getMessage(),
            ], 500);
        }
    }


    public function vehicleByCustomer(Request $request)
    {
        try {
            $validated = $request->validate([
                'custCode' => 'required|string',
            ]);

            $params = json_encode([
                'json_data' => [
                    'custCode' => trim($validated['custCode']),
                ],
            ], JSON_UNESCAPED_UNICODE);

            $results = DB::select(
                'EXEC sproc_PHP_MJO @mode = ?, @params = ?',
                ['GetVehicleByCustomer', $params]
            );

            return response()->json([
                'success' => true,
                'data'    => $results,
            ], 200);

        } catch (\Throwable $e) {
            Log::error('MJO Vehicle Master by Customer failed', [
                'message' => $e->getMessage(),
                'request' => $request->all(),
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'data'    => [],
            ], 500);
        }
    }


    public function mode(Request $request)
    {
        $validated = $request->validate([
            'mode'      => 'required|string',
            'json_data' => 'nullable|array',
        ]);

        $allowedModes = [
            'Header',
            'Detail1',
            'Detail2',
            'OpenVSO',
            'GetTop1VJO',
            'GetPartsRequest',
            'GetVehicleByCustomer',
        ];

        if (!in_array($validated['mode'], $allowedModes, true)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid MJO mode.',
            ], 422);
        }

        try {
            $params = json_encode(
                [
                    'json_data' =>
                        $validated['json_data'] ?? [],
                ],
                JSON_UNESCAPED_UNICODE
            );

            $results = DB::select(
                'EXEC sproc_PHP_MJO @mode = ?, @params = ?',
                [
                    $validated['mode'],
                    $params,
                ]
            );

            return response()->json([
                'success' => true,
                'data'    => $results,
            ], 200);

        } catch (\Throwable $e) {
            Log::error('MJO mode failed', [
                'mode'    => $validated['mode'],
                'message' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
