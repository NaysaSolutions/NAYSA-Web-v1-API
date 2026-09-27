<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use JsonException;
use Throwable;

class FAMoveController extends Controller
{
    private const QUERY_TIME_LIMIT_SECONDS = 0;

    public function getFAAssetQuery(Request $request)
    {
        return $this->executeMode($request, 'AssetInquiry');
    }

    public function getFAAssetHistory(Request $request)
    {
        return $this->executeMode($request, 'AssetHistory');
    }

    public function getFADeprHistory(Request $request)
    {
        return $this->executeMode($request, 'DepreciationHistory');
    }

    public function getFALapsingSchedule(Request $request)
    {
        return $this->executeMode($request, 'LapsingSchedule');
    }

    private function executeMode(Request $request, string $mode)
    {
        set_time_limit(self::QUERY_TIME_LIMIT_SECONDS);

        $startTotal = microtime(true);

        try {
            $validated = $request->validate([
                'json_data' => ['required', 'array'],

                'json_data.mode' => ['nullable', 'string'],
                'json_data.branchCode' => ['nullable', 'string'],
                'json_data.flocCode' => ['nullable', 'string'],
                'json_data.rcCode' => ['nullable', 'string'],
                'json_data.categCode' => ['nullable', 'string'],
                'json_data.classCode' => ['nullable', 'string'],
                'json_data.faCode' => ['nullable', 'string'],
                'json_data.tagNo' => ['nullable', 'string'],
                'json_data.startingCutoff' => ['nullable', 'string', 'max:6'],
                'json_data.endingCutoff' => ['nullable', 'string', 'max:6'],
            ]);

            $jsonData = $validated['json_data'];

            if (
                isset($jsonData['json_data']) &&
                is_array($jsonData['json_data'])
            ) {
                $jsonData = $jsonData['json_data'];
            }

            $jsonData = array_filter(
                $jsonData,
                static fn ($value) => $value !== null
            );

            $params = json_encode(
                ['json_data' => $jsonData],
                JSON_UNESCAPED_UNICODE |
                JSON_UNESCAPED_SLASHES |
                JSON_THROW_ON_ERROR
            );

            $startSql = microtime(true);

            $results = DB::select(
                'exec dbo.sproc_PHP_FAMove @mode = ?, @params = ?',
                [
                    $mode,
                    $params,
                ]
            );

            $sqlSeconds = round(microtime(true) - $startSql, 4);
            $totalSeconds = round(microtime(true) - $startTotal, 4);

            Log::info('FA Move execution timing.', [
                'mode' => $mode,
                'sqlSeconds' => $sqlSeconds,
                'totalSecondsBeforeResponse' => $totalSeconds,
                'params' => $params,
                'resultCount' => is_array($results) ? count($results) : 0,
                'resultSize' => isset($results[0]->result)
                    ? strlen((string) $results[0]->result)
                    : 0,
            ]);

            return response()->json([
                'status' => 'success',
                'data' => $results,
                'debug' => [
                    'sqlSeconds' => $sqlSeconds,
                    'totalSecondsBeforeResponse' => $totalSeconds,
                ],
            ], 200);
        } catch (ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid FA inquiry request.',
                'errors' => $e->errors(),
            ], 422);
        } catch (JsonException $e) {
            Log::error('Failed to encode FA Move parameters.', [
                'mode' => $mode,
                'message' => $e->getMessage(),
                'request' => $request->all(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to prepare the FA inquiry parameters.',
                'details' => $e->getMessage(),
            ], 500);
        } catch (Throwable $e) {
            Log::error('Error executing sproc_PHP_FAMove.', [
                'mode' => $mode,
                'message' => $e->getMessage(),
                'request' => $request->all(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Error executing FA Move ' . $mode . '.',
                'details' => $e->getMessage(),
            ], 500);
        }
    }
}