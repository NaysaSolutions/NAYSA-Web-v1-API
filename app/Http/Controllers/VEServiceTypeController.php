<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class VEServiceTypeController extends Controller
{
    /**
     * Normalize json_data whether the frontend sends:
     *
     * 1. json_data as an object/array
     *    {
     *      "json_data": {
     *          "code": "PM",
     *          "description": "Preventive Maintenance",
     *          "active": "Y",
     *          "userCode": "ADMIN"
     *      }
     *    }
     *
     * 2. json_data as a JSON string
     *    {
     *      "json_data":
     *      "{\"json_data\":{\"code\":\"PM\",...}}"
     *    }
     */
    private function buildParams(Request $request): string
    {
        $jsonData = $request->input('json_data');

        /* =====================================================
           json_data sent as ARRAY / OBJECT
           ===================================================== */
        if (is_array($jsonData)) {
            return json_encode(
                [
                    'json_data' => $jsonData,
                ],
                JSON_UNESCAPED_UNICODE
            );
        }

        /* =====================================================
           json_data sent as JSON STRING
           ===================================================== */
        if (is_string($jsonData)) {
            $decoded = json_decode($jsonData, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \InvalidArgumentException(
                    'Invalid json_data format.'
                );
            }

            /*
             * Frontend may already send:
             *
             * {
             *   "json_data": {
             *      ...
             *   }
             * }
             */
            if (
                is_array($decoded) &&
                array_key_exists('json_data', $decoded)
            ) {
                return json_encode(
                    $decoded,
                    JSON_UNESCAPED_UNICODE
                );
            }

            /*
             * Or a direct object:
             *
             * {
             *   "code": "...",
             *   ...
             * }
             */
            if (is_array($decoded)) {
                return json_encode(
                    [
                        'json_data' => $decoded,
                    ],
                    JSON_UNESCAPED_UNICODE
                );
            }
        }

        /*
         * Fallback for direct request parameters.
         */
        return json_encode(
            [
                'json_data' => [
                    'code' => $request->input('code', ''),
                    'description' => $request->input('description', ''),
                    'active' => $request->input('active', 'Y'),
                    'userCode' => $request->input('userCode', ''),
                ],
            ],
            JSON_UNESCAPED_UNICODE
        );
    }


    /**
     * Convert the stored procedure result into a consistent response.
     */
    private function procedureResponse(
        array $rows,
        string $successMessage,
        string $failureMessage
    ) {
        $row = $rows[0] ?? null;

        $errorCount = (int) ($row->errorcount ?? 0);
        $errorMessage = trim(
            (string) ($row->errormsg ?? '')
        );

        return response()->json([
            'success' => $errorCount === 0,
            'message' => $errorMessage !== ''
                ? $errorMessage
                : (
                    $errorCount === 0
                        ? $successMessage
                        : $failureMessage
                ),
            'data' => $rows,
        ]);
    }


    /**
     * =========================================================
     * LOAD
     *
     * GET /api/veServiceType
     * =========================================================
     */
    public function index(Request $request)
    {
        try {
            $rows = DB::select(
                'EXEC sproc_PHP_VEServiceType @mode = ?',
                ['Load']
            );

            $result = $rows[0]->result ?? '[]';

            $data = json_decode(
                $result,
                true
            );

            if (!is_array($data)) {
                $data = [];
            }

            return response()->json([
                'success' => true,
                'ok' => true,
                'message' => 'Vehicle Service Types have been retrieved.',
                'data' => $data,
            ]);
        } catch (\Throwable $e) {
            Log::error(
                'VEServiceType Load Error',
                [
                    'message' => $e->getMessage(),
                ]
            );

            return response()->json([
                'success' => false,
                'ok' => false,
                'message' => 'Failed to retrieve Vehicle Service Types.',
                'data' => [],
            ], 500);
        }
    }


    /**
     * =========================================================
     * GET SINGLE RECORD
     *
     * GET /api/getVEServiceType?code=PM
     * =========================================================
     */
    public function get(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string',
        ]);

        try {
            $rows = DB::select(
                'EXEC sproc_PHP_VEServiceType @mode = ?, @params = ?',
                [
                    'Get',
                    strtoupper(
                        trim(
                            $validated['code']
                        )
                    ),
                ]
            );

            $result = $rows[0]->result ?? '[]';

            return response()->json([
                'success' => true,
                'data' => json_decode(
                    $result,
                    true
                ) ?? [],
            ]);
        } catch (\Throwable $e) {
            Log::error(
                'VEServiceType Get Error',
                [
                    'message' => $e->getMessage(),
                    'code' => $validated['code'],
                ]
            );

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve Vehicle Service Type.',
                'data' => [],
            ], 500);
        }
    }


    /**
     * =========================================================
     * LOOKUP
     *
     * GET /api/lookupVEServiceType
     * =========================================================
     */
    public function lookup(Request $request)
    {
        try {
            $rows = DB::select(
                'EXEC sproc_PHP_VEServiceType @mode = ?',
                ['Lookup']
            );

            $result = $rows[0]->result ?? '[]';

            return response()->json([
                'success' => true,
                'data' => json_decode(
                    $result,
                    true
                ) ?? [],
            ]);
        } catch (\Throwable $e) {
            Log::error(
                'VEServiceType Lookup Error',
                [
                    'message' => $e->getMessage(),
                ]
            );

            return response()->json([
                'success' => false,
                'message' => 'Failed to load Vehicle Service Type lookup.',
                'data' => [],
            ], 500);
        }
    }


    /**
     * =========================================================
     * UPSERT
     *
     * POST /api/upsertVEServiceType
     * =========================================================
     */
    public function upsert(Request $request)
    {
        try {
            $params = $this->buildParams(
                $request
            );

            $decoded = json_decode(
                $params,
                true
            );

            $data =
                $decoded['json_data']
                ?? [];

            $code = strtoupper(
                trim(
                    (string) (
                        $data['code']
                        ?? ''
                    )
                )
            );

            $description = trim(
                (string) (
                    $data['description']
                    ?? ''
                )
            );

            $active = strtoupper(
                trim(
                    (string) (
                        $data['active']
                        ?? 'Y'
                    )
                )
            );

            $userCode = trim(
                (string) (
                    $data['userCode']
                    ?? ''
                )
            );


            /* ================= REQUIRED VALIDATION ================= */

            $missing = [];

            if ($code === '') {
                $missing[] =
                    'Service Type Code';
            }

            if ($description === '') {
                $missing[] =
                    'Service Type Description';
            }

            if ($userCode === '') {
                $missing[] =
                    'User Code';
            }

            if (!empty($missing)) {
                return response()->json([
                    'success' => false,
                    'message' =>
                        'Please fill in the required field(s):'
                        . "\n- "
                        . implode(
                            "\n- ",
                            $missing
                        ),
                    'data' => [],
                ], 422);
            }


            if (!in_array(
                $active,
                ['Y', 'N'],
                true
            )) {
                return response()->json([
                    'success' => false,
                    'message' =>
                        'Active must be Y or N.',
                    'data' => [],
                ], 422);
            }


            /*
             * Rebuild normalized params before sending to SQL.
             */
            $params = json_encode(
                [
                    'json_data' => [
                        'code' => $code,
                        'description' => $description,
                        'active' => $active,
                        'userCode' => $userCode,
                    ],
                ],
                JSON_UNESCAPED_UNICODE
            );


            $rows = DB::select(
                'EXEC sproc_PHP_VEServiceType @mode = ?, @params = ?',
                [
                    'Upsert',
                    $params,
                ]
            );


            return $this->procedureResponse(
                $rows,
                'Vehicle Service Type saved successfully.',
                'Failed to save Vehicle Service Type.'
            );
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'data' => [],
            ], 422);
        } catch (\Throwable $e) {
            Log::error(
                'VEServiceType Upsert Error',
                [
                    'message' => $e->getMessage(),
                    'request' => $request->all(),
                ]
            );

            return response()->json([
                'success' => false,
                'message' => 'Failed to save Vehicle Service Type.',
                'data' => [],
            ], 500);
        }
    }


    /**
     * =========================================================
     * CHECK DUPLICATE
     *
     * POST /api/checkDuplicateVEServiceType
     * =========================================================
     */
    public function checkDuplicate(Request $request)
    {
        try {
            $params = $this->buildParams(
                $request
            );

            $decoded = json_decode(
                $params,
                true
            );

            $code = strtoupper(
                trim(
                    (string) (
                        $decoded['json_data']['code']
                        ?? ''
                    )
                )
            );

            if ($code === '') {
                return response()->json([
                    'success' => true,
                    'data' => [
                        (object) [
                            'result' => '0',
                        ],
                    ],
                ]);
            }


            $params = json_encode(
                [
                    'json_data' => [
                        'code' => $code,
                    ],
                ],
                JSON_UNESCAPED_UNICODE
            );


            $rows = DB::select(
                'EXEC sproc_PHP_VEServiceType @mode = ?, @params = ?',
                [
                    'CheckDuplicate',
                    $params,
                ]
            );


            return response()->json([
                'success' => true,
                'data' => $rows,
            ]);
        } catch (\Throwable $e) {
            Log::error(
                'VEServiceType Duplicate Check Error',
                [
                    'message' => $e->getMessage(),
                ]
            );

            return response()->json([
                'success' => false,
                'message' => 'Failed to check duplicate Vehicle Service Type.',
                'data' => [],
            ], 500);
        }
    }


    /**
     * =========================================================
     * DELETE
     *
     * POST /api/deleteVEServiceType
     * =========================================================
     */
    public function delete(Request $request)
    {
        try {
            $params = $this->buildParams(
                $request
            );

            $decoded = json_decode(
                $params,
                true
            );

            $data =
                $decoded['json_data']
                ?? [];

            $code = strtoupper(
                trim(
                    (string) (
                        $data['code']
                        ?? ''
                    )
                )
            );

            $userCode = trim(
                (string) (
                    $data['userCode']
                    ?? ''
                )
            );


            if ($code === '') {
                return response()->json([
                    'success' => false,
                    'message' => 'Service Type Code is required.',
                    'data' => [],
                ], 422);
            }


            $params = json_encode(
                [
                    'json_data' => [
                        'code' => $code,
                        'userCode' => $userCode,
                    ],
                ],
                JSON_UNESCAPED_UNICODE
            );


            $rows = DB::select(
                'EXEC sproc_PHP_VEServiceType @mode = ?, @params = ?',
                [
                    'Delete',
                    $params,
                ]
            );


            return $this->procedureResponse(
                $rows,
                'Vehicle Service Type deleted successfully.',
                'Failed to delete Vehicle Service Type.'
            );
        } catch (\Throwable $e) {
            Log::error(
                'VEServiceType Delete Error',
                [
                    'message' => $e->getMessage(),
                ]
            );

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete Vehicle Service Type.',
                'data' => [],
            ], 500);
        }
    }
}
