<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class FileAttachmentController extends Controller
{

    public function get(Request $request)
    {   
        $params = $request->input('documentID');

        try {
            $results = DB::select(
                'EXEC sproc_PHP_AttTran @mode = ?, @tranid = ?',
                ['Load', $params]
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


    public function attachFile(Request $request)
    {
        $files = $request->file('files'); 
        $modifiedDates = $request->input('modifiedDate', []);
        $uploadedDates = $request->input('uploadedDate', []);
        $tranID =$request->input('documentID');

        $responses = [];

        foreach ($files as $index => $file) {
            // Generate random system ID (no extension)
            $randomId = uniqid();

            // Original file name (with extension)
            $originalName = $file->getClientOriginalName();

            // Store file in /storage/app/uploads using randomId (no extension)
            $path = $file->storeAs('', $randomId, 'public_uploads');

            // Dates
            $modified = $modifiedDates[$index] ?? now();
            $uploaded = $uploadedDates[$index] ?? now();

            // Call stored procedure
            DB::statement("EXEC sproc_PHP_AttTran ?, ?, ?, ?, ?, ?", [
                "upload",
                $randomId,      // file_id
                $originalName,  // file_name (original with extension)
                $modified,
                $uploaded,
                $tranID
            ]);

            $responses[] = [
                "id"            => $randomId,
                "file_name"     => $originalName,
                "path"          => $path,
                "date_modified" => $modified,
                "date_uploaded" => $uploaded
            ];
        }

        return response()->json([
            "message" => "Files uploaded successfully",
            "data"    => $responses,
        ]);
    }


    public function deleteFile(Request $request, $id)
    {
        try {
            // Get file info from DB
            $fileRecord = DB::table('att_tran')->where('file_id', $id)->first();

            if (!$fileRecord) {
                return response()->json(["message" => "File not found"], 404);
            }

            // Delete physical file
            $filePath = $id;
            if (Storage::disk('public_uploads')->exists($filePath)) {
                Storage::disk('public_uploads')->delete($filePath);
            }

            // Call stored procedure
            DB::statement("EXEC sproc_PHP_AttTran ?, ?", ["delete", $id]);

            return response()->json([
                "message" => "File deleted successfully",
                "file"    => $fileRecord,
            ]);
        } catch (\Exception $e) {
            \Log::error("Delete failed: " . $e->getMessage());

            return response()->json([
                "message" => "Delete failed",
                "error"   => $e->getMessage(),
            ], 500);
        }
    }


    public function downloadAll(Request $request, $documentID)
    {
        try {
            $files = DB::table('ATT_TRAN')
                ->where('TRAN_ID', $documentID)
                ->get();

            if ($files->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No attachments found'
                ], 404);
            }

            $zipFileName = "attachments_{$documentID}.zip";
            $zipPath = storage_path("app/temp/{$zipFileName}");

            if (!file_exists(storage_path("app/temp"))) {
                mkdir(storage_path("app/temp"), 0777, true);
            }

            $zip = new \ZipArchive;
            if ($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) === true) {

                foreach ($files as $file) {
                    $filePath = public_path('uploads/' . $file->FILE_ID);

                    \Log::info("Checking file: {$filePath}");

                    if (file_exists($filePath)) {
                        // Sanitize filename for ZIP (avoid \ / : * ? " < > | )
                        $safeName = preg_replace('/[\/:*?"<>|]/', '_', $file->FILE_NAME);

                        $zip->addFile($filePath, $safeName);
                        \Log::info("Added to ZIP: {$safeName}");
                    } else {
                        \Log::warning("File not found on disk: {$filePath}");
                    }
                }

                $zip->close();
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Could not create ZIP file'
                ], 500);
            }

            // THE MAGIC FIX: Clear any output buffers before sending the binary file
            while (ob_get_level() > 0) {
                ob_end_clean();
            }

            return response()->download($zipPath, $zipFileName)->deleteFileAfterSend(true);

        } catch (\Exception $e) {
            \Log::error("DownloadAll failed: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Download failed: ' . $e->getMessage(),
            ], 500);
        }
    }


    public function downloadFile(Request $request, $id)
    {
        try {
            $file = DB::table('ATT_TRAN')
                ->where('FILE_ID', $id)
                ->first();

            if (!$file) {
                return response()->json([
                    'success' => false,
                    'message' => 'File not found'
                ], 404);
            }

            $storagePath = public_path('uploads/' . $file->FILE_ID);

            if (!file_exists($storagePath)) {
                return response()->json([
                    'success' => true,
                    'message' => 'File not found on server'
                ], 404);
            }

            // THE MAGIC FIX: Clear any output buffers before sending the binary file
            while (ob_get_level() > 0) {
                ob_end_clean();
            }

            return response()->download($storagePath, $file->FILE_NAME);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Download failed: ' . $e->getMessage(),
            ], 500);
        }
    }
}