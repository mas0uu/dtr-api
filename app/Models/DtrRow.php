<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DtrRow extends Model
{
    use HasFactory;

    protected $fillable = [
        'dtr_month_id',
        'date',
        'day',
        'time_in',
        'time_in_meridiem',
        'time_out',
        'time_out_meridiem',
        'total_minutes',
        'status',
    ];

    public function month(){
        return $this->belongsTo(DtrMonth::class, 'dtr_month_id');
    }
}
