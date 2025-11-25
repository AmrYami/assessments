<?php

namespace Yami\Assessments\Tests\Fixtures;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    protected $table = 'categories';

    protected $fillable = [
        'name',
    ];
}
