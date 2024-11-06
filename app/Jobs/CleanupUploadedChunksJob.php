<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class CleanupUploadedChunksJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    protected $filename;
    /**
     * Create a new job instance.
     */
    public function __construct($filename)
    {
        $this->filename = $filename;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $this->cleanupChunks($this->filename);
    }

    private function cleanupChunks($filename): void
    {
        // if there are some leftovers, delete them
        $chunks = Cache::get("upload_{$filename}", [
            'chunkCount' => 0,
            'receivedChunks' => [],
            'startTime' => now()
        ]);

        $totalChunks = $chunks['chunkCount'];

        $path = storage_path('app/private/chunks/');
        for ($i = 0; $i < $totalChunks; $i++) {
            $chunkPath = $path . $filename . '.' . $i;
            if (!Storage::delete("chunks/{$filename}.{$i}")) {
                Log::info("Failed to delete chunk: {$chunkPath}");
            }
        }

        Cache::forget("upload_{$filename}");
    }
}
