<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use JsonException;
use Throwable;

class SalesInqController extends Controller
{
    public function getSalesLifecycleTracker(Request $request)
    {
        return $this->executeSalesInquiry('SalesLifecycleTracker', $request);
    }

    public function getSalesItemFlowTracker(Request $request)
    {
        return $this->executeSalesInquiry('SalesItemFlowTracker', $request);
    }

    public function getSalesAgingAnalysis(Request $request)
    {
        return $this->executeSalesInquiry('SalesAgingAnalysis', $request);
    }

    public function getSalesPerformanceAnalysis(Request $request)
    {
        return $this->executeSalesInquiry('SalesPerformanceAnalysis', $request);
    }

    public function getSalesCollectionAnalysis(Request $request)
    {
        return $this->executeSalesInquiry('SalesCollectionAnalysis', $request);
    }

    public function getSalesTrackerSummary(Request $request)
    {
        return $this->executeSalesInquiry('SalesLifecycleTracker', $request);
    }

    public function getSalesTrackerDelivery(Request $request)
    {
        return $this->executeSalesInquiry('Delivery', $request);
    }

    public function getSalesTrackerInvoices(Request $request)
    {
        return $this->executeSalesInquiry('Invoices', $request);
    }

    public function getSalesTrackerSettlements(Request $request)
    {
        return $this->executeSalesInquiry('Settlements', $request);
    }

    public function getSalesTrackerTimeline(Request $request)
    {
        return $this->executeSalesInquiry('Timeline', $request);
    }

    public function getSalesTrackerDetails(Request $request)
    {
        return $this->executeSalesInquiry('GetDocumentDetails', $request);
    }

    private function executeSalesInquiry(string $mode, Request $request)
    {
        $payload = $request->all();

        if (!array_key_exists('json_data', $payload)) {
            $payload = ['json_data' => $payload];
        }

        $jsonString = json_encode($payload);

        try {
            $results = DB::select(
                'exec sproc_PHP_Sales_Inq @mode = ?, @params = ?',
                [$mode, $jsonString]
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




public function iesPosting(Request $request)
{
    try {
        $row = DB::selectOne(
            'EXEC dbo.sproc_PHP_Sales_Inq @mode = ?',
            ['iesposting']
        );

        if (!$row) {
            return response()->json([
                'success' => false,
                'message' => 'No result was returned by EIS Posting.',
            ], 500);
        }

        $resultJson = $row->result ?? null;

        $data = [];

        if (is_string($resultJson) && trim($resultJson) !== '') {
            $data = json_decode(
                $resultJson,
                true,
                512,
                JSON_THROW_ON_ERROR
            );
        }

        return response()->json([
            'success' => true,
            'data' => $data,
        ], 200);

    } catch (\Throwable $e) {
        report($e);

        return response()->json([
            'success' => false,
            'message' => app()->environment('local')
                ? $e->getMessage()
                : 'Unable to retrieve the EIS Posting list.',
        ], 500);
    }
}




public function iesHistory(Request $request)
{
     $jsonString = $request->input('json_data');

    try {
        $results = DB::select(
            'EXEC dbo.sproc_PHP_Sales_Inq @mode = ?, @params = ?',
            ['ieshistory', $jsonString]
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





public function invoiceEmailing(Request $request)
{
    /*
     * Keep Laravel validation outside try/catch.
     * Validation errors will return HTTP 422 normally.
     */
    $validated = $request->validate([
        /*
         * No user ID or password is required in this controller payload.
         * Access is controlled by the route middleware/session.
         */
        'json_data' => [
            'required',
            'array',
        ],

        'json_data.requestId' => [
            'nullable',
            'string',
            'max:100',
        ],

        'json_data.dt1' => [
            'required',
            'array',
            'min:1',
        ],

        'json_data.dt1.*.documentType' => [
            'required',
            'string',
            'in:SI,SVI,ARCM,ARDM',
        ],

        'json_data.dt1.*.branchCode' => [
            'required',
            'string',
            'max:10',
        ],

        'json_data.dt1.*.groupId' => [
            'required',
            'string',
            'max:40',
        ],
    ]);

    try {
        /*
         * Resolve the current user from the authenticated request/session.
         * The request body does not need to carry userCode or userPassword.
         */
        $authenticatedUser = $request->user();

        $userCode = trim((string) (
            data_get($authenticatedUser, 'userCode')
            ?: data_get($authenticatedUser, 'user_code')
            ?: data_get($authenticatedUser, 'email')
            ?: 'SYSTEM'
        ));

        /*
         * Build the exact @params structure expected by:
         * dbo.sproc_PHP_InvoiceEmailing
         *
         * {
         *   "json_data": {
         *      "userCode": "...",
         *      "requestId": "...",
         *      "dt1": [...]
         *   }
         * }
         */
        $params = json_encode(
            [
                'json_data' => $validated['json_data'],
            ],
            JSON_UNESCAPED_UNICODE
            | JSON_UNESCAPED_SLASHES
            | JSON_THROW_ON_ERROR
        );

        /*
         * Confirm which database Laravel is actually using.
         */
        $databaseInfo = DB::selectOne(
            'select db_name() as databaseName'
        );

        $databaseName = $databaseInfo->databaseName ?? null;

        /*
         * Use DB::select instead of selectOne so we can inspect
         * the exact number of result rows returned by SQL Server.
         */
        $rows = DB::select(
            '
                exec dbo.sproc_PHP_InvoiceEmailing
                    @tranId = ?,
                    @mode = ?,
                    @userCode = ?,
                    @params = ?
            ',
            [
                null,
                'GeneratePayload',
                $userCode,
                $params,
            ]
        );

        $rowCount = count($rows);
        $row = $rows[0] ?? null;

        if ($row === null) {
            Log::error(
                'Invoice Emailing stored procedure returned no result row.',
                [
                    'databaseName' => $databaseName,
                    'userCode' => $userCode,
                    'rowCount' => $rowCount,
                    'selectedDocumentCount' => count(
                        $validated['json_data']['dt1']
                    ),
                ]
            );

            return response()->json([
                'success' => false,
                'message' =>
                    'The Invoice Emailing stored procedure returned no result.',
                'diagnostic' => [
                    'databaseName' => $databaseName,
                    'rowCount' => $rowCount,
                    'selectedDocumentCount' => count(
                        $validated['json_data']['dt1']
                    ),
                ],
            ], 500);
        }

        /*
         * SQL Server/ODBC can change the casing of column names.
         * Normalize all returned keys to lowercase.
         */
        $procedureResult = array_change_key_case(
            (array) $row,
            CASE_LOWER
        );

        $returnedColumns = array_keys($procedureResult);

        /*
         * Helper for SQL Server values.
         *
         * nvarchar(max) may sometimes arrive as:
         * - string
         * - resource/stream
         * - stringable object
         */
        $normalizeSqlValue = static function ($value): string {
            if ($value === null) {
                return '';
            }

            if (is_resource($value)) {
                $streamValue = stream_get_contents($value);

                return is_string($streamValue)
                    ? $streamValue
                    : '';
            }

            if (
                is_object($value)
                && method_exists($value, '__toString')
            ) {
                return (string) $value;
            }

            if (
                is_string($value)
                || is_numeric($value)
                || is_bool($value)
            ) {
                return (string) $value;
            }

            return '';
        };

        /*
         * Read controlled stored-procedure status.
         */
        $errorCount = (int) (
            $procedureResult['errorcount'] ?? 0
        );

        $errorMessage = trim(
            $normalizeSqlValue(
                $procedureResult['errormsg'] ?? null
            )
        );

        $procedureRequestId = trim(
            $normalizeSqlValue(
                $procedureResult['requestid'] ?? null
            )
        );

        $procedureDocumentCount = (int) (
            $procedureResult['documentcount'] ?? 0
        );

        /*
         * Read result before converting it so we can record
         * the actual PHP/ODBC type for diagnostics.
         */
        $rawResultValue =
            $procedureResult['result'] ?? null;

        $resultOriginalType = gettype(
            $rawResultValue
        );

        $resultWasResource = is_resource(
            $rawResultValue
        );

        $payloadJson = $normalizeSqlValue(
            $rawResultValue
        );

        /*
         * Remove UTF-8 BOM and surrounding whitespace.
         */
        $payloadJson = ltrim(
            $payloadJson,
            "\xEF\xBB\xBF \t\n\r\0\x0B"
        );

        /*
         * The stored procedure catches its own SQL errors.
         * When that happens:
         *
         * errorCount = 1
         * errorMsg   = actual SQL error
         * result     = NULL
         */
        if ($errorCount > 0 || $errorMessage !== '') {
            Log::warning(
                'Invoice Emailing payload generation failed.',
                [
                    'databaseName' => $databaseName,
                    'userCode' => $userCode,
                    'requestId' =>
                        $procedureRequestId !== ''
                            ? $procedureRequestId
                            : data_get(
                                $validated,
                                'json_data.requestId'
                            ),
                    'errorCount' => $errorCount,
                    'errorMessage' => $errorMessage,
                    'documentCount' =>
                        $procedureDocumentCount,
                    'returnedColumns' =>
                        $returnedColumns,
                ]
            );

            return response()->json([
                'success' => false,
                'message' =>
                    $errorMessage !== ''
                        ? $errorMessage
                        : 'Unable to generate the Invoice Emailing payload.',
                'requestId' =>
                    $procedureRequestId !== ''
                        ? $procedureRequestId
                        : null,
                'documentCount' =>
                    $procedureDocumentCount,
                'diagnostic' => [
                    'databaseName' => $databaseName,
                    'rowCount' => $rowCount,
                    'returnedColumns' => $returnedColumns,
                    'errorCount' => $errorCount,
                    'resultOriginalType' =>
                        $resultOriginalType,
                    'resultWasResource' =>
                        $resultWasResource,
                    'resultLength' =>
                        strlen($payloadJson),
                ],
            ], 422);
        }

        /*
         * SQL reported success but result is empty.
         * Return detailed diagnostics.
         */
        if ($payloadJson === '') {
            Log::error(
                'Invoice Emailing procedure returned an empty payload.',
                [
                    'databaseName' => $databaseName,
                    'userCode' => $userCode,
                    'rowCount' => $rowCount,
                    'returnedColumns' =>
                        $returnedColumns,
                    'errorCount' => $errorCount,
                    'errorMessage' => $errorMessage,
                    'requestId' =>
                        $procedureRequestId,
                    'documentCount' =>
                        $procedureDocumentCount,
                    'resultOriginalType' =>
                        $resultOriginalType,
                    'resultWasResource' =>
                        $resultWasResource,
                    'selectedDocumentCount' => count(
                        $validated['json_data']['dt1']
                    ),
                ]
            );

            return response()->json([
                'success' => false,
                'message' =>
                    'The generated Invoice Emailing payload is empty.',
                'requestId' =>
                    $procedureRequestId !== ''
                        ? $procedureRequestId
                        : null,
                'documentCount' =>
                    $procedureDocumentCount,
                'diagnostic' => [
                    'databaseName' => $databaseName,
                    'rowCount' => $rowCount,
                    'returnedColumns' =>
                        $returnedColumns,
                    'errorCount' => $errorCount,
                    'errorMsg' => $errorMessage,
                    'resultOriginalType' =>
                        $resultOriginalType,
                    'resultWasResource' =>
                        $resultWasResource,
                    'resultIsNull' =>
                        $rawResultValue === null,
                    'resultLength' => 0,
                    'selectedDocumentCount' => count(
                        $validated['json_data']['dt1']
                    ),
                ],
            ], 500);
        }

        /*
         * Decode generated SQL JSON.
         */
        $payload = json_decode(
            $payloadJson,
            true,
            512,
            JSON_THROW_ON_ERROR
        );

        if (!is_array($payload)) {
            return response()->json([
                'success' => false,
                'message' =>
                    'The generated Invoice Emailing payload has an invalid structure.',
                'diagnostic' => [
                    'databaseName' => $databaseName,
                    'resultLength' =>
                        strlen($payloadJson),
                ],
            ], 500);
        }

        /*
         * Validate generated document array.
         */
        $documents = $payload['documents'] ?? null;

        if (
            !is_array($documents)
            || count($documents) === 0
        ) {
            return response()->json([
                'success' => false,
                'message' =>
                    'The generated Invoice Emailing payload contains no documents.',
                'requestId' =>
                    $payload['requestId']
                    ?? (
                        $procedureRequestId !== ''
                            ? $procedureRequestId
                            : null
                    ),
                'documentCount' => 0,
                'diagnostic' => [
                    'databaseName' => $databaseName,
                    'resultLength' =>
                        strlen($payloadJson),
                    'resultIsValidJson' => true,
                    'payloadKeys' =>
                        array_keys($payload),
                ],
            ], 422);
        }

        $requestId = trim((string) (
            $payload['requestId']
            ?? $procedureRequestId
        ));

        $documentCount = (int) (
            $payload['documentCount']
            ?? count($documents)
        );

        /*
         * Read ESRS destination from config/services.php.
         */
        $apiUrl = trim((string) config(
            'services.esrs_invoice_emailing.url',
            ''
        ));

        $clientId = trim((string) config(
            'services.esrs_invoice_emailing.client_id',
            'NAYSA_CLOUD'
        ));

        $apiKey = trim((string) config(
            'services.esrs_invoice_emailing.api_key',
            ''
        ));

        $timeout = (int) config(
            'services.esrs_invoice_emailing.timeout',
            180
        );

        if ($timeout <= 0) {
            $timeout = 180;
        }

        $verifySslValue = config(
            'services.esrs_invoice_emailing.verify_ssl',
            true
        );

        $verifySsl = filter_var(
            $verifySslValue,
            FILTER_VALIDATE_BOOLEAN,
            FILTER_NULL_ON_FAILURE
        );

        if ($verifySsl === null) {
            $verifySsl = true;
        }

        if ($apiUrl === '') {
            Log::error(
                'ESRS Invoice Emailing URL is missing.',
                [
                    'requestId' => $requestId,
                    'documentCount' => $documentCount,
                ]
            );

            return response()->json([
                'success' => false,
                'message' =>
                    'ESRS Invoice Emailing URL is not configured.',
                'requestId' => $requestId,
                'documentCount' => $documentCount,
            ], 500);
        }

        if ($apiKey === '') {
            Log::error(
                'ESRS Invoice Emailing API key is missing.',
                [
                    'requestId' => $requestId,
                    'documentCount' => $documentCount,
                ]
            );

            return response()->json([
                'success' => false,
                'message' =>
                    'ESRS Invoice Emailing API key is not configured.',
                'requestId' => $requestId,
                'documentCount' => $documentCount,
            ], 500);
        }

        /*
         * Build HTTP headers.
         * Never include the API key in logs or responses.
         */
        $headers = [
            'X-ESRS-API-KEY' => $apiKey,
        ];

        if ($clientId !== '') {
            $headers['X-ESRS-CLIENT-ID'] =
                $clientId;
        }

        if ($requestId !== '') {
            $headers['X-REQUEST-ID'] =
                $requestId;
        }

        /*
         * Log only the payload structure, never the API key or full
         * customer/invoice payload.
         */
        Log::info(
            'Invoice Emailing ESRS outbound payload prepared.',
            [
                'databaseName' => $databaseName,
                'apiUrl' => $apiUrl,
                'requestId' => $requestId,
                'companyCode' => $payload['companyCode'] ?? null,
                'documentCount' => $documentCount,
                'documentsCount' => count($documents),
                'payloadKeys' => array_keys($payload),
                'payloadJsonLength' => strlen(
                    json_encode(
                        $payload,
                        JSON_UNESCAPED_UNICODE
                        | JSON_UNESCAPED_SLASHES
                    )
                ),
            ]
        );

        /*
         * Submit the decoded stored-procedure result directly as
         * the ESRS JSON request body. Do not wrap it in payload or
         * json_data.
         */
        $esrsResponse = Http::acceptJson()
            ->asJson()
            ->timeout($timeout)
            ->withOptions([
                'verify' => $verifySsl,
            ])
            ->withHeaders($headers)
            ->post(
                $apiUrl,
                $payload
            );

        $esrsStatusCode =
            $esrsResponse->status();

        $esrsResponseBody = trim(
            $esrsResponse->body()
        );

        $esrsResponseData =
            $esrsResponse->json();

        if (!is_array($esrsResponseData)) {
            $esrsResponseData = [];
        }

        /*
         * The ESRS integration response stores the accepted row at:
         * rows[0]
         *
         * Example:
         * {
         *   "success": true,
         *   "rows": [
         *      {
         *          "success": "1",
         *          "status": "ACCEPTED",
         *          "message": "...",
         *          "requestId": "...",
         *          "batchId": "..."
         *      }
         *   ]
         * }
         */
        $integrationRow = data_get(
            $esrsResponseData,
            'rows.0',
            []
        );

        if (!is_array($integrationRow)) {
            $integrationRow = [];
        }

        $normalizeBoolean = static function (
            $value,
            ?bool $default = null
        ): ?bool {
            if (is_bool($value)) {
                return $value;
            }

            if (is_int($value) || is_float($value)) {
                return ((int) $value) !== 0;
            }

            if (is_string($value)) {
                $normalized = strtolower(trim($value));

                if (in_array(
                    $normalized,
                    ['1', 'true', 'yes', 'y'],
                    true
                )) {
                    return true;
                }

                if (in_array(
                    $normalized,
                    ['0', 'false', 'no', 'n', ''],
                    true
                )) {
                    return false;
                }
            }

            return $default;
        };

        $topLevelSuccess = $normalizeBoolean(
            data_get(
                $esrsResponseData,
                'success'
            )
        );

        $rowSuccess = $normalizeBoolean(
            data_get(
                $integrationRow,
                'success'
            )
        );

        $remoteStatus = strtoupper(
            trim((string) (
                data_get(
                    $integrationRow,
                    'status'
                )
                ?? data_get(
                    $esrsResponseData,
                    'status'
                )
                ?? ''
            ))
        );

        /*
         * Read the actual ESRS message from rows[0] first.
         */
        $messageCandidates = [
            data_get(
                $integrationRow,
                'message'
            ),
            data_get(
                $esrsResponseData,
                'message'
            ),
            data_get(
                $esrsResponseData,
                'error'
            ),
            data_get(
                $esrsResponseData,
                'data.message'
            ),
            data_get(
                $esrsResponseData,
                'result.message'
            ),
            data_get(
                $esrsResponseData,
                'errors.0'
            ),
        ];

        $esrsMessage = '';

        foreach ($messageCandidates as $candidate) {
            if (
                is_string($candidate)
                || is_numeric($candidate)
            ) {
                $candidate = trim(
                    (string) $candidate
                );

                if ($candidate !== '') {
                    $esrsMessage = $candidate;
                    break;
                }
            }
        }

        /*
         * Use the raw response only when ESRS did not return a
         * structured message.
         */
        if (
            $esrsMessage === ''
            && $esrsResponseBody !== ''
        ) {
            $plainResponse = trim(
                strip_tags($esrsResponseBody)
            );

            if ($plainResponse !== '') {
                $esrsMessage = substr(
                    $plainResponse,
                    0,
                    1000
                );
            }
        }

        $failureStatuses = [
            'ERROR',
            'FAILED',
            'REJECTED',
            'INVALID',
        ];

        $remoteReportedFailure =
            $topLevelSuccess === false
            || $rowSuccess === false
            || in_array(
                $remoteStatus,
                $failureStatuses,
                true
            );

        if (
            !$esrsResponse->successful()
            || $remoteReportedFailure
        ) {
            Log::error(
                'ESRS Invoice Emailing submission failed.',
                [
                    'databaseName' => $databaseName,
                    'requestId' => $requestId,
                    'documentCount' => $documentCount,
                    'esrsStatusCode' =>
                        $esrsStatusCode,
                    'remoteStatus' =>
                        $remoteStatus,
                    'esrsMessage' =>
                        $esrsMessage,
                    'responsePreview' => substr(
                        $esrsResponseBody,
                        0,
                        2000
                    ),
                ]
            );

            $clientStatusCode =
                $esrsStatusCode >= 400
                && $esrsStatusCode < 500
                    ? 422
                    : 502;

            return response()->json([
                'success' => false,
                'message' =>
                    $esrsMessage !== ''
                        ? $esrsMessage
                        : 'ESRS rejected the Invoice Emailing payload.',
                'status' =>
                    $remoteStatus !== ''
                        ? $remoteStatus
                        : 'FAILED',
                'requestId' =>
                    data_get(
                        $integrationRow,
                        'requestId'
                    )
                    ?? $requestId,
                'documentCount' =>
                    $documentCount,
                'esrsStatusCode' =>
                    $esrsStatusCode,
                'rows' =>
                    data_get(
                        $esrsResponseData,
                        'rows',
                        []
                    ),
                'customerNotificationHelperTriggered' =>
                    data_get(
                        $esrsResponseData,
                        'customerNotificationHelperTriggered'
                    ),
                'customerNotificationHelperError' =>
                    data_get(
                        $esrsResponseData,
                        'customerNotificationHelperError'
                    ),
                'customerNotificationHelperResult' =>
                    data_get(
                        $esrsResponseData,
                        'customerNotificationHelperResult'
                    ),
                'esrsResponse' =>
                    $esrsResponseData,
            ], $clientStatusCode);
        }

        /*
         * HTTP success alone is not enough. A valid integration
         * response must contain an accepted row.
         */
        if (
            $remoteStatus === ''
            || !in_array(
                $remoteStatus,
                [
                    'ACCEPTED',
                    'PARTIAL',
                    'COMPLETED',
                    'SUCCESS',
                ],
                true
            )
        ) {
            Log::error(
                'ESRS returned an unexpected Invoice Emailing response.',
                [
                    'databaseName' => $databaseName,
                    'requestId' => $requestId,
                    'documentCount' => $documentCount,
                    'esrsStatusCode' =>
                        $esrsStatusCode,
                    'remoteStatus' =>
                        $remoteStatus,
                    'responsePreview' => substr(
                        $esrsResponseBody,
                        0,
                        2000
                    ),
                ]
            );

            return response()->json([
                'success' => false,
                'message' =>
                    $esrsMessage !== ''
                        ? $esrsMessage
                        : 'ESRS returned an unexpected Invoice Emailing response.',
                'status' =>
                    $remoteStatus !== ''
                        ? $remoteStatus
                        : 'UNKNOWN',
                'requestId' =>
                    data_get(
                        $integrationRow,
                        'requestId'
                    )
                    ?? $requestId,
                'documentCount' =>
                    $documentCount,
                'esrsStatusCode' =>
                    $esrsStatusCode,
                'rows' =>
                    data_get(
                        $esrsResponseData,
                        'rows',
                        []
                    ),
                'esrsResponse' =>
                    $esrsResponseData,
            ], 502);
        }

        /*
         * ACCEPTED means ESRS received the document. It does not
         * mean that the customer email has already been sent.
         */
        $responseRequestId = trim((string) (
            data_get(
                $integrationRow,
                'requestId'
            )
            ?? $requestId
        ));

        $responseDocumentCount = (int) (
            data_get(
                $integrationRow,
                'documentCount'
            )
            ?? $documentCount
        );

        Log::info(
            'Invoice Emailing payload accepted by ESRS.',
            [
                'databaseName' => $databaseName,
                'requestId' => $responseRequestId,
                'documentCount' =>
                    $responseDocumentCount,
                'status' =>
                    $remoteStatus,
                'batchId' =>
                    data_get(
                        $integrationRow,
                        'batchId'
                    ),
                'esrsStatusCode' =>
                    $esrsStatusCode,
            ]
        );

        return response()->json([
            'success' => true,
            'status' =>
                $remoteStatus,
            'message' =>
                $esrsMessage !== ''
                    ? $esrsMessage
                    : 'Selected invoices were accepted by ESRS for customer notification.',
            'requestId' =>
                $responseRequestId,
            'batchId' =>
                data_get(
                    $integrationRow,
                    'batchId'
                ),
            'batchNo' =>
                data_get(
                    $integrationRow,
                    'batchNo'
                ),
            'sourceSystemCode' =>
                data_get(
                    $integrationRow,
                    'sourceSystemCode'
                ),
            'companyCode' =>
                data_get(
                    $integrationRow,
                    'companyCode'
                ),
            'notificationType' =>
                data_get(
                    $integrationRow,
                    'notificationType'
                ),
            'documentCount' =>
                $responseDocumentCount,
            'insertedCount' =>
                (int) (
                    data_get(
                        $integrationRow,
                        'insertedCount'
                    )
                    ?? 0
                ),
            'duplicateCount' =>
                (int) (
                    data_get(
                        $integrationRow,
                        'duplicateCount'
                    )
                    ?? 0
                ),
            'failedCount' =>
                (int) (
                    data_get(
                        $integrationRow,
                        'failedCount'
                    )
                    ?? 0
                ),
            'validationStatus' =>
                data_get(
                    $integrationRow,
                    'validationStatus'
                ),
            'transmissionStatus' =>
                data_get(
                    $integrationRow,
                    'transmissionStatus'
                ),
            'rows' =>
                data_get(
                    $esrsResponseData,
                    'rows',
                    []
                ),
            'customerNotificationHelperTriggered' =>
                data_get(
                    $esrsResponseData,
                    'customerNotificationHelperTriggered'
                ),
            'customerNotificationHelperError' =>
                data_get(
                    $esrsResponseData,
                    'customerNotificationHelperError'
                ),
            'customerNotificationHelperResult' =>
                data_get(
                    $esrsResponseData,
                    'customerNotificationHelperResult'
                ),
            'esrsStatusCode' =>
                $esrsStatusCode,

            /*
             * Kept for backward compatibility while the JSX is
             * being revised.
             */
            'esrsResponse' =>
                $esrsResponseData,
        ], 200);

    } catch (ConnectionException $exception) {
        report($exception);

        Log::error(
            'Unable to connect to the ESRS Invoice Emailing API.',
            [
                'exception' =>
                    get_class($exception),
                'message' =>
                    $exception->getMessage(),
            ]
        );

        return response()->json([
            'success' => false,
            'message' =>
                'Unable to connect to the ESRS Invoice Emailing API.',
        ], 502);

    } catch (JsonException $exception) {
        report($exception);

        Log::error(
            'Invoice Emailing JSON processing failed.',
            [
                'exception' =>
                    get_class($exception),
                'message' =>
                    $exception->getMessage(),
            ]
        );

        return response()->json([
            'success' => false,
            'message' =>
                'The Invoice Emailing payload contains invalid JSON.',
        ], 500);

    } catch (Throwable $exception) {
        report($exception);

        Log::error(
            'Invoice Emailing failed unexpectedly.',
            [
                'exception' =>
                    get_class($exception),
                'message' =>
                    $exception->getMessage(),
            ]
        );

        return response()->json([
            'success' => false,
            'message' =>
                app()->environment('local')
                    ? $exception->getMessage()
                    : 'Unable to submit invoices to ESRS.',
        ], 500);
    }
}



private function tenantCredentials(Request $request): array
{
    $credentials = $request->attributes->get('tenant.db');

    if (!$credentials && ($connection = $request->attributes->get('tenant.connection'))) {
        $credentials = [
            'host' => config("database.connections.{$connection}.host"),
            'database' => config("database.connections.{$connection}.database"),
            'username' => config("database.connections.{$connection}.username"),
            'password' => config("database.connections.{$connection}.password"),
        ];
    }

    if (!$credentials) {
        $connection = config('database.default', 'tenant');
        $credentials = [
            'host' => config("database.connections.{$connection}.host"),
            'database' => config("database.connections.{$connection}.database"),
            'username' => config("database.connections.{$connection}.username"),
            'password' => config("database.connections.{$connection}.password"),
        ];
    }

    return $credentials ?: [];
}



private function generateInvoicePdf(
    Request $request,
    string $documentType,
    string $groupId,
    string $userCode
): string {
    $docResult = DB::selectOne(
        'exec dbo.sproc_PHP_HSDoc @mode = ?, @docCode = ?',
        ['get', $documentType]
    );

    $docRows = json_decode((string) ($docResult->result ?? '[]'), true);
    $formName = trim((string) data_get($docRows, '0.formName', ''));

    if ($formName === '') {
        throw new \RuntimeException("Report Name is not defined for {$documentType}.");
    }

    $credentials = $this->tenantCredentials($request);

    foreach (['host', 'database', 'username', 'password'] as $field) {
        if (empty($credentials[$field])) {
            throw new \RuntimeException("Missing tenant database credential: {$field}.");
        }
    }

    $apiUrl = rtrim((string) config('services.crystal.base'), '/')
        . (string) config('services.crystal.form_generate');

    $response = Http::accept('application/pdf')
        ->timeout((int) config('services.ies_invoice_queuing.timeout', 180))
        ->post($apiUrl, [
            'ReportName' => $formName,
            'ServerName' => $credentials['host'],
            'DatabaseName' => $credentials['database'],
            'UserId' => $credentials['username'],
            'Password' => $credentials['password'],
            'TransactionId' => $groupId,
            'DocCode' => $documentType,
            'PrintMode' => 'IES',
            'UserCode' => $userCode,
        ]);

    if (!$response->successful()) {
        throw new \RuntimeException(
            "Unable to generate the PDF for {$documentType}. "
            . trim($response->body())
        );
    }

    $pdf = $response->body();

    if (!str_starts_with($pdf, '%PDF')) {
        throw new \RuntimeException("The generated {$documentType} document is not a valid PDF file.");
    }

    return $pdf;
}



private function createInvoiceZip(
    string $directory,
    string $baseName,
    string $pdf,
    string $invoiceJson
): string {
    $zipPath = $directory . DIRECTORY_SEPARATOR . $baseName . '.zip';
    $zip = new \ZipArchive();

    if ($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
        throw new \RuntimeException("Unable to create {$baseName}.zip.");
    }

    $zip->addFromString($baseName . '.pdf', $pdf);
    $zip->addFromString(
        $baseName . '.json',
        $invoiceJson
    );
    $zip->close();

    return $zipPath;
}



private function removeInvoiceQueueDirectory(string $directory): void
{
    if (!is_dir($directory)) {
        return;
    }

    foreach (scandir($directory) ?: [] as $fileName) {
        if ($fileName === '.' || $fileName === '..') {
            continue;
        }

        $path = $directory . DIRECTORY_SEPARATOR . $fileName;

        if (is_file($path)) {
            @unlink($path);
        }
    }

    @rmdir($directory);
}



private function resolveCurrentInvoiceCustomer(string $documentType, string $groupId): ?array
{
    $documentType = strtoupper(trim($documentType));
    $groupId = trim($groupId);

    if ($groupId === '') {
        return null;
    }

    /*
     * Resolve the customer from the actual transaction being queued.
     * Only whitelisted table/ID pairs are used here; no request value is
     * interpolated into a table or column name.
     */
    $sources = [
        'SI' => ['table' => 'si_hd', 'id' => 'si_id'],
        'SVI' => ['table' => 'svi_hd', 'id' => 'svi_id'],
        'ARCM' => ['table' => 'arcm_hd', 'id' => 'arcm_id'],
        'ARDM' => ['table' => 'ardm_hd', 'id' => 'ardm_id'],
    ];

    $source = $sources[$documentType] ?? null;

    if (!$source) {
        return null;
    }

    $transaction = DB::selectOne(
        "select top 1 custCode = cust_code
           from dbo.{$source['table']}
          where convert(nvarchar(40), {$source['id']}) = ?",
        [$groupId]
    );

    $custCode = trim((string) ($transaction->custCode ?? ''));

    if ($custCode === '') {
        return null;
    }

    $customer = DB::selectOne(
        "select top 1
                custCode = cust_code,
                custName = cust_name,
                businessName = business_name,
                custTin = cust_tin,
                branchCode = branch_code,
                custAddr1 = cust_addr1,
                custAddr2 = cust_addr2,
                custAddr3 = cust_addr3,
                custContact = cust_contact,
                custPosition = cust_position,
                custTelno = cust_telno,
                custMobileno = cust_mobileno,
                custEmail = cust_email,
                active = active
           from dbo.cust_mast
          where cust_code = ?",
        [$custCode]
    );

    if (!$customer) {
        return null;
    }

    return array_change_key_case((array) $customer, CASE_LOWER);
}


private function normalizeEisBuyerBranchCode(?string $branchCode): string
{
    $branchCode = trim((string) $branchCode);

    /*
     * BIR BuyerInfo.BranchCd is a numeric branch code. Internal NAYSA branch
     * values such as HO must never become the buyer's EIS branch code.
     */
    if ($branchCode !== '' && preg_match('/^\d{3}(?:\d{2})?$/', $branchCode) === 1) {
        return str_pad($branchCode, 5, '0', STR_PAD_LEFT);
    }

    return '00000';
}


private function buildCustomerRegisteredAddress(array $customer): string
{
    $parts = [];

    foreach (['custaddr1', 'custaddr2', 'custaddr3'] as $key) {
        $value = trim((string) ($customer[$key] ?? ''));

        if ($value !== '' && !in_array($value, $parts, true)) {
            $parts[] = $value;
        }
    }

    return implode(', ', $parts);
}


private function refreshInvoiceBuyerInfoFromCustomerMaster(
    string $invoiceJson,
    string $documentType,
    string $groupId,
    string $documentNo
): string {
    $invoiceData = json_decode(
        $invoiceJson,
        true,
        512,
        JSON_THROW_ON_ERROR
    );

    if (!is_array($invoiceData)) {
        throw new \RuntimeException(
            "The generated invoice JSON is invalid for {$documentType} {$documentNo}."
        );
    }

    $customer = $this->resolveCurrentInvoiceCustomer(
        $documentType,
        $groupId
    );

    if (!$customer) {
        throw new \RuntimeException(
            "Unable to resolve the current Customer Master record for {$documentType} {$documentNo}."
        );
    }

    if (strtoupper(trim((string) ($customer['active'] ?? 'Y'))) === 'N') {
        throw new \RuntimeException(
            "Customer {$customer['custcode']} is inactive and cannot receive {$documentType} {$documentNo}."
        );
    }

    $customerEmail = trim((string) ($customer['custemail'] ?? ''));

    /*
     * Do not fall back to MAIL_USERNAME / MAIL_FROM_ADDRESS / seller email.
     * An invoice must only be sent to the current customer email from CUST_MAST.
     */
    if ($customerEmail === '' || filter_var($customerEmail, FILTER_VALIDATE_EMAIL) === false) {
        throw new \RuntimeException(
            "Customer {$customer['custcode']} has no valid email address in Customer Master."
        );
    }

    /*
     * JsonVal may be the invoice root directly or wrapped by a common root key.
     * Work with the actual invoice object by reference so the final ZIP receives
     * the refreshed customer values.
     */
    $root =& $invoiceData;

    foreach (['document', 'Document', 'invoice', 'Invoice', 'data', 'Data'] as $rootKey) {
        if (isset($invoiceData[$rootKey]) && is_array($invoiceData[$rootKey])) {
            $root =& $invoiceData[$rootKey];
            break;
        }
    }

    if (isset($root['BuyerInfo']) && is_array($root['BuyerInfo'])) {
        $buyer =& $root['BuyerInfo'];
    } elseif (isset($root['buyerInfo']) && is_array($root['buyerInfo'])) {
        $buyer =& $root['buyerInfo'];
    } else {
        $root['BuyerInfo'] = [];
        $buyer =& $root['BuyerInfo'];
    }

    $custCode = trim((string) ($customer['custcode'] ?? ''));
    $custName = trim((string) ($customer['custname'] ?? ''));
    $businessName = trim((string) ($customer['businessname'] ?? ''));
    $custTin = trim((string) ($customer['custtin'] ?? ''));
    $custContact = trim((string) ($customer['custcontact'] ?? ''));
    $custPosition = trim((string) ($customer['custposition'] ?? ''));
    $custTelno = trim((string) ($customer['custtelno'] ?? ''));
    $custMobileno = trim((string) ($customer['custmobileno'] ?? ''));
    $registeredAddress = $this->buildCustomerRegisteredAddress($customer);

    $buyer['CustomerCode'] = $custCode;
    $buyer['Tin'] = $custTin;
    $buyer['BranchCd'] = $this->normalizeEisBuyerBranchCode(
        (string) ($customer['branchcode'] ?? '')
    );
    $buyer['RegNm'] = $custName !== '' ? $custName : $businessName;
    $buyer['BusinessNm'] = $businessName !== '' ? $businessName : $custName;
    $buyer['Email'] = $customerEmail;
    $buyer['RegAddr'] = $registeredAddress;

    /*
     * These are NAYSA extension fields used by the E-Invoicing Customer Master
     * synchronization. They do not replace the standard EIS BuyerInfo fields.
     */
    $buyer['CustContact'] = $custContact;
    $buyer['CustPosition'] = $custPosition;
    $buyer['CustTelno'] = $custTelno;
    $buyer['CustMobileno'] = $custMobileno;

    return json_encode(
        $invoiceData,
        JSON_UNESCAPED_UNICODE
        | JSON_UNESCAPED_SLASHES
        | JSON_THROW_ON_ERROR
    );
}


public function invoiceEmailingToIes(Request $request)
{
    $validated = $request->validate([
        'json_data' => ['required', 'array'],
        'json_data.requestId' => ['nullable', 'string', 'max:100'],
        'json_data.resend' => ['nullable', 'boolean'],
        'json_data.dt1' => ['required', 'array', 'min:1'],
        'json_data.dt1.*.documentType' => ['required', 'string', 'in:SI,SVI,ARCM,ARDM'],
        'json_data.dt1.*.branchCode' => ['required', 'string', 'max:10'],
        'json_data.dt1.*.groupId' => ['required', 'string', 'max:40'],
    ]);

    $uploadEndpoint = trim((string) config('services.ies_invoice_queuing.upload_endpoint'));
    $triggerEndpoint = trim((string) config('services.ies_invoice_queuing.email_trigger_endpoint'));
    $apiKey = trim((string) config('services.ies_invoice_queuing.api_key'));
    $apiKeyName = trim((string) config('services.ies_invoice_queuing.api_key_name'));
    $timeout = max(1, (int) config('services.ies_invoice_queuing.timeout', 180));
    $verifySsl = (bool) config('services.ies_invoice_queuing.verify_ssl', true);

    if ($uploadEndpoint === '' || $triggerEndpoint === '' || $apiKey === '' || $apiKeyName === '') {
        return response()->json([
            'success' => false,
            'message' => 'IES invoice queuing configuration is incomplete.',
        ], 500);
    }

    $authenticatedUser = $request->user();
    $userCode = trim((string) (
        data_get($authenticatedUser, 'userCode')
        ?: data_get($authenticatedUser, 'user_code')
        ?: data_get($validated, 'json_data.userCode')
        ?: 'SYSTEM'
    ));

    $params = json_encode(
        ['json_data' => array_merge($validated['json_data'], ['userCode' => $userCode])],
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR
    );

    $temporaryDirectory = storage_path(
        'app/tmp/invoice-queuing/' . bin2hex(random_bytes(12))
    );

    try {
        $procedureRow = DB::selectOne(
            'exec dbo.sproc_PHP_InvoiceEmailing @tranId = ?, @mode = ?, @userCode = ?, @params = ?',
            [null, 'GeneratePayload', $userCode, $params]
        );

        if (!$procedureRow) {
            throw new \RuntimeException('The Invoice Queuing procedure returned no result.');
        }

        $procedureResult = array_change_key_case((array) $procedureRow, CASE_LOWER);
        $errorMessage = trim((string) ($procedureResult['errormsg'] ?? ''));

        if ((int) ($procedureResult['errorcount'] ?? 0) > 0 || $errorMessage !== '') {
            throw new \RuntimeException($errorMessage ?: 'Unable to prepare the selected invoices.');
        }

        $payloadJson = $procedureResult['result'] ?? '';

        if (is_resource($payloadJson)) {
            $payloadJson = stream_get_contents($payloadJson);
        }

        $payload = json_decode((string) $payloadJson, true, 512, JSON_THROW_ON_ERROR);
        $documents = $payload['documents'] ?? [];

        if (!is_array($documents) || count($documents) === 0) {
            throw new \RuntimeException('No invoice document was generated for IES.');
        }

        if (!is_dir($temporaryDirectory) && !mkdir($temporaryDirectory, 0775, true) && !is_dir($temporaryDirectory)) {
            throw new \RuntimeException('Unable to create the temporary Invoice Queuing folder.');
        }

        $headers = [
            $apiKeyName => $apiKey,
            'X-ESRS-CLIENT-ID' => 'NAYSA_CLOUD',
            'X-ESRS-USER-ID' => $userCode,
        ];

        $uploadedDocuments = [];
        $processingResults = [];

        foreach ($documents as $document) {
            $source = is_array($document['SourceReference'] ?? null)
                ? $document['SourceReference']
                : [];

            $documentType = strtoupper(trim((string) ($source['SourceDocumentType'] ?? '')));
            $branchCode = trim((string) ($source['BranchCode'] ?? ''));
            $groupId = trim((string) ($source['GroupId'] ?? ''));
            $documentNo = trim((string) ($source['DocumentNo'] ?? ''));

            if ($documentType === '' || $branchCode === '' || $groupId === '' || $documentNo === '') {
                throw new \RuntimeException('An IES invoice is missing its source document information.');
            }

            $safeBaseName = preg_replace(
                '/[^A-Za-z0-9._()\-]/',
                '_',
                $documentType . '-' . $branchCode . '-' . $documentNo . '(' . date('YmdHisv') . ')'
            );

            $pdf = $this->generateInvoicePdf(
                $request,
                $documentType,
                $groupId,
                $userCode
            );

            $invoiceJson = trim((string) ($document['JsonVal'] ?? ''));

            if ($invoiceJson === '') {
                throw new \RuntimeException(
                    "The V11 legacy JSON is invalid for {$documentType} {$documentNo}."
                );
            }

            /*
             * IMPORTANT:
             * JsonVal is generated by the stored procedure, but customer details can
             * change after that legacy mapping was created. Refresh BuyerInfo from the
             * CURRENT NAYSA CUST_MAST immediately before creating the ZIP.
             *
             * This makes CUST_MAST the source of truth for the same invoice:
             * - Email
             * - Customer code
             * - TIN / BIR branch
             * - Registered/business name
             * - Address
             * - Contact person / position / phone numbers
             */
            $invoiceJson = $this->refreshInvoiceBuyerInfoFromCustomerMaster(
                $invoiceJson,
                $documentType,
                $groupId,
                $documentNo
            );

            $zipPath = $this->createInvoiceZip(
                $temporaryDirectory,
                $safeBaseName,
                $pdf,
                $invoiceJson
            );

            /* API #1: upload the ZIP to Invoice Queueing. */
            $uploadResponse = Http::acceptJson()
                ->timeout($timeout)
                ->withOptions(['verify' => $verifySsl])
                ->withHeaders(array_merge($headers, [
                    'Content-Type' => 'application/zip',
                    'X-ESRS-FILE-NAME' => rawurlencode(basename($zipPath)),
                ]))
                ->withBody(file_get_contents($zipPath), 'application/zip')
                ->post($uploadEndpoint);

            if (!$uploadResponse->successful()) {
                throw new \RuntimeException(
                    "IES ZIP upload failed for {$documentType} {$documentNo}. "
                    . trim($uploadResponse->body())
                );
            }

            $uploadData = $uploadResponse->json();
            if (!is_array($uploadData)) {
                $uploadData = [];
            }

            $queueId = (int) data_get($uploadData, 'queueId', 0);

            /*
             * API #2: explicitly process THIS newly uploaded queue row.
             * Passing queueId prevents old pending rows from consuming the cycle first.
             */
            $triggerPayload = [
                'batchSize' => 1,
                'currentUser' => $userCode,
            ];

            if ($queueId > 0) {
                $triggerPayload['queueId'] = $queueId;
            }

            $triggerResponse = Http::acceptJson()
                ->asJson()
                ->timeout(min($timeout, 60))
                ->withOptions(['verify' => $verifySsl])
                ->withHeaders($headers)
                ->post($triggerEndpoint, $triggerPayload);

            if (!$triggerResponse->successful()) {
                throw new \RuntimeException(
                    "IES email processing could not be started for {$documentType} {$documentNo}. "
                    . trim($triggerResponse->body())
                );
            }

            $triggerData = $triggerResponse->json();
            if (!is_array($triggerData)) {
                $triggerData = [];
            }

            $uploadStatus = strtoupper(trim((string) data_get($uploadData, 'status', '')));
            $triggerSuccess = data_get($triggerData, 'success');
            $triggerStatus = strtoupper(trim((string) data_get($triggerData, 'status', '')));
            $triggerFailedCount = (int) data_get($triggerData, 'failedCount', 0);

            if (
                $uploadStatus !== 'SENT'
                && ($triggerSuccess === false || $triggerFailedCount > 0 || $triggerStatus === 'COMPLETED_WITH_ERRORS')
            ) {
                $triggerMessages = data_get($triggerData, 'messages', []);
                $triggerMessage = is_array($triggerMessages)
                    ? implode(' ', array_map('strval', $triggerMessages))
                    : (string) $triggerMessages;

                throw new \RuntimeException(
                    "IES email processing failed for {$documentType} {$documentNo}. "
                    . trim($triggerMessage)
                );
            }

            $uploadedDocuments[] = [
                'tranId' => $groupId,
                'documentType' => $documentType,
                'branchCode' => $branchCode,
                'documentNo' => $documentNo,
                'fileName' => basename($zipPath),
                'queueId' => $queueId > 0 ? $queueId : null,
                'uploadStatus' => $uploadStatus,
                'processingStatus' => $triggerStatus,
            ];

            $processingResults[] = [
                'documentType' => $documentType,
                'documentNo' => $documentNo,
                'queueId' => $queueId > 0 ? $queueId : null,
                'upload' => $uploadData,
                'process' => $triggerData,
            ];
        }

        $statusDate = now()->toIso8601String();
        $statusParams = json_encode([
            'json_data' => [
                'requestId' => $payload['requestId'] ?? null,
                'dt1' => array_map(
                    fn (array $document) => [
                        'tranId' => $document['tranId'],
                        'status' => 'TRANSMITTED',
                        'statusDate' => $statusDate,
                        'statusBy' => $userCode,
                        'receivedBy' => null,
                    ],
                    $uploadedDocuments
                ),
            ],
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);

        $statusResult = DB::selectOne(
            'exec dbo.sproc_PHP_CallBack_InvoiceEmailing @mode = ?, @params = ?',
            ['UPDATESTATUS', $statusParams]
        );

        $statusRow = array_change_key_case((array) $statusResult, CASE_LOWER);

        if ((int) ($statusRow['errorcount'] ?? 0) > 0) {
            throw new \RuntimeException(
                (string) ($statusRow['errormsg'] ?? 'Unable to update IES transmission status.')
            );
        }

        return response()->json([
            'success' => true,
            'status' => 'ACCEPTED',
            'message' => count($uploadedDocuments) . ' invoice(s) were queued and processing was triggered successfully in IES.',
            'requestId' => $payload['requestId'] ?? null,
            'documentCount' => count($uploadedDocuments),
            'uploadedDocuments' => $uploadedDocuments,
            'processingResults' => $processingResults,
        ]);
    } catch (ConnectionException $exception) {
        report($exception);

        return response()->json([
            'success' => false,
            'message' => 'Unable to connect to the IES Invoice Queuing API.',
        ], 502);
    } catch (Throwable $exception) {
        report($exception);

        Log::error('Invoice Queuing failed.', [
            'exception' => get_class($exception),
            'message' => $exception->getMessage(),
        ]);

        return response()->json([
            'success' => false,
            'message' => app()->environment('local')
                ? $exception->getMessage()
                : 'Unable to queue the selected invoices in IES.',
        ], 500);
    } finally {
        /*
         * Keep the generated files only while you are verifying the ZIP content.
         * Change this back to $this->removeInvoiceQueueDirectory($temporaryDirectory)
         * after the integration is confirmed.
         */
        Log::info('Invoice Queuing temporary files retained for checking.', [
            'directory' => $temporaryDirectory,
        ]);
    }
}



public function callBackInvoiceEmailing(Request $request)
{
    /*
     * Public server-to-server callback from IES.
     * Authentication uses the same X-ESRS-API-KEY configured for
     * the existing NAYSA Cloud to IES invoice transmission.
     */
    $expectedApiKey = trim((string) config(
        'services.ies_invoice_queuing.api_key',
        ''
    ));

    $apiKeyName = trim((string) config(
        'services.ies_invoice_queuing.api_key_name',
        'X-ESRS-API-KEY'
    ));

    $providedApiKey = trim((string) $request->header(
        $apiKeyName,
        ''
    ));

    if ($expectedApiKey === '') {
        Log::error(
            'Invoice Emailing callback API key is not configured.'
        );

        return response()->json([
            'success' => false,
            'message' =>
                'Invoice Emailing callback API key is not configured.',
        ], 500);
    }

    if (
        $providedApiKey === ''
        || !hash_equals($expectedApiKey, $providedApiKey)
    ) {
        Log::warning(
            'Rejected Invoice Emailing callback due to an invalid API key.',
            [
                'requestId' =>
                    $request->input('json_data.requestId'),
                'remoteAddress' =>
                    $request->ip(),
            ]
        );

        return response()->json([
            'success' => false,
            'message' => 'Unauthorized.',
        ], 401);
    }

    $validated = $request->validate([
        'json_data' => [
            'required',
            'array',
        ],

        'json_data.requestId' => [
            'nullable',
            'string',
            'max:100',
        ],

        'json_data.dt1' => [
            'required',
            'array',
            'min:1',
        ],

        'json_data.dt1.*.tranId' => [
            'required',
            'string',
            'max:40',
        ],

        'json_data.dt1.*.status' => [
            'required',
            'string',
            'in:TRANSMITTED,ACCEPTED,REJECTED',
        ],

        'json_data.dt1.*.statusDate' => [
            'required',
            'date',
        ],

        'json_data.dt1.*.statusBy' => [
            'required',
            'string',
            'max:100',
        ],

        'json_data.dt1.*.receivedBy' => [
            'nullable',
            'string',
            'max:100',
        ],
    ]);

    /*
     * receivedBy is not required for TRANSMITTED.
     * It is required when the customer ACCEPTS or REJECTS the invoice.
     */
    foreach ($validated['json_data']['dt1'] as $index => $statusRow) {
        $status = strtoupper(
            trim((string) ($statusRow['status'] ?? ''))
        );

        $receivedBy = trim(
            (string) ($statusRow['receivedBy'] ?? '')
        );

        if (
            in_array(
                $status,
                ['ACCEPTED', 'REJECTED'],
                true
            )
            && $receivedBy === ''
        ) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                "json_data.dt1.{$index}.receivedBy" =>
                    'The received by field is required when status is ACCEPTED or REJECTED.',
            ]);
        }
    }

    try {
        $params = json_encode(
            [
                'json_data' => $validated['json_data'],
            ],
            JSON_UNESCAPED_UNICODE
            | JSON_UNESCAPED_SLASHES
            | JSON_THROW_ON_ERROR
        );

        $row = DB::selectOne(
            '
                exec dbo.sproc_PHP_CallBack_InvoiceEmailing
                    @mode = ?,
                    @params = ?
            ',
            [
                'UPDATESTATUS',
                $params,
            ]
        );

        if ($row === null) {
            throw new \RuntimeException(
                'The Invoice Emailing callback procedure returned no result.'
            );
        }

        $procedureResult = array_change_key_case(
            (array) $row,
            CASE_LOWER
        );

        $errorCount = (int) (
            $procedureResult['errorcount'] ?? 0
        );

        $errorMessage = trim((string) (
            $procedureResult['errormsg'] ?? ''
        ));

        $rawResult = $procedureResult['result'] ?? null;

        if (is_resource($rawResult)) {
            $rawResult = stream_get_contents($rawResult);
        }

        if ($errorCount > 0 || $errorMessage !== '') {
            Log::warning(
                'Invoice Emailing callback was rejected by SQL.',
                [
                    'requestId' =>
                        data_get($validated, 'json_data.requestId'),
                    'errorCount' => $errorCount,
                    'errorMessage' => $errorMessage,
                    'documentCount' =>
                        count($validated['json_data']['dt1']),
                ]
            );

            return response()->json([
                'success' => false,
                'message' =>
                    $errorMessage !== ''
                        ? $errorMessage
                        : 'Unable to update the Invoice Emailing status.',
            ], 422);
        }

        $result = [];

        if (
            is_string($rawResult)
            && trim($rawResult) !== ''
        ) {
            $result = json_decode(
                $rawResult,
                true,
                512,
                JSON_THROW_ON_ERROR
            );
        } elseif (is_array($rawResult)) {
            $result = $rawResult;
        }

        Log::info(
            'Invoice Emailing callback processed successfully.',
            [
                'requestId' =>
                    data_get($validated, 'json_data.requestId'),
                'documentCount' =>
                    count($validated['json_data']['dt1']),
                'updatedCount' =>
                    data_get($result, 'updatedCount'),
            ]
        );

        return response()->json([
            'success' => true,
            'message' =>
                'Invoice Emailing status was updated successfully.',
            'data' => $result,
        ], 200);

    } catch (JsonException $exception) {
        report($exception);

        return response()->json([
            'success' => false,
            'message' =>
                'The Invoice Emailing callback payload contains invalid JSON.',
        ], 422);

    } catch (Throwable $exception) {
        report($exception);

        Log::error(
            'Invoice Emailing callback failed unexpectedly.',
            [
                'exception' =>
                    get_class($exception),
                'message' =>
                    $exception->getMessage(),
                'requestId' =>
                    data_get($validated, 'json_data.requestId'),
            ]
        );

        return response()->json([
            'success' => false,
            'message' =>
                app()->environment('local')
                    ? $exception->getMessage()
                    : 'Unable to update the Invoice Emailing status.',
        ], 500);
    }
}



}
