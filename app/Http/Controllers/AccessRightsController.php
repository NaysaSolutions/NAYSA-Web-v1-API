<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;


class AccessRightsController extends Controller
{


    public function upsertRole(Request $request)
    {
        try {
            $request->validate([
                'json_data' => 'required|array',
            ]);

            $jsonData = $request->get('json_data');
            $params = json_encode($jsonData);


            DB::statement(
                'EXEC sproc_PHP_AccessRights @params = ?, @mode = ?',
                [$params, 'UpsertRole']
            );


            return response()->json([
                'success' => true,
                'data'    => ['status' => 'success'],
                'message' => 'Role saved successfully.',
            ], 200);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'data'    => ['status' => 'error'],
                'message' => 'Error executing Role Upsert.',
                'details' => $e->getMessage(),
            ], 500);
        }
    }




    public function loadRole(Request $request)
    {

        try {

            $results = DB::select(
                'EXEC sproc_PHP_AccessRights @mode = ?',
                ['loadRole']
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



    public function deleteRole(Request $request)
    {

        $request->validate([
            'ROLE_CODE' => 'required|string',
        ]);

        $params = $request->input('ROLE_CODE');

        try {
            $results = DB::select(
                'EXEC sproc_PHP_AccessRights @mode = ?, @params = ?',
                ['DeleteRole', $params]
            );

            return response()->json([
                'success' => true,
                'data'    => ['status' => 'success'],
                'message' => 'Role deleted successfully.',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }


    public function deleteUserRole(Request $request)
    {
        $payload = $request->input('json_data.json_data'); // same as Upsert
        $dt1 = $payload['dt1'] ?? []; // roles
        $dt2 = $payload['dt2'] ?? []; // users

        foreach ($dt1 as $r) {
            foreach ($dt2 as $u) {
                DB::table('USERROLE_REF')
                    ->where('user_code', $u['userCode'])
                    ->where('role_code', $r['roleCode'])
                    ->delete();
            }
        }

        return response()->json(['data' => ['status' => 'success']]);
    }




    public function getRole(Request $request)
    {

        $request->validate([
            'ROLE_CODE' => 'required|string',
        ]);

        $params = $request->input('ROLE_CODE');

        try {
            $results = DB::select(
                'EXEC sproc_PHP_AccessRights @mode = ?, @params = ?',
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


    public function load(Request $request)
    {
        try {
            $results = DB::select(
                'EXEC sproc_PHP_Users @mode = ?',
                ['getUsers']
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


    public function getRoleMenu(Request $request)
    {

        try {

            $request->validate([
                'ROLE_CODE' => 'required|string',
            ]);
            $params = $request->input('ROLE_CODE');



            $results = DB::select(
                'EXEC sproc_PHP_AccessRights @mode = ?, @params = ?',
                ['getRoleMenu', $params]
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


    public function getUserRoles(Request $request)
    {
        try {
            // Check if specific users are requested via query params
            $userCodesParam = $request->query('userCodes');
            $userCodes = $userCodesParam ? explode(',', $userCodesParam) : [];

            Log::info('getUserRoles called', [
                'userCodesParam' => $userCodesParam,
                'userCodes' => $userCodes
            ]);

            $rows = [];
            if (!empty($userCodes)) {
                // Filter by specific users using direct table query
                $rows = DB::table('USERROLE_REF')
                    ->select(['user_code as userCode', 'role_code as roleCode'])
                    ->whereIn('user_code', $userCodes)
                    ->get()
                    ->toArray();
            } else {
                // Get all user roles
                $rows = DB::table('USERROLE_REF')
                    ->select(['user_code as userCode', 'role_code as roleCode'])
                    ->get()
                    ->toArray();
            }

            Log::info('getUserRoles query result', ['count' => count($rows)]);

            // Convert objects to arrays for consistent format
            $data = array_map(function ($r) {
                $a = (array) $r;
                return [
                    'userCode' => $a['userCode'] ?? $a['USER_CODE'] ?? $a['user_code'] ?? null,
                    'roleCode' => $a['roleCode'] ?? $a['ROLE_CODE'] ?? $a['role_code'] ?? null,
                ];
            }, $rows);

            return response()->json([
                'success' => true,
                'data'    => $data,
            ], 200);
        } catch (\Throwable $e) {
            Log::error('getUserRoles error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }









    // public function upsertRoleMenu(Request $request)
    // {
    //         $validated = $request->validate([
    //             'json_data' => 'required|array'
    //         ]);

    //         try {
    //             $params = json_encode(['json_data' => $validated['json_data']]);
    //             $mode = 'UpsertRoleMenu';

    //             // Call the stored procedure
    //             $result = DB::select('EXEC sproc_PHP_AccessRights @mode = ?, @params = ?', [
    //                 $mode,
    //                 $params
    //             ]);

    //             return response()->json([
    //                 'status' => 'success',
    //                 'data' => $result
    //             ], 200);
    //         } catch (\Throwable $e) {
    //             return response()->json([
    //                 'status' => 'error',
    //                 'message' => 'Error executing Role Menu Upsert.',
    //                 'details' => $e->getMessage()
    //             ], 500);
    //         }
    // }

    public function upsertRoleMenu(Request $request)
    {
        // Accept either uppercase query key or json_data roleCode
        $request->validate([
            'json_data.roleCode' => 'required_without:ROLE_CODE|string',
            'ROLE_CODE'          => 'required_without:json_data.roleCode|string',
            'json_data.dt1'      => 'array', // allow empty to mean "remove all"
        ]);

        // Normalize roleCode and dt1 into the JSON envelope the sproc expects
        $roleCode = $request->input('json_data.roleCode') ?? $request->input('ROLE_CODE');
        $dt1      = $request->input('json_data.dt1', []); // may be []

        $params = json_encode([
            'json_data' => [
                'roleCode' => $roleCode,
                'dt1'      => $dt1,          // array of { menuCode }, can be empty
            ]
        ], JSON_UNESCAPED_UNICODE);

        try {
            DB::statement(
                'EXEC sproc_PHP_AccessRights @params = ?, @mode = ?',
                [$params, 'UpsertRoleMenu']
            );

            return response()->json([
                'success' => true,
                'data'    => ['status' => 'success'],
                'message' => 'Role menu saved.',
            ], 200);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error executing Role Menu Upsert.',
                'details' => $e->getMessage(),
            ], 500);
        }
    }






    public function UpsertUserRole(Request $request)
    {
        try {
            $request->validate([
                'json_data' => 'required|array',
            ]);

            $jsonData = $request->get('json_data');
            $params = json_encode($jsonData);


            DB::statement(
                'EXEC sproc_PHP_AccessRights @params = ?, @mode = ?',
                [$params, 'UpsertUserRole']
            );


            return response()->json([
                'success' => true,
                'data'    => ['status' => 'success'],
                'message' => 'User Role saved successfully.',
            ], 200);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'data'    => ['status' => 'error'],
                'message' => 'Error executing User Role.',
                'details' => $e->getMessage(),
            ], 500);
        }
    }

    public function upsert(Request $request)
    {
        try {
            $request->validate([
                'json_data' => 'required|array',
            ]);

            $jsonData = $request->get('json_data');
            $params = json_encode($jsonData);

            DB::statement(
                'EXEC sproc_PHP_Users @params = ?, @mode = ?',
                [$params, 'upsert']
            );

            return response()->json([
                'success' => true,
                'data'    => ['status' => 'success'],
                'message' => 'User saved successfully.',
            ], 200);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'data'    => ['status' => 'error'],
                'message' => 'Error executing User Upsert.',
                'details' => $e->getMessage(),
            ], 500);
        }
    }
}
