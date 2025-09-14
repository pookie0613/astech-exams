<?php

namespace App\Http\Controllers;

use App\Models\Exam;
use App\Models\ClassModel;
use App\Models\Trainee;
use App\Models\Result;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ExamController extends Controller
{
    public function index(): JsonResponse
    {
        $exams = Exam::with(['class.course', 'results.trainee'])->get();
        return response()->json($exams);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'class_id' => 'required|exists:classes,id',
            'exam_name' => 'required|string|max:255',
            'exam_date' => 'required|date'
        ]);

        $exam = Exam::create($request->all());
        $exam->load(['class.course', 'results.trainee']);

        return response()->json($exam, 201);
    }

    public function show(Exam $exam): JsonResponse
    {
        $exam->load(['class.course', 'results.trainee', 'trainees']);
        return response()->json($exam);
    }

    public function update(Request $request, Exam $exam): JsonResponse
    {
        $request->validate([
            'class_id' => 'required|exists:classes,id',
            'exam_name' => 'required|string|max:255',
            'exam_date' => 'required|date'
        ]);

        $exam->update($request->all());
        $exam->load(['class.course', 'results.trainee']);

        return response()->json($exam);
    }

    public function destroy(Exam $exam): JsonResponse
    {
        $exam->delete();
        return response()->json(null, 204);
    }

    public function getByClass(ClassModel $class): JsonResponse
    {
        $exams = $class->exams()->with(['results.trainee'])->get();
        return response()->json($exams);
    }

    public function getEnrolledTrainees(Exam $exam): JsonResponse
    {
        $trainees = $exam->trainees()->with(['results' => function($query) use ($exam) {
            $query->where('exam_id', $exam->id);
        }])->get();

        return response()->json($trainees);
    }

    public function addTraineeToExam(Request $request, Exam $exam): JsonResponse
    {
        $request->validate([
            'trainee_id' => 'required|exists:trainees,id'
        ]);

        // Check if trainee is enrolled in the class
        $enrolled = $exam->trainees()->where('trainee_id', $request->trainee_id)->exists();

        if (!$enrolled) {
            return response()->json(['message' => 'Trainee is not enrolled in this class'], 400);
        }

        // Check if result already exists
        $existingResult = Result::where('exam_id', $exam->id)
            ->where('trainee_id', $request->trainee_id)
            ->first();

        if ($existingResult) {
            return response()->json(['message' => 'Trainee already has a result for this exam'], 400);
        }

        // Create empty result
        $result = Result::create([
            'exam_id' => $exam->id,
            'trainee_id' => $request->trainee_id,
            'result' => null
        ]);

        $result->load('trainee');
        return response()->json($result, 201);
    }

    public function removeTraineeFromExam(Request $request, Exam $exam): JsonResponse
    {
        $request->validate([
            'trainee_id' => 'required|exists:trainees,id'
        ]);

        Result::where('exam_id', $exam->id)
            ->where('trainee_id', $request->trainee_id)
            ->delete();

        return response()->json(['message' => 'Trainee removed from exam']);
    }

    public function updateResult(Request $request, Exam $exam, Trainee $trainee): JsonResponse
    {
        $request->validate([
            'result' => 'required|string|max:255'
        ]);

        $result = Result::where('exam_id', $exam->id)
            ->where('trainee_id', $trainee->id)
            ->first();

        if (!$result) {
            return response()->json(['message' => 'No result found for this trainee and exam'], 404);
        }

        $result->update(['result' => $request->result]);
        $result->load('trainee');

        return response()->json($result);
    }

    public function getExamResults(Exam $exam): JsonResponse
    {
        $results = $exam->results()->with('trainee')->get();
        return response()->json($results);
    }

    public function getAvailableTrainees(Exam $exam): JsonResponse
    {
        // Get trainees enrolled in the class but not yet added to the exam
        $enrolledTraineeIds = $exam->trainees()->pluck('trainee_id');

        $availableTrainees = \App\Models\Trainee::whereHas('classes', function($query) use ($exam) {
            $query->where('class_id', $exam->class_id);
        })->whereNotIn('id', $enrolledTraineeIds)->get();

        return response()->json($availableTrainees);
    }

    public function bulkAddTrainees(Request $request, Exam $exam): JsonResponse
    {
        $request->validate([
            'trainee_ids' => 'required|array',
            'trainee_ids.*' => 'exists:trainees,id'
        ]);

        $addedResults = [];

        foreach ($request->trainee_ids as $traineeId) {
            // Check if trainee is enrolled in the class
            $enrolled = $exam->trainees()->where('trainee_id', $traineeId)->exists();

            if (!$enrolled) {
                continue; // Skip if not enrolled
            }

            // Check if result already exists
            $existingResult = Result::where('exam_id', $exam->id)
                ->where('trainee_id', $traineeId)
                ->first();

            if (!$existingResult) {
                $result = Result::create([
                    'exam_id' => $exam->id,
                    'trainee_id' => $traineeId,
                    'result' => null
                ]);

                $result->load('trainee');
                $addedResults[] = $result;
            }
        }

        return response()->json($addedResults, 201);
    }
}
