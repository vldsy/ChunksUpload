<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Post;

class UploadController extends Controller
{
    public function upload(Request $request)
    {
        //dd($request);
        $file = $request->file('file');

        $post = Post::find(1);
        $post->addMedia($request->file('file'))->toMediaCollection();

        return response()->json(['success' => true]);
    }
}
