<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Post;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Spatie\MediaLibrary\MediaCollections\Exceptions\FileIsTooBig;
use Spatie\MediaLibrary\MediaCollections\Exceptions;
use Spatie\MediaLibrary\Support\File;

/**
 * A controller that handles a chunk upload
 */

class UploadController extends Controller
{
    /**
     * upload function
     * handles single chunk upload
     * when the last chunk is uploaded the file is merged on the server from chunks
     * then the chunks are deleted
     */
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

        // If this is the last chunk, combine chunks
        if ($chunkIndex == $totalChunks - 1) {
            $this->combineChunks($filename, $totalChunks);
            $post = Post::find(1);
            try {
                $post->addMedia(storage_path('app/private/chunks/' . $filename))
                 ->toMediaCollection('my_collection');
            } catch (Spatie\MediaLibrary\MediaCollections\Exceptions\FileIsTooBig $e) {
                Log::error("Uploaded file is too big.");
            }

        }

        return response()->json(['success' => true]);
    }

    /**
     * combineChunks combines the chunks after
     * all of them are uploaded
     * @param  string  $filename
     * this is the filename (actually it is a GUID)
     * @param  int  $totalChunks
     * number of chunks
     * @return void
     */
    private function combineChunks(string $filename, int $totalChunks)
    {
        $path = storage_path('app/private/chunks/');
        $combinedFilePath = $path . $filename;

        // chunks directory should exist
        // if not -- we create it here
        if (!file_exists($path)) {
            mkdir($path, 0755, true); // FIXME not the intended way to change file permissions
        }

        // Create the combined result file
        $combinedFile = fopen($combinedFilePath, 'w');

        // copy each chunk to result combined file
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
