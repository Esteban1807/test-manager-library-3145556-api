<?php

namespace App\Adapters\Persistence\Models;

use Illuminate\Database\Eloquent\Model;

final class CategoryRecord extends Model
{
    protected $table = 'library.category';
    protected $keyType = 'string';
    public $incrementing = false;
    public $timestamps = false;
    protected $guarded = [];
}
