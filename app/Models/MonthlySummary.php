<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MonthlySummary extends Model
{
    use HasFactory;
    protected $fillable = ['user_id', 'year', 'month', 'total_in', 'total_out'];
    public $timestamps = false;
}
