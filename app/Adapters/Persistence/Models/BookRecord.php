<?php

namespace App\Adapters\Persistence\Models;

use Illuminate\Database\Eloquent\Model;

// Table library.book (data-model §3). Persistence detail only: the domain never sees this class.
final class BookRecord extends Model
{
    protected $table = 'library.book';
    protected $keyType = 'string';
    public $incrementing = false;
    public $timestamps = false;
    protected $guarded = [];

    protected $casts = [
        'deleted_at' => 'immutable_datetime',
    ];
}
