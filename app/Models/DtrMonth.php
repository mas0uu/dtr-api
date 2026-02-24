<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DtrMonth extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'month',
        'year',
        'is_fulfilled',
    ];

    public function user(){
        return $this->belongsTo(User::class);
    }
    public function rows(){
        return $this->hasMany(DtrRow::class);
    }
}
