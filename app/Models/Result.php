<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Result extends Model
{
    use HasFactory;

    protected $fillable = [
        'exam_id',
        'trainee_id',
        'result',
    ];

    public function trainee()
    {
        return $this->belongsTo(Trainee::class);
    }

    public function exam()
    {
        return $this->belongsTo(Exam::class);
    }
}
