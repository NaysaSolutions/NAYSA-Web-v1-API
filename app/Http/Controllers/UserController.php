<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use App\Mail\TempPasswordMail;
use Throwable;

class UserController extends Controller
{


    public function get(Request $request)
    {


        $request->validate([
            'USER_CODE' => 'required|string',
        ]);

        $params = $request->input('USER_CODE');


        try {
            $results = DB::select(
                'EXEC sproc_PHP_Users @mode = ?, @params = ?',
                ['get', $params]
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

    public function lookup(Request $request)
    {
        try {
            $results = DB::select(
                'EXEC sproc_PHP_USERS @mode = ?',
                ['Lookup']
            );

            return response()->json([
                'success' => true,
                'data' => $results, // no more `json_encode`
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }




    public function approveAccount(Request $req)
    {
        $req->validate(['userCode' => 'required|string']);
        $userCode = trim($req->input('userCode'));
        $company = $req->header('X-Company-DB'); // ✅ tenant/db


        try {
            // Fetch user (for email/name) via sproc
            $row  = collect(DB::select("exec sproc_PHP_Users ?, ?", ['Get', $userCode]))->first();
            $user = ($row && isset($row->result)) ? (json_decode($row->result, true)[0] ?? null) : null;

            if (!$user || empty($user['emailAdd'])) {
                return response()->json(['status' => 'error', 'message' => 'User not found or email is missing.'], 422);
            }

            // Check if a password already exists (direct table read)
            $existingHash = DB::table('users')->where('user_code', $userCode)->value('password');
            $hasPassword  = is_string($existingHash) && strlen(trim($existingHash)) > 0;

            if ($hasPassword) {
                // ✅ Password already exists:
                // Do NOT generate temp, do NOT update tpword_date; just send reset link
                Mail::to($user['emailAdd'])->send(new TempPasswordMail(
                    'reset',                                      // purpose
                    $user['userName'] ?? $userCode,               // name
                    $userCode,                                    // userCode
                    null,
                    $company // ✅ add                                          // temp (none)
                ));

                return response()->json([
                    'status'  => 'success',
                    'message' => 'User already has a password. Sent a reset link asking to change the password.',
                ]);
            }

            // ❌ No existing password:
            // Generate temp password + hash and stamp tpword_date via sproc
            $temp = substr(str_shuffle('ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnpqrstuvwxyz23456789'), 0, 6);
            $hash = Hash::make($temp);


            DB::select("exec sproc_PHP_Users ?, ?", [
                'SetTempPassword',
                json_encode(['json_data' => [
                    'userCode'     => $userCode,
                    'passwordHash' => $hash,
                ]]),
            ]);

            // Email with temp password (purpose=release)
            Mail::to($user['emailAdd'])->send(new TempPasswordMail(
                'release',
                $user['userName'] ?? $userCode,
                $userCode,
                $temp,
                $company // ✅ add
            ));

            return response()->json([
                'status'  => 'success',
                'message' => 'Temporary password sent and tpword_date updated.',
            ]);
        } catch (Throwable $e) {
            report($e);
            return response()->json([
                'status'  => 'error',
                'message' => 'Approve/Release failed: ' . $e->getMessage(),
            ], 500);
        }
    }


    // /** Approve/Release account: set temp password + tpword_date and email link (purpose=release). */
    // public function approveAccount(Request $req)
    // {
    //     $req->validate(['userCode' => 'required|string']);
    //     $userCode = trim($req->input('userCode'));

    //     try {
    //         // Get user (plain string; sproc wraps to JSON internally for Get)
    //         $row  = collect(DB::select("exec sproc_PHP_Users ?, ?", ['Get', $userCode]))->first();
    //         $user = ($row && isset($row->result)) ? (json_decode($row->result, true)[0] ?? null) : null;

    //         if (!$user || empty($user['emailAdd'])) {
    //             return response()->json(['status' => 'error', 'message' => 'User not found or email is missing.'], 422);
    //         }

    //         // Generate temp password + hash
    //         $temp = bin2hex(random_bytes(4)) . strtoupper(chr(random_int(65,90))) . random_int(10,99);
    //         $hash = Hash::make($temp);

    //         // Update DB (tpword_date)
    //         DB::select("exec sproc_PHP_Users ?, ?", [
    //             'SetTempPassword',
    //             json_encode(['json_data' => [
    //                 'userCode'     => $userCode,
    //                 'passwordHash' => $hash,
    //             ]]),
    //         ]);

    //         // Email (same Blade) with purpose='release'
    //         Mail::to($user['emailAdd'])->send(new TempPasswordMail(
    //             'release',
    //             $user['userName'] ?? $userCode,
    //             $userCode,
    //             $temp    // only for release
    //         ));

    //         return response()->json([
    //             'status'  => 'success',
    //             'message' => 'Temporary password sent and tpword_date updated.',
    //         ]);
    //     } catch (Throwable $e) {
    //         report($e);
    //         return response()->json([
    //             'status'  => 'error',
    //             'message' => 'Release failed: ' . $e->getMessage(),
    //         ], 500);
    //     }
    // }

    //     public function changePassword(Request $req)
    // {
    //     $req->validate([
    //         'userCode'     => 'required|string',
    //         'newPassword'  => 'required|string|min:6',
    //     ]);

    //     $userCode = trim($req->input('userCode'));
    //     $hash     = Hash::make($req->input('newPassword'));

    //     // Update the password and stamp cpword_date
    //     DB::select(
    //         "exec sproc_PHP_Users ?, ?",
    //         [
    //             'SetChangedPassword',
    //             json_encode([
    //                 'json_data' => [
    //                     'userCode'     => $userCode,
    //                     'passwordHash' => $hash,
    //                 ],
    //             ]),
    //         ]
    //     );

    //     return response()->json([
    //         'status'  => 'success',
    //         'message' => 'Password updated and cpword_date recorded.'
    //     ]);
    // }

    /** Tokenless: send reset email with link to /change-password (purpose=reset). */
    public function requestPasswordReset(Request $req)
    {
        $req->validate(['userCode' => 'required|string']);
        $userCode = trim($req->input('userCode'));

        try {
            $row  = collect(DB::select("exec sproc_PHP_Users ?, ?", ['Get', $userCode]))->first();
            $user = ($row && isset($row->result)) ? (json_decode($row->result, true)[0] ?? null) : null;

            if (!$user || empty($user['emailAdd'])) {
                return response()->json(['status' => 'error', 'message' => 'User not found or email is missing.'], 422);
            }

            // Email (same Blade) with purpose='reset' (no temp)
            Mail::to($user['emailAdd'])->send(new TempPasswordMail(
                'reset',
                $user['userName'] ?? $userCode,
                $userCode,
                null
            ));

            return response()->json(['status' => 'success', 'message' => 'Password reset link sent.']);
        } catch (Throwable $e) {
            report($e);
            return response()->json(['status' => 'error', 'message' => 'Reset request failed: ' . $e->getMessage()], 500);
        }
    }



    /**
     * Change Password:
     * - Normal in-app change: requires oldPassword, validates, stamps cpword_date.
     * - From email link (tokenless): pass ?mode=reset or mode=release, oldPassword not required.
     */
    public function changePassword(Request $req)
    {
        // Accept optional 'mode' to allow tokenless email path without oldPassword
        $mode = $req->input('mode'); // 'reset' | 'release' | null

        // Validation: require oldPassword only if not coming from email mode
        $rules = [
            'userCode'    => 'required|string',
            'newPassword' => [
                'required',
                'string',
                'min:8',
                'regex:/[a-z]/',       // lowercase
                'regex:/[A-Z]/',       // uppercase
                'regex:/\d/',          // digit
                'regex:/[^A-Za-z0-9]/', // special char
                'not_regex:/\s/',      // no spaces
            ],
        ];
        if (!in_array($mode, ['reset', 'release'], true)) {
            $rules['oldPassword'] = 'required|string';
        }

        $validated  = $req->validate($rules, [
            'newPassword.min'      => 'Password must be at least 8 characters.',
            'newPassword.regex'    => 'Password must include lowercase, uppercase, number, and special character, with no spaces.',
            'oldPassword.required' => 'Old password is required.',
        ]);

        $userCode    = trim($validated['userCode']);
        $newPassword = $validated['newPassword'];
        $oldPassword = $req->input('oldPassword'); // may be null for reset/release

        // Load current hash + name to verify (sproc Get doesn’t return the hash)
        $row = DB::table('users')->select('password', 'user_name')->where('user_code', $userCode)->first();
        if (!$row) {
            return response()->json(['status' => 'error', 'message' => 'User not found.'], 422);
        }

        // If not email mode, verify old password
        if (!in_array($mode, ['reset', 'release'], true)) {
            if (!Hash::check($oldPassword, $row->password)) {
                return response()->json(['status' => 'error', 'message' => 'Old password is incorrect.'], 422);
            }
        }

        // Must differ from existing
        if (Hash::check($newPassword, $row->password)) {
            return response()->json(['status' => 'error', 'message' => 'New password must be different from the old password.'], 422);
        }
        // Avoid username==password
        if (strcasecmp($newPassword, $userCode) === 0) {
            return response()->json(['status' => 'error', 'message' => 'New password must not be the same as the username.'], 422);
        }

        // Hash new password
        $hash = Hash::make($newPassword);

        // Update via sproc (stamps cpword_date)
        DB::select("exec sproc_PHP_Users ?, ?", [
            'SetChangedPassword',
            json_encode(['json_data' => [
                'userCode'     => $userCode,
                'passwordHash' => $hash,
            ]]),
        ]);

        return response()->json([
            'status'  => 'success',
            'message' => 'Password updated and cpword_date recorded.',
        ]);
    }







    // public function load(Request $request) {

    //     $request->validate([
    //         'Status' => 'required|string',
    //     ]);

    //     $params = $request->input('Status');

    //     try {

    //         $results = DB::select(
    //             'EXEC sproc_PHP_Users @mode = ?, @params =?',
    //             ['load',$params] 
    //         );

    //         return response()->json([
    //             'success' => true,
    //             'data' => $results,
    //         ], 200);
    //     } catch (\Exception $e) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => $e->getMessage(),
    //         ], 500);
    //     }


    // }

    // public function load(Request $request)
    // {
    //     $filter = $request->query('Status', 'Active'); // "Active" | "Inactive" | etc.
    //     $rows = DB::select(
    //         'EXEC dbo.sproc_PHP_Users @mode = ?, @params = ?',
    //         ['Load', $filter]
    //     );
    //     return response()->json(['success' => true, 'data' => $rows]);
    // }

    public function load(Request $request)
    {
        // Base status: Active | Pending | Inactive
        $base = $request->query('Status', 'Active');

        // UserType: "ALL" (default) or a specific code like "R", "S", etc.
        $userType = strtoupper($request->query('UserType', 'ALL'));

        // Build the legacy-style @params string that your sproc already understands
        // e.g., "ActiveAll" to include all types, or "Active:R" to include only R
        $params = $userType === 'ALL' ? "{$base}All" : "{$base}:{$userType}";

        $rows = DB::select(
            'EXEC dbo.sproc_PHP_Users @mode = ?, @params = ?',
            ['Load', $params]
        );

        return response()->json(['success' => true, 'data' => $rows]);
    }




    public function upsert(Request $request)
    {

        // A) Grab raw JSON string as-is
        $json = $request->getContent(); // <- critical

        // Safety: if something turned it into an array already, re-encode
        if (!$json && $request->all()) {
            $json = json_encode($request->all(), JSON_UNESCAPED_UNICODE);
        }

        // Optional: verify we truly have JSON
        json_decode($json);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid JSON body sent to /upsert.',
            ], 422);
        }

        Log::info('Users.Upsert raw payload', ['json' => $json]);

        // B) Call sproc with the raw JSON string
        DB::statement(
            'EXEC dbo.sproc_PHP_Users @mode = ?, @params = ?',
            ['Upsert', $json]
        );

        return response()->json([
            'success' => true,
            'data' => ['status' => 'success'],
            'message' => 'User saved',
        ]);
    }
}
