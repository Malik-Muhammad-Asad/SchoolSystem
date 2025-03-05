<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\Term;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class MarkSheetController extends Controller
{
    public function downloadSingle(Student $student, Term $term)
    {

        $pdf = Pdf::loadView('exports.mark-sheets', [
            'students' => collect([$student]),
            'termId' => $term->id,
        ]);

        return $pdf->download("mark-sheet-{$student->id}.pdf");
    }
}