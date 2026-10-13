<?php

namespace App\Adapters\Persistence\Models;

use Illuminate\Database\Eloquent\Model;

final class LoanItemRecord extends Model
{
    protected $table = 'library.loan_item';
    protected $keyType = 'string';
    public $incrementing = false;
    public $timestamps = false;
    protected $guarded = [];

    protected $casts = [
        'due_at' => 'immutable_datetime',
        'returned_at' => 'immutable_datetime',
    ];
}
