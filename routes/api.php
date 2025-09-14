<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ExamController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Exams API
Route::apiResource('exams', ExamController::class);

// Additional exam routes
Route::prefix('exams/{exam}')->group(function () {
    Route::get('/trainees', [ExamController::class, 'getEnrolledTrainees']);
    Route::get('/available-trainees', [ExamController::class, 'getAvailableTrainees']);
    Route::post('/trainees', [ExamController::class, 'addTraineeToExam']);
    Route::delete('/trainees', [ExamController::class, 'removeTraineeFromExam']);
    Route::put('/trainees/{trainee}/result', [ExamController::class, 'updateResult']);
    Route::get('/results', [ExamController::class, 'getExamResults']);
    Route::post('/trainees/bulk', [ExamController::class, 'bulkAddTrainees']);
});

// Results routes
Route::get('/exams/{exam}/results', [ExamController::class, 'getExamResults']);

// Class-based exam routes
Route::get('/classes/{class}/exams', [ExamController::class, 'getByClass']);

// Health check
Route::get('/health', function () {
    return response()->json(['status' => 'healthy', 'service' => 'exams']);
});
