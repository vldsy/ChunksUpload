<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Post;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class UploadController extends Controller
{
    public function upload(Request $request)
    {
        Log::info($request->all());

        $file = $request->file('file');
        $chunkIndex = $request->input('dzchunkindex');
        $totalChunks = $request->input('dztotalchunkcount');
        $fileExtension = $file->getClientOriginalExtension();
        $filename = $request->input('dzuuid') . '.' . $fileExtension;

        Log::info('Chunk Index: ' . $chunkIndex);
        Log::info('Total Chunks: ' . $totalChunks);
        Log::info('fileExtension: ' . $fileExtension);
        Log::info('filename: ' . $filename);

        Storage::putFileAs('chunks', $file, $filename . '.' . $chunkIndex);

        // Combine chunks if this is the last one
        if ($chunkIndex == $totalChunks - 1) {
            $this->combineChunks($filename, $totalChunks);
            $post = Post::find(1);
            $post->addMedia(storage_path('app/private/chunks/' . $filename))
                 ->toMediaCollection('my_collection');
        }

        return response()->json(['success' => true]);
    }

    private function combineChunks($filename, $totalChunks)
    {
        $path = storage_path('app/private/chunks/');
        $combinedFilePath = $path . $filename;

        // Ensure the chunks directory exists
        if (!file_exists($path)) {
            mkdir($path, 0755, true);
        }

        // Create the combined file
        $combinedFile = fopen($combinedFilePath, 'w');

        for ($i = 0; $i < $totalChunks; $i++) {
            $chunkPath = $path . $filename . '.' . $i;
            if (file_exists($chunkPath)) {
                $chunk = fopen($chunkPath, 'r');
                stream_copy_to_stream($chunk, $combinedFile);
                fclose($chunk);
                if (!Storage::delete("chunks/{$filename}.{$i}")) {
                    Log::error("Failed to delete chunk: {$chunkPath}");
                }
            } else {
                Log::error('Chunk file does not exist: ' . $chunkPath);
            }
        }

        fclose($combinedFile);
    }
}
