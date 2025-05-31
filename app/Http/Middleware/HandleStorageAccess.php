<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class HandleStorageAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        if (strpos($request->path(), 'storage/') === 0) {
            $path = str_replace('storage/', '', $request->path());
            
            // Log the storage access attempt
            \Log::info('Storage access attempt:', [
                'original_path' => $request->path(),
                'processed_path' => $path,
                'exists' => Storage::disk('public')->exists($path),
                'full_path' => storage_path('app/public/' . $path)
            ]);

            // Check if file exists
            if (!Storage::disk('public')->exists($path)) {
                \Log::warning('Storage file not found:', [
                    'path' => $path,
                    'full_path' => storage_path('app/public/' . $path)
                ]);
                return response()->json(['error' => 'File not found'], 404);
            }

            // Get file mime type
            $mime = Storage::disk('public')->mimeType($path);
            
            // Set appropriate headers
            $headers = [
                'Content-Type' => $mime,
                'Cache-Control' => 'public, max-age=31536000',
                'Access-Control-Allow-Origin' => '*',
                'Access-Control-Allow-Methods' => 'GET, OPTIONS',
                'Access-Control-Allow-Headers' => 'Content-Type, Authorization, X-Requested-With'
            ];

            // Return file with headers
            return response()->file(
                storage_path('app/public/' . $path),
                $headers
            );
        }

        return $next($request);
    }
} 