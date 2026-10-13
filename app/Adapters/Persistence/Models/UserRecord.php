<?php

namespace App\Adapters\Persistence\Models;

use Illuminate\Database\Eloquent\Model;

final class UserRecord extends Model
{
    protected $table = 'library.user';
    protected $keyType = 'string';
    public $incrementing = false;
    public $timestamps = false;
    protected $guarded = [];
}
