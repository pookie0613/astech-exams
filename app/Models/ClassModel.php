<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClassModel extends Model
{
    use HasFactory;

    protected $table = 'classes';

    protected $fillable = [
        'class_name',
        'start_date',
        'end_date',
        'course_id',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function trainees()
    {
        return $this->belongsToMany(Trainee::class, 'trainee_classes', 'class_id', 'trainee_id');
    }

    public function exams()
    {
        return $this->hasMany(Exam::class, 'class_id');
    }
}
