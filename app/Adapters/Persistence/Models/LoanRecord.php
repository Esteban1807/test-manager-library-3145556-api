<?php

namespace App\Adapters\Persistence\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class LoanRecord extends Model
{
    protected $table = 'library.loan';
    protected $keyType = 'string';
    public $incrementing = false;
    public $timestamps = false;
    protected $guarded = [];

    protected $casts = [
        'loaned_at' => 'immutable_datetime',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(LoanItemRecord::class, 'loan_id');
    }
}
