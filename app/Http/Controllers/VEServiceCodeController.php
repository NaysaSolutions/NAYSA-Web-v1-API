<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class VEServiceCodeController extends Controller
{
    private const SPROC = 'sproc_PHP_VEServiceCode';

    /**
     * Normalize json_data whether sent as:
     * - array/object
     * - JSON string
     * - direct request values
     */
    private function buildParams(Request $request): string
    {
        $jsonData = $request->input('json_data');

        if (is_array($jsonData)) {
            return json_encode(
                [
                    'json_data' => $jsonData,
                ],
                JSON_UNESCAPED_UNICODE
            );
        }

        if (is_string($jsonData)) {
            $decoded = json_decode(
                $jsonData,
                true
            );

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \InvalidArgumentException(
                    'Invalid json_data format.'
                );
            }

            if (
                is_array($decoded) &&
                array_key_exists(
                    'json_data',
                    $decoded
                )
            ) {
                return json_encode(
                    $decoded,
                    JSON_UNESCAPED_UNICODE
                );
            }

            if (is_array($decoded)) {
                return json_encode(
                    [
                        'json_data' => $decoded,
                    ],
                    JSON_UNESCAPED_UNICODE
                );
            }
        }

        return json_encode(
            [
                'json_data' => [
                    'serviceCode' =>
                        $request->input(
                            'serviceCode',
                            $request->input(
                                'code',
                                ''
                            )
                        ),

                    'serviceDescription' =>
                        $request->input(
                            'serviceDescription',
                            $request->input(
                                'description',
                                ''
                            )
                        ),

                    'billCode' =>
                        $request->input(
                            'billCode',
                            ''
                        ),

                    'active' =>
                        $request->input(
                            'active',
                            'Y'
                        ),

                    'userCode' =>
                        $request->input(
                            'userCode',
                            ''
                        ),
                ],
            ],
            JSON_UNESCAPED_UNICODE
        );
    }


    /**
     * Standard stored procedure response.
     */
    private function procedureResponse(
        array $rows,
        string $successMessage,
        string $failureMessage
    ) {
        $row = $rows[0] ?? null;

        $errorCount = (int) (
            $row->errorcount ??
            0
        );

        $errorMessage = trim(
            (string) (
                $row->errormsg ??
                ''
            )
        );

        return response()->json([
            'success' =>
                $errorCount === 0,

            'message' =>
                $errorMessage !== ''
                    ? $errorMessage
                    : (
                        $errorCount === 0
                            ? $successMessage
                            : $failureMessage
                    ),

            'data' => $rows,
        ]);
    }

    public function index()
    {
        try {
            $rows = DB::select(
                'EXEC ' . self::SPROC . ' @mode = ?',
                ['Load']
            );

            $result =
                $rows[0]->result ??
                '[]';

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
                'message' =>
                    'Vehicle Service Codes have been retrieved.',
                'data' => $data,
            ]);
        } catch (\Throwable $e) {
            Log::error(
                'VEServiceCode Load Error',
                [
                    'message' =>
                        $e->getMessage(),
                ]
            );

            return response()->json([
                'success' => false,
                'ok' => false,
                'message' =>
                    $e->getMessage(),
                'data' => [],
            ], 500);
        }
    }

    public function get(Request $request)
    {
        $serviceCode = strtoupper(
            trim(
                (string) (
                    $request->query(
                        'serviceCode',
                        $request->query(
                            'code',
                            ''
                        )
                    )
                )
            )
        );

        if ($serviceCode === '') {
            return response()->json([
                'success' => false,
                'message' =>
                    'Service Code is required.',
                'data' => [],
            ], 422);
        }

        try {
            $rows = DB::select(
                'EXEC ' . self::SPROC . ' @mode = ?, @params = ?',
                [
                    'Get',
                    $serviceCode,
                ]
            );

            $result =
                $rows[0]->result ??
                '[]';

            return response()->json([
                'success' => true,
                'data' =>
                    json_decode(
                        $result,
                        true
                    ) ?? [],
            ]);
        } catch (\Throwable $e) {
            Log::error(
                'VEServiceCode Get Error',
                [
                    'message' =>
                        $e->getMessage(),

                    'serviceCode' =>
                        $serviceCode,
                ]
            );

            return response()->json([
                'success' => false,
                'message' =>
                    $e->getMessage(),
                'data' => [],
            ], 500);
        }
    }

    public function lookup()
    {
        try {
            $rows = DB::select(
                'EXEC ' . self::SPROC . ' @mode = ?',
                ['Lookup']
            );

            $result =
                $rows[0]->result ??
                '[]';

            return response()->json([
                'success' => true,
                'data' =>
                    json_decode(
                        $result,
                        true
                    ) ?? [],
            ]);
        } catch (\Throwable $e) {
            Log::error(
                'VEServiceCode Lookup Error',
                [
                    'message' =>
                        $e->getMessage(),
                ]
            );

            return response()->json([
                'success' => false,
                'message' =>
                    $e->getMessage(),
                'data' => [],
            ], 500);
        }
    }

    public function upsert(Request $request)
    {
        try {
            $params =
                $this->buildParams(
                    $request
                );

            $decoded = json_decode(
                $params,
                true
            );

            $data =
                $decoded['json_data']
                ?? [];

            $serviceCode = strtoupper(
                trim(
                    (string) (
                        $data['serviceCode']
                        ?? $data['code']
                        ?? ''
                    )
                )
            );

            $serviceDescription = trim(
                (string) (
                    $data['serviceDescription']
                    ?? $data['description']
                    ?? ''
                )
            );

            $billCode = strtoupper(
                trim(
                    (string) (
                        $data['billCode']
                        ?? ''
                    )
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


            $missing = [];

            if ($serviceCode === '') {
                $missing[] =
                    'Service Code';
            }

            if ($serviceDescription === '') {
                $missing[] =
                    'Service Description';
            }

            if ($billCode === '') {
                $missing[] =
                    'Bill Code';
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


            if (
                !in_array(
                    $active,
                    ['Y', 'N'],
                    true
                )
            ) {
                return response()->json([
                    'success' => false,
                    'message' =>
                        'Active must be Y or N.',
                    'data' => [],
                ], 422);
            }


            $params = json_encode(
                [
                    'json_data' => [
                        'serviceCode' =>
                            $serviceCode,

                        'serviceDescription' =>
                            $serviceDescription,

                        'billCode' =>
                            $billCode,

                        'active' =>
                            $active,

                        'userCode' =>
                            $userCode,
                    ],
                ],
                JSON_UNESCAPED_UNICODE
            );


            $rows = DB::select(
                'EXEC ' . self::SPROC . ' @mode = ?, @params = ?',
                [
                    'Upsert',
                    $params,
                ]
            );


            return $this->procedureResponse(
                $rows,
                'Vehicle Service Code saved successfully.',
                'Failed to save Vehicle Service Code.'
            );
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' =>
                    $e->getMessage(),
                'data' => [],
            ], 422);
        } catch (\Throwable $e) {
            Log::error(
                'VEServiceCode Upsert Error',
                [
                    'message' =>
                        $e->getMessage(),

                    'request' =>
                        $request->all(),
                ]
            );

            return response()->json([
                'success' => false,
                'message' =>
                    $e->getMessage(),
                'data' => [],
            ], 500);
        }
    }

    public function checkDuplicate(
        Request $request
    ) {
        try {
            $params =
                $this->buildParams(
                    $request
                );

            $decoded =
                json_decode(
                    $params,
                    true
                );

            $serviceCode =
                strtoupper(
                    trim(
                        (string) (
                            $decoded['json_data']['serviceCode']
                            ?? $decoded['json_data']['code']
                            ?? ''
                        )
                    )
                );


            if ($serviceCode === '') {
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
                        'serviceCode' =>
                            $serviceCode,
                    ],
                ],
                JSON_UNESCAPED_UNICODE
            );


            $rows = DB::select(
                'EXEC ' . self::SPROC . ' @mode = ?, @params = ?',
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
                'VEServiceCode Duplicate Check Error',
                [
                    'message' =>
                        $e->getMessage(),
                ]
            );

            return response()->json([
                'success' => false,
                'message' =>
                    $e->getMessage(),
                'data' => [],
            ], 500);
        }
    }


    /**
     * =========================================================
     * CHECK IN USED
     *
     * POST /api/checkVEServiceCodeInUsed
     * =========================================================
     */
    public function checkInUsed(
        Request $request
    ) {
        try {
            $params =
                $this->buildParams(
                    $request
                );

            $decoded =
                json_decode(
                    $params,
                    true
                );

            $serviceCode =
                strtoupper(
                    trim(
                        (string) (
                            $decoded['json_data']['serviceCode']
                            ?? $decoded['json_data']['code']
                            ?? ''
                        )
                    )
                );


            $params = json_encode(
                [
                    'json_data' => [
                        'serviceCode' =>
                            $serviceCode,
                    ],
                ],
                JSON_UNESCAPED_UNICODE
            );


            $rows = DB::select(
                'EXEC ' . self::SPROC . ' @mode = ?, @params = ?',
                [
                    'CheckInUsed',
                    $params,
                ]
            );


            return response()->json([
                'success' => true,
                'data' => $rows,
            ]);
        } catch (\Throwable $e) {
            Log::error(
                'VEServiceCode Check In Used Error',
                [
                    'message' =>
                        $e->getMessage(),
                ]
            );

            return response()->json([
                'success' => false,
                'message' =>
                    $e->getMessage(),
                'data' => [],
            ], 500);
        }
    }

    public function delete(
        Request $request
    ) {
        try {
            $params =
                $this->buildParams(
                    $request
                );

            $decoded =
                json_decode(
                    $params,
                    true
                );

            $data =
                $decoded['json_data']
                ?? [];

            $serviceCode =
                strtoupper(
                    trim(
                        (string) (
                            $data['serviceCode']
                            ?? $data['code']
                            ?? ''
                        )
                    )
                );

            $userCode =
                trim(
                    (string) (
                        $data['userCode']
                        ?? ''
                    )
                );


            if ($serviceCode === '') {
                return response()->json([
                    'success' => false,
                    'message' =>
                        'Service Code is required.',
                    'data' => [],
                ], 422);
            }


            $params = json_encode(
                [
                    'json_data' => [
                        'serviceCode' =>
                            $serviceCode,

                        'userCode' =>
                            $userCode,
                    ],
                ],
                JSON_UNESCAPED_UNICODE
            );


            $rows = DB::select(
                'EXEC ' . self::SPROC . ' @mode = ?, @params = ?',
                [
                    'Delete',
                    $params,
                ]
            );


            return $this->procedureResponse(
                $rows,
                'Vehicle Service Code deleted successfully.',
                'Failed to delete Vehicle Service Code.'
            );
        } catch (\Throwable $e) {
            Log::error(
                'VEServiceCode Delete Error',
                [
                    'message' =>
                        $e->getMessage(),
                ]
            );

            return response()->json([
                'success' => false,
                'message' =>
                    $e->getMessage(),
                'data' => [],
            ], 500);
        }
    }
}
