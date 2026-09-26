<?php
namespace App\Helpers;

use App\Models\FinalResult;

class ResultHelper
{
    // public static function calculateAndSave($classID, $studentId, $examId, $termId, $subjectResults)
    // {
    //     $totalMax = 0;
    //     $totalObtained = 0;
    //     $isFail = false;
    //     foreach ($subjectResults as $result) {
    //         $max = (int) ($result->subject_number ?? 0);
    //         $obt = (float) ($result->obtain_number ?? 0);

    //         if ($max > 0 && ($obt / $max) * 100 < 50) {
    //             $isFail = true;
    //         }

    //         $totalMax += $max;
    //         $totalObtained += $obt;
    //     }

    //     $percentage = $totalMax > 0 ? ($totalObtained / $totalMax) * 100 : 0;
    //     $grade = $isFail ? 'F' : self::getGrade($percentage);

    //     $final = FinalResult::updateOrCreate(
    //         [
    //             'student_id' => $studentId,
    //             'exam_id' => $examId,
    //             'term_id' => $termId,
    //             'class_id'=>$classID
    //         ],
    //         [
    //             'total_marks' => $totalMax,
    //             'obtained_marks' => $totalObtained,
    //             'percentage' => round($percentage, 2),
    //             'grade' => $grade,
    //             'is_locked' => true,
    //         ]
    //     );

    //     return $final;
    // }
public static function calculateAndSave($classID, $studentId, $examId, $termId, $subjectResults, $reportType = null)
{
    $totalMax = 0;
    $totalObtained = 0;
    $isFail = false;

    // Step 1: Group results by subject_id
    $groupedSubjects = collect($subjectResults)
        ->groupBy('subject_id')
        ->map(function ($items) {
            return [
                'subject_id' => $items->first()->subject_id,
                'total_max' => $items->sum('subject_number'),
                'total_obtained' => $items->sum('obtain_number'),
            ];
        });

    // Step 2: Loop through grouped subjects
    foreach ($groupedSubjects as $subject) {
        $max = (float) $subject['total_max'];
        $obt = (float) $subject['total_obtained'];

        // Check if failed in this subject
        if ($max > 0 && ($obt / $max) * 100 < 50) {
            $isFail = true;
        }

        // Add to grand total
        $totalMax += $max;
        $totalObtained += $obt;
    }

    // Step 3: Calculate overall percentage & grade
    $percentage = $totalMax > 0 ? ($totalObtained / $totalMax) * 100 : 0;
    $grade = $isFail ? 'F' : self::getGrade($percentage);

    // Step 4: Save or update final result
    $final = FinalResult::updateOrCreate(
        [
            'student_id' => $studentId,
            'exam_id' => $examId,
            'term_id' => $termId,
            'class_id' => $classID,
            'report_type' => $reportType,
        ],
        [
            'total_marks' => $totalMax,
            'obtained_marks' => $totalObtained,
            'percentage' => round($percentage, 2),
            'grade' => $grade,
            'is_locked' => true,
        ]
    );

    return $final;
}

    private static function getGrade($percentage)
    {
        return match (true) {
            $percentage >= 80 => 'A+',
            $percentage >= 70 => 'A',
            $percentage >= 60 => 'B',
            $percentage >= 50 => 'C',
            $percentage >= 40 => 'D',
            default => 'F',
        };
    }
}
