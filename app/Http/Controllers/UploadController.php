<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class UploadController extends Controller
{
    public function upload(Request $request)
    {
        //dd($request);
        $file = $request->file('file');

        return response()->json(['success' => true]);
    }
}
