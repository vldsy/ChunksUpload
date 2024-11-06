<?php

namespace App\Http\Controllers;

use App\Jobs\CleanupUploadedChunksJob;
use Illuminate\Http\Request;
use App\Models\Post;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Spatie\MediaLibrary\MediaCollections\Exceptions\FileIsTooBig;
use Spatie\MediaLibrary\MediaCollections\Exceptions;
use Spatie\MediaLibrary\Support\File;
use Illuminate\Support\Str;

/**
 * A controller that handles a chunk upload
 */

class UploadController extends Controller
{
    /**
     * uploadFiles
     * upload function
     * handles single chunk upload
     * when the last chunk is uploaded the file is merged on the server from chunks
     * then the chunks are deleted
     */
    public function uploadFiles(Request $request)
    {
        if ($request->hasFile('file')) {
            // To check if there is CSRF token
            //Log::debug('CSRF Token from request', ['token' => $request->header('X-CSRF-TOKEN')]);

            //Log::info($request->all());

            $file = $request->file('file');

            $fileUuid = $request->input('dzuuid');
            $chunkIndex = 0;
            $totalChunks = 1;
            if (empty($fileUuid)) {
                // the file size is smaller than chunk size
                // so we get no chunk data
                $fileUuid = Str::uuid();
            } else {
                // a chunk is here
                $chunkIndex = $request->input('dzchunkindex');
                $totalChunks = $request->input('dztotalchunkcount');
            }

            $fileExtension = $file->getClientOriginalExtension();
            $filename = $fileUuid . '.' . $fileExtension;

            Log::info('Chunk Index: ' . $chunkIndex);
            Log::info('Total Chunks: ' . $totalChunks);
            Log::info('fileExtension: ' . $fileExtension);
            Log::info('filename: ' . $filename);

            // Using cache to store chunks data
            // Retrieve or init
            $chunks = Cache::get("upload_{$filename}", [
                'chunkCount' => $totalChunks,
                'receivedChunks' => [],
                'startTime' => now()
            ]);
            // add current if not already added
            if (!in_array($chunkIndex, $chunks['receivedChunks'])) {
                $chunks['receivedChunks'][] = $chunkIndex;
            }
            // store
            Cache::put("upload_{$filename}", $chunks, now()->addMinutes(30)); // expires in 30 minutes
            Storage::putFileAs('chunks', $file, $filename . '.' . $chunkIndex);

            // Dispatch the cleanup job in 30 mins if it's the first chunk
            if (count($chunks['receivedChunks']) === 1) {
                CleanupUploadedChunksJob::dispatch($filename)->delay(now()->addMinutes(30));
            }

            Log::info('~~~~ Received chunks: ' . json_encode($chunks['receivedChunks']));

            Log::info('~~~~ Received chunks count: ' . count($chunks['receivedChunks']));

            // If this is the last chunk, combine chunks
            if (count($chunks['receivedChunks']) === (int)$totalChunks) {
                $this->combineChunks($filename, $totalChunks);
                $filenameWithFullPath = storage_path('app/private/chunks/' . $filename);
                $this->attachToModel($filenameWithFullPath);
                Cache::forget("upload_{$filename}");
                return response()->json(['status' => 'complete']);
            }

            return response()->json(['status' => 'partial', 'receivedChunks' => $chunks['receivedChunks']]);
        }

        return response()->json(['success' => false]);
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
    private function combineChunks(string $filename, int $totalChunks): void
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

            } else {
                Log::error('Chunk file does not exist: ' . $chunkPath);
            }
        }

        fclose($combinedFile);

        // delete all chunks, we have already combined file
        for ($i = 0; $i < $totalChunks; $i++) {
            $chunkPath = $path . $filename . '.' . $i;
            if (!Storage::delete("chunks/{$filename}.{$i}")) {
                Log::error("Failed to delete chunk: {$chunkPath}");
            }
        }
    }

    private function attachToModel(string $filenameWithFullPath)
    {
        $post = Post::find(1);
        try {
            $post->addMedia($filenameWithFullPath)
             ->toMediaCollection('my_collection');
        } catch (\Spatie\MediaLibrary\MediaCollections\Exceptions\FileIsTooBig $e) {
            Log::error("Uploaded file is too big.");
        }
    }
}
