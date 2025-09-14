<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Trainee extends Model
{
    use HasFactory;

    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'dob',
    ];

    protected $casts = [
        'dob' => 'date',
    ];

    public function classes()
    {
        return $this->belongsToMany(ClassModel::class, 'trainee_classes', 'trainee_id', 'class_id');
    }

    public function courses()
    {
        return $this->belongsToMany(Course::class, 'trainee_classes', 'trainee_id', 'class_id')
            ->join('classes', 'trainee_classes.class_id', '=', 'classes.id')
            ->select('courses.*');
    }

    public function results()
    {
        return $this->hasMany(Result::class);
    }

    public function exams()
    {
        return $this->hasManyThrough(Exam::class, Result::class, 'trainee_id', 'id', 'id', 'exam_id');
    }
}
