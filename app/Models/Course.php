<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Course extends Model
{
    use HasFactory;

    protected $fillable = [
        'course_name',
    ];

    public function classes()
    {
        return $this->hasMany(ClassModel::class);
    }

    public function trainees()
    {
        return $this->hasManyThrough(Trainee::class, ClassModel::class, 'course_id', 'id', 'id', 'class_id')
            ->join('trainee_classes', 'classes.id', '=', 'trainee_classes.class_id')
            ->select('trainees.*');
    }
}
