<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * This is a sample model
 * It has no data. Just used as an example with Spatie Media library
 *
*/
class Post extends Model implements HasMedia
{
    use InteractsWithMedia;
    use HasFactory;
}
