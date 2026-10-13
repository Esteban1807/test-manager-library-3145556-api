<?php

namespace App\Adapters\Persistence\Models;

use Illuminate\Database\Eloquent\Model;

final class MemberRecord extends Model
{
    protected $table = 'library.member';
    protected $keyType = 'string';
    public $incrementing = false;
    public $timestamps = false;
    protected $guarded = [];
}
