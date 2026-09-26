<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\Term;
use Barryvdh\DomPDF\Facade\Pdf;
use Doctrine\DBAL\Schema\View;
use Illuminate\Http\Request;
use App\Models\SchoolSetting;

class MarkSheetController extends Controller
{
    public function downloadSingle(Request $request, Student $student, Term $term, string $type, ?int $examID)
    {
        $prevTermId = $request->query('prevTerm');

        $schoolSettings = SchoolSetting::first();

        if ($type == "single") {
            $pdf = Pdf::loadView('exports.grandTestMarkSheet', [
                'students' => collect([$student]),
                'termId' => $term->id,
                'IsRank' => false,
                'examId' => $examID,
                'schoolSettings' => $schoolSettings,
            ]);
        } elseif ($type == "final") {
            $pdf = Pdf::loadView('exports.finalMarkSheet', [
                'students' => collect([$student]),
                'termId' => $term->id,
                'prevTermId' => $prevTermId,
                'schoolSettings' => $schoolSettings,
            ]);
        } else {
            $pdf = Pdf::loadView('exports.mark-sheets', [
                'students' => collect([$student]),
                'termId' => $term->id,
                'schoolSettings' => $schoolSettings,
            ]);
        }

        return $pdf->download("mark-sheet-{$student->name}.pdf");
    }
}
