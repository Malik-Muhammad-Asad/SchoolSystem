<!DOCTYPE html>
<html>

<head>
    @php
        $watermark = null;
        if (isset($schoolSettings) && $schoolSettings->watermark_image) {
            $watermarkPath = public_path('storage/' . $schoolSettings->watermark_image);
            if (file_exists($watermarkPath)) {
                $watermark = "data:image/png;base64," . base64_encode(file_get_contents($watermarkPath));
            }
        }
    @endphp
    <meta charset="utf-8">
    <title>Student Report Card</title>
    <style>
        body {
            font-family: 'Georgia', serif;
            font-size: 14px;
            margin: 0;
            padding: 0;
            text-align: center;
        }

        .report-card {
            /* other properties remain the same */
            background-size: 200px;
            background-repeat: no-repeat;

            border: 3px solid #444;
            border-radius: 10px;
            background-position: center;
            background-color: #fff;
            /* Keep background white */
            position: relative;
        }

        /* Add this after the .report-card class */
        @if($watermark)
        .report-card::after {
            content: "";
            position: absolute;
            top: 25%;
            left: 15%;
            width: 70%;
            height: 50%;
            background-image: url('{{ $watermark }}');
            background-size: contain;
            background-repeat: no-repeat;
            background-position: center;
            opacity: 0.15;
            pointer-events: none;
            z-index: 1;
        }
        @endif

        .header {
            text-align: center;
            margin-bottom: 15px;
        }

        .header img {
            max-width: 80px;
        }

        .title {
            font-size: 22px;
            font-weight: bold;
            text-transform: uppercase;
            color: #666;
            letter-spacing: 1px;
        }

        .subtitle {
            font-size: 16px;
            font-weight: bold;
            color: #666;
        }

        .student-info {
            padding: 12px;
            border: 1px solid #ccc;
            margin-bottom: 15px;
            font-size: 14px;
            background: #f9f9f9;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        th,
        td {
            padding: 8px;
            text-align: center;
            border: 1px solid #ccc;
        }

        th {
            background-color: #611d60ff;
            /* Change from #444 to a lighter gray */
            color: #fff;
        }

        /* Summary Table */
        .summary-table {
            width: 100%;
            border: 1px solid #ccc;
            margin-top: 15px;
        }

        .summary-table td {
            padding: 10px;
            text-align: center;
            font-weight: bold;
            border: 1px solid #ccc;
            background: #f1f1f1;
        }

        /* Remarks Section */
        .remarks-table {
            width: 100%;
            border: 1px solid #ccc;
            background: #f9f9f9;
            margin-top: 15px;
            border-collapse: collapse;
            /* This ensures a single border */
        }

        .remarks-table td {
            padding: 10px;
            text-align: left;
            font-weight: bold;
            border: none;
            /* Remove individual cell borders */
        }

        /* Signature Section */
        .signatures-table {
            width: 100%;
            margin-top: 20px;
            border: none;
        }

        .signatures-table td {
            width: 50%;
            text-align: center;
            font-weight: bold;
            padding-top: 20px;
            border-top: 2px solid #000;
        }

        .report-card {
            page-break-after: always;
            /* Ensure new page for each report card */
        }
        input[type="checkbox"] {
    width: 14px;
    height: 14px;
    accent-color: #000; /* For modern browsers */
    margin-right: 6px;
    vertical-align: middle;
}
    </style>
</head>

<body>

    @php
        use App\Models\Exam;
        use App\Models\ClassSubject;
        use App\Models\ExamResult;
        use App\Models\Term;
        use App\Models\FinalResult;
        use App\Models\AcademicYear;

        $exams = Exam::where('id', $examId)->get();
        $term = Term::find($termId);
        $rankQuery = FinalResult::where('term_id', $termId)
        ->where('class_id', $students->first()->class_id ?? null)
        ->where('exam_id',$exams->first()->id)->get();
        $session = AcademicYear::where('is_current', true)->first();
    @endphp

    @foreach($students as $student)
        @php
            $subjects = ClassSubject::where('class_id', $student->class_id)->pluck('subject_id');
            $results = ExamResult::where('student_id', $student->id)
                ->where('term_id', $termId)
                ->get()
                ->groupBy('subject_id');
            $totalMarks = 0;
            $totalObtained = 0;
        @endphp
        @php
            $logo = null;
            if (isset($schoolSettings) && $schoolSettings->header_image) {
                $path = public_path('storage/' . $schoolSettings->header_image);
                if (file_exists($path)) {
                    $logo = "data:image/png;base64," . base64_encode(file_get_contents($path));
                }
            }
            if (!$logo) {
                $path = public_path('images/schoolLogo.png'); // Fallback
                if (file_exists($path)) {
                    $logo = "data:image/png;base64," . base64_encode(file_get_contents($path));
                }
            }
        @endphp




        <div class="report-card">
            <div style="text-align: center;">
                @if($logo)
                    <img src="{{ $logo }}" alt="School Logo"
                        style="width: 550px; height: 100px; display: block; margin: 0 auto;">
                @endif
            </div>
            <div class="header">


                <div class="title">Progress Report</div>
                <div class="subtitle">{{ $exams->first()->name }} Examination - Session {{ $session->year }}</div>
            </div>

            <div class="student-info">
                <p><strong>Student Name:</strong> {{ $student->name }} |
                    <strong>Father Name:</strong> {{ $student->father_name }} |
                    <strong>Class:</strong> {{ optional($student->classes)->name ?? 'N/A' }}
                </p>
            </div>

            <table>
                <thead>
                    <tr>
                        <th>Subjects</th>
                        <!-- @foreach($exams as $exam)
                            <th>{{ $exam->name }}</th>
                            <th>Secured Marks</th>
                        @endforeach -->
                        <th>Total Marks</th>
                        <th>Marks Obtained</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($subjects as $subjectId)
                                @php
                                    $subjectResults = $results[$subjectId] ?? collect();
                                    $subjectTotalMax = 0;
                                    $subjectTotalObtained = 0;
                                @endphp
                                <tr>
                                    <td>{{ optional($subjectResults->first()->subject)->name ?? 'N/A' }}</td>
                                    @foreach($exams as $exam)
                                                @php
                                                    $maxMarks = $subjectResults->where('exam_id', $exam->id)->first()->subject_number ?? 0;
                                                    $obtainedMarks = $subjectResults->where('exam_id', $exam->id)->first()->obtain_number ?? 0;
                                                    $subjectTotalMax += $maxMarks;
                                                    $subjectTotalObtained += $obtainedMarks;
                                                @endphp
                                                <!-- <td>{{ (int)$maxMarks }}</td>
                                                <td>
                                                   {{ fmod($obtainedMarks, 1) == 0 ? (int)$obtainedMarks : number_format($obtainedMarks, 2) }}
                                                </td> -->

                                    @endforeach
                                    <td><strong>{{ $subjectTotalMax }}</strong></td>
                                    <td><strong>{{ $subjectTotalObtained }}</strong></td>
                                </tr>
                                @php
                                    $totalMarks += $subjectTotalMax;
                                    $totalObtained += $subjectTotalObtained;
                                @endphp
                    @endforeach
                    <tr>
                        <td><strong>Grand Total</strong></td>
                        <td><strong>{{ $totalMarks }}</strong></td>
                        <td><strong>{{ $totalObtained }}</strong></td>
                    </tr>
                </tbody>
            </table>
@php
    $percentage = $totalMarks > 0 ? ($totalObtained / $totalMarks) * 100 : 0;
    $StudentRank =$rankQuery->where('student_id', $student->id)->first();
    $isPass = $StudentRank->grade != 'F';
    $remarks = '';

  

    if ($isPass) {
        if ($percentage >= 80) {
            $grade = 'A + 1';
            $remarks = 'Exceptional work! Keep up the dedication and excellence.';
        } elseif ($percentage >= 70) {
            $grade = 'A';
            $remarks = 'Great job! Keep up the good work and aim even higher.';
        } elseif ($percentage >= 60) {
            $grade = 'B';
            $remarks = 'A decent performance! Aim for further improvement.';
        } elseif ($percentage >= 50) {
            $grade = 'C';
            $remarks = 'Fair effort, but there\'s room for improvement. Keep working!';
        } else {
            $grade = 'D';
            $remarks = 'Don\'t be discouraged! With hard work, you can do much better.';
        }
    } else {
        $remarks = 'Student needs to pass all subjects to receive final remarks.';
    }
@endphp

            <table class="summary-table">
                    <tr>
                        <td><strong>Percentage:</strong> {{ $isPass ? round($percentage, 2).'%' : '   -' }}</td>
                        <td><strong>Grade:</strong> {{ $isPass ? $grade : '   -' }}</td>
                       <!-- <td><strong>Rank:</strong> {{ ($isPass )? ($StudentRank->rank ?? '   -') : '   -' }}</td>-->
                    </tr>
                    <tr>
                        <td colspan="3"><strong>Result:</strong> {{ $isPass ? 'Passed' : 'Failed' }}</td>
                    </tr>
                </table>
            <!-- Remarks Section -->
           <table class="remarks-table">
                    <tr>
                        <td>Teacher's Remarks: {{ $remarks }}</td>
                    </tr>
                </table>
                
            @php
                $signature = null;
                if (isset($schoolSettings) && $schoolSettings->signature_image) {
                    $signaturePath = public_path('storage/' . $schoolSettings->signature_image);
                    if (file_exists($signaturePath)) {
                        $signature = "data:image/png;base64," . base64_encode(file_get_contents($signaturePath));
                    }
                }
                if (!$signature) {
                    $signaturePath = public_path('images/madamSignature.png'); // Fallback
                    if (file_exists($signaturePath)) {
                        $signature = "data:image/png;base64," . base64_encode(file_get_contents($signaturePath));
                    }
                }
            @endphp
            <!-- Signatures Table -->
           
            <table class="signatures-table">
                <tr>
                    <td style="text-align: center; vertical-align: bottom; padding-bottom: 10px;">
                        Teacher's Signature
                    </td>
                    <td style="text-align: center;">
                        @if($signature)
                            <img src="{{ $signature }}" alt="Principal's Signature"
                                 style="width: 80px; height: auto; display: block; margin: 0 auto;">
                        @endif
                        <div>Principal's Signature</div>
                    </td>
                </tr>
            </table>



        </div>
    @endforeach

</body>

</html>