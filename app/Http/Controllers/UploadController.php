<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Post;
use Illuminate\Support\Facades\Storage;

class UploadController extends Controller
{
    public function upload(Request $request)
    {
        //dd($request);
        $file = $request->file('file');

        $chunkIndex = $request->input('dzchunkindex');
        $totalChunks = $request->input('dztotalchunkcount');
        $filename = $request->input('dzuuid') . '.' . $file->getClientOriginalExtension();

        $file->storeAs('chunks', $filename . '.' . $chunkIndex);

        if ($chunkIndex == $totalChunks - 1) {
            $this->combineChunks($filename, $totalChunks);

            $post = Post::find(1);
            $post->addMedia(storage_path('app/chunks/' . $filename))
                  ->toMediaCollection('your_collection');
        }

        return response()->json(['success' => true]);
    }

    private function combineChunks($filename, $totalChunks)
    {
        $path = storage_path('app/chunks/');
        $combinedFile = fopen($path . $filename, 'w');

        for ($i = 0; $i < $totalChunks; $i++) {
            $chunk = fopen($path . $filename . '.' . $i, 'r');
            stream_copy_to_stream($chunk, $combinedFile);
            fclose($chunk);
            Storage::delete('chunks/' . $filename . '.' . $i);
        }

        fclose($combinedFile);
    }
}
