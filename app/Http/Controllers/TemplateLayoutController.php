<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class TemplateLayoutController extends Controller
{
    private string $baseDirectory = 'template-layouts';

    private function cleanLayoutType(?string $value): string
    {
        $type = trim((string) $value);
        $type = preg_replace('/[^a-zA-Z0-9_-]/', '', $type) ?: 'general';
        return Str::limit(Str::lower($type), 60, '');
    }

    private function cleanLayoutName(?string $value): string
    {
        $name = trim((string) $value);
        $name = preg_replace('/[^a-zA-Z0-9 _.-]/', '', $name) ?: 'default';
        $name = preg_replace('/\s+/', ' ', $name);
        return Str::limit($name, 60, '');
    }

    private function directory(string $layoutType): string
    {
        return $this->baseDirectory . '/' . $this->cleanLayoutType($layoutType);
    }

    private function fileName(string $layoutName): string
    {
        $safeName = str_replace(' ', '_', $this->cleanLayoutName($layoutName));
        return $safeName . '.json';
    }

    private function filePath(string $layoutType, string $layoutName): string
    {
        return $this->directory($layoutType) . '/' . $this->fileName($layoutName);
    }

    public function index(string $layoutType): JsonResponse
    {
        $type = $this->cleanLayoutType($layoutType);
        $directory = $this->directory($type);

        Storage::disk('local')->makeDirectory($directory);

        $files = Storage::disk('local')->files($directory);

        $layouts = collect($files)
            ->filter(fn ($file) => Str::endsWith($file, '.json'))
            ->map(function ($file) use ($type) {
                $content = json_decode(Storage::disk('local')->get($file), true) ?: [];
                $settings = $content['settings'] ?? $content;
                $layoutName = $content['layoutName'] ?? $settings['layoutName'] ?? pathinfo($file, PATHINFO_FILENAME);

                return [
                    'layoutType' => $content['layoutType'] ?? $settings['layoutType'] ?? $type,
                    'layoutName' => $layoutName,
                    'description' => $content['description'] ?? $settings['description'] ?? '',
                    'fileName' => basename($file),
                    'savedAt' => $content['savedAt'] ?? $settings['savedAt'] ?? null,
                    'updatedAt' => date('Y-m-d H:i:s', Storage::disk('local')->lastModified($file)),
                ];
            })
            ->values();

        return response()->json([
            'success' => true,
            'data' => $layouts,
        ]);
    }

    public function show(string $layoutType, string $layoutName): JsonResponse
    {
        $type = $this->cleanLayoutType($layoutType);
        $name = $this->cleanLayoutName($layoutName);
        $path = $this->filePath($type, $name);

        if (!Storage::disk('local')->exists($path)) {
            return response()->json([
                'success' => false,
                'message' => 'Template layout file was not found.',
            ], 404);
        }

        $content = json_decode(Storage::disk('local')->get($path), true) ?: [];

        return response()->json([
            'success' => true,
            'data' => $content,
        ]);
    }

    public function store(Request $request, string $layoutType): JsonResponse
    {
        $type = $this->cleanLayoutType($layoutType);
        $layoutName = $this->cleanLayoutName($request->input('layoutName', 'default'));
        $description = trim((string) $request->input('description', ''));
        $settings = $request->input('settings', []);

        if (!is_array($settings)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid layout settings payload.',
            ], 422);
        }

        $settings['layoutType'] = $type;
        $settings['layoutName'] = $layoutName;
        $settings['savedAt'] = now()->toISOString();

        $payload = [
            'layoutType' => $type,
            'layoutName' => $layoutName,
            'description' => $description,
            'savedAt' => now()->toISOString(),
            'settings' => $settings,
        ];

        $directory = $this->directory($type);
        Storage::disk('local')->makeDirectory($directory);
        Storage::disk('local')->put(
            $this->filePath($type, $layoutName),
            json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
        );

        return response()->json([
            'success' => true,
            'message' => 'Template layout saved successfully.',
            'data' => $payload,
        ]);
    }

    public function destroy(string $layoutType, string $layoutName): JsonResponse
    {
        $type = $this->cleanLayoutType($layoutType);
        $name = $this->cleanLayoutName($layoutName);
        $path = $this->filePath($type, $name);

        if (!Storage::disk('local')->exists($path)) {
            return response()->json([
                'success' => false,
                'message' => 'Template layout file was not found.',
            ], 404);
        }

        Storage::disk('local')->delete($path);

        return response()->json([
            'success' => true,
            'message' => 'Template layout deleted successfully.',
        ]);
    }
}
