<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RCTypeController extends Controller
{
    /**
     * RC Type SQL expects FLAT JSON: {"rcTypeCode":"...","userCode":"..."}.
     * Keep support for the stringified json_data already sent by RcRef.jsx.
     */
    private function writeParams(Request $request): string
    {
        $data = $request->input('json_data', $request->all());

        try {
            if (is_string($data)) {
                $data = json_decode($data, true, 512, JSON_THROW_ON_ERROR);
            }

            if (is_array($data) && array_key_exists('json_data', $data)) {
                $data = $data['json_data'];
                if (is_string($data)) {
                    $data = json_decode($data, true, 512, JSON_THROW_ON_ERROR);
                }
            }
        } catch (\JsonException $e) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'json_data' => 'Invalid RC Type JSON data.',
            ]);
        }

        if (!is_array($data)) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'json_data' => 'RC Type data must be a JSON object.',
            ]);
        }

        // Prefer the authenticated identity; retain payload support for
        // the existing application flow when no Laravel user is available.
        $currentUser = $request->user();
        $userCode = '';
        foreach ([
            data_get($currentUser, 'USER_CODE'),
            data_get($currentUser, 'user_code'),
            data_get($currentUser, 'userCode'),
            $data['userCode'] ?? null,
        ] as $candidate) {
            if (is_scalar($candidate) && trim((string) $candidate) !== '') {
                $userCode = trim((string) $candidate);
                break;
            }
        }

        if ($userCode === '') {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'userCode' => 'The signed-in user code is missing. Please sign in again.',
            ]);
        }

        $data['userCode'] = $userCode;
        return json_encode($data, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    }

    /**
     * Consume all SQL result sets so a later audit error is not missed.
     * The record and audit writes use the same connection and transaction.
     */
    private function executeWrite(string $mode, string $params): array
    {
        $connection = DB::connection();

        return $connection->transaction(function () use ($connection, $mode, $params) {
            $statement = $connection->getPdo()->prepare(
                'EXEC dbo.sproc_PHP_RCTypeRef @mode = ?, @params = ?'
            );
            $statement->bindValue(1, $mode, \PDO::PARAM_STR);
            $statement->bindValue(2, $params, \PDO::PARAM_STR);
            $lastRows = [];

            try {
                $statement->execute();
                do {
                    if ($statement->columnCount() > 0) {
                        $lastRows = $statement->fetchAll(\PDO::FETCH_OBJ);
                        foreach ($lastRows as $row) {
                            $fields = array_change_key_case((array) $row, CASE_LOWER);
                            if ((int) ($fields['errorcount'] ?? 0) > 0) {
                                throw \Illuminate\Validation\ValidationException::withMessages([
                                    'rcType' => $fields['errormsg'] ?? 'RC Type operation failed.',
                                ]);
                            }
                        }
                    }
                } while ($statement->nextRowset());
            } finally {
                $statement->closeCursor();
            }

            $status = array_change_key_case((array) ($lastRows[0] ?? []), CASE_LOWER);
            if (!array_key_exists('errorcount', $status)
                || !array_key_exists('errormsg', $status)) {
                throw new \RuntimeException('RC Type procedure did not return a save/delete result.');
            }

            return $lastRows;
        });
    }

    private function writeResponse(Request $request, string $mode)
    {
        try {
            $results = $this->executeWrite($mode, $this->writeParams($request));
            return response()->json([
                'status' => 'success',
                'success' => true,
                'data' => $results,
            ], 200);
        } catch (\Illuminate\Validation\ValidationException $e) {
            $messages = [];
            foreach ($e->errors() as $items) {
                foreach ((array) $items as $message) {
                    $messages[] = $message;
                }
            }
            return response()->json([
                'status' => 'error',
                'success' => false,
                'message' => implode("\n", $messages),
                'errors' => $e->errors(),
            ], 422);
        } catch (\Throwable $e) {
            Log::error('RC Type operation failed', [
                'mode' => $mode,
                'exception' => get_class($e),
                'error' => $e->getMessage(),
            ]);
            return response()->json([
                'status' => 'error',
                'success' => false,
                'message' => ($mode === 'Delete' ? 'Failed to delete: ' : 'Failed to save: ')
                    . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Load all RC Types
     */
    public function index(Request $request)
    {
        try {
            $results = DB::select('EXEC sproc_PHP_RCTypeRef @mode = ?', ['Load']);
            return response()->json(['success' => true, 'data' => $results], 200);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Add or Update RC Type (POST - already JSON)
     */
    public function upsert(Request $request)
    {
        return $this->writeResponse($request, 'Upsert');
    }

    /**
     * Delete RC Type (POST - already JSON)
     */
    public function delete(Request $request)
    {
        return $this->writeResponse($request, 'Delete');
    }

    public function loadRcType(Request $request)
    {
        return $this->index($request);
    }

    /**
     * Get a single RC Type (GET - Convert to JSON)
     */
    public function get(Request $request)
    {
        try {
            $code = $request->query('rcTypeCode');
            
            // Format as JSON before sending to SPROC
            $json_params = json_encode(['rcTypeCode' => $code]);

            $results = DB::select('EXEC sproc_PHP_RCTypeRef @mode = ?, @params = ?', ['Get', $json_params]);
            return response()->json(['success' => true, 'data' => $results], 200);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Check Duplicate (POST - already JSON)
     */
    public function checkDuplicate(Request $request)
    {
        try {
            $params = $request->input('json_data');
            
            $results = DB::select('EXEC sproc_PHP_RCTypeRef @mode = ?, @params = ?', ['CheckDuplicate', $params]);
            return response()->json(['success' => true, 'data' => $results], 200);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Check In Used (POST - already JSON)
     */
    public function checkInUsed(Request $request)
    {
        try {
            $params = $request->input('json_data');
            
            $results = DB::select('EXEC sproc_PHP_RCTypeRef @mode = ?, @params = ?', ['CheckInUsed', $params]);
            return response()->json(['success' => true, 'data' => $results], 200);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Lookup for Dropdowns (GET - Convert to JSON)
     */
    public function lookup(Request $request)
    {
        try {
            $filter = $request->query('filter', '');
            
            // Format as JSON before sending to SPROC
            $json_params = json_encode(['filter' => $filter]);

            $results = DB::select('EXEC sproc_PHP_RCTypeRef @mode = ?, @params = ?', ['Lookup', $json_params]);
            return response()->json(['success' => true, 'data' => $results], 200);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}
