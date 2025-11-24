<?php

namespace Streaming\Assessments\Tests\Fixtures;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    protected $table = 'categories';

    protected $fillable = [
        'name',
    ];
}
