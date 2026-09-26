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
    <title>Student Final Report Card</title>
    <style>
        body {
            font-family: 'Georgia', serif;
            font-size: 14px;
            margin: 0;
            padding: 0;
            text-align: center;
        }

        .report-card {
            background-size: 200px;
            background-repeat: no-repeat;
            border: 3px solid #444;
            border-radius: 10px;
            background-position: center;
            background-color: #fff;
            position: relative;
        }

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
            padding: 10px;
            border: 1px solid #ccc;
            margin-bottom: 10px;
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
            padding: 6px;
            text-align: center;
            border: 1px solid #ccc;
        }

        th {
            background-color: #6b236a;
            color: #fff;
        }

        .summary-table {
            width: 100%;
            border: 1px solid #ccc;
            margin-top: 10px;
        }

        .summary-table td {
            padding: 10px;
            text-align: center;
            font-weight: bold;
            border: 1px solid #ccc;
            background: #f1f1f1;
        }

        .remarks-table {
            width: 100%;
            border: 1px solid #ccc;
            background: #f9f9f9;
            margin-top: 10px;
            border-collapse: collapse;
        }

        .remarks-table td {
            padding: 10px;
            text-align: left;
            font-weight: bold;
            border: none;
        }

        .signatures-table {
            width: 100%;
            margin-top: 15px;
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
        }
        input[type="checkbox"] {
            width: 14px;
            height: 14px;
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

        $exams = Exam::where('term_id', $termId)->get();
        $term = Term::find($termId);
        $prevTerm = Term::find($prevTermId);
        
        $studentIds = $students->pluck('id');
        
        $rankQuery = FinalResult::where('term_id', $termId)
            ->where('class_id', $students->first()->class_id ?? null)
            ->where('report_type', 'final')
            ->get();
            
        $session = AcademicYear::where('is_current', true)->first();
        
        // Pre-fetch all results for the class to avoid N+1 queries
        $allCurrentResults = ExamResult::whereIn('student_id', $studentIds)
            ->where('term_id', $termId)
            ->get()
            ->groupBy('student_id');
            
        $allPrevResults = collect();
        if ($prevTermId) {
            $allPrevResults = ExamResult::whereIn('student_id', $studentIds)
                ->where('term_id', $prevTermId)
                ->get()
                ->groupBy('student_id');
        }
    @endphp

    @foreach($students as $student)
        @php
            $subjects = ClassSubject::where('class_id', $student->class_id)->pluck('subject_id');
            
            $currentResults = $allCurrentResults->get($student->id, collect())->groupBy('subject_id');
            $prevResults = $allPrevResults->get($student->id, collect())->groupBy('subject_id');
            
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
                    <img src="{{ $logo }}" alt="School Logo" style="width: 550px; height: 100px; display: block; margin: 0 auto;">
                @endif
            </div>
            <div class="header">
                <div class="title">Progress Report</div>
                <div class="subtitle">Final Examination - Session {{ $session->year }}</div>
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
                        @foreach($exams as $exam)
                            @php
                                // Get max marks for this exam from any of the current results
                                $examMaxMarks = 0;
                                foreach($allCurrentResults as $studentResults) {
                                    $res = $studentResults->where('exam_id', $exam->id)->first();
                                    if ($res) {
                                        $examMaxMarks = $res->subject_number;
                                        break;
                                    }
                                }
                            @endphp
                            <th>{{ $exam->name }} ({{ (int)$examMaxMarks }})</th>
                        @endforeach
                        @php
                            // Calculate total max marks for previous term for a single subject
                            $prevTermTotalMax = 0;
                            if ($allPrevResults->isNotEmpty()) {
                                $firstStudentPrevResults = $allPrevResults->first();
                                // Group by subject to get results for one subject
                                $resultsBySubject = $firstStudentPrevResults->groupBy('subject_id');
                                if ($resultsBySubject->isNotEmpty()) {
                                    $firstSubjectResults = $resultsBySubject->first();
                                    $prevTermTotalMax = $firstSubjectResults->sum('subject_number');
                                }
                            }
                        @endphp
                        <th>Half Yearly ({{ (int)$prevTermTotalMax }})</th>
                        <th>Total Marks</th>
                        <th>Marks Obtained</th>
                    </tr>
                </thead>
                <tbody>
                    @php 
                        $grandSubjectTotalMax = 0;
                        $grandSubjectTotalObtained = 0;
                    @endphp
                    @foreach($subjects as $subjectId)
                        @php
                            $subjectCurrentResults = $currentResults[$subjectId] ?? collect();
                            $subjectPrevResults = $prevResults[$subjectId] ?? collect();
                            
                            $subjectTermObtained = 0;
                            $subjectTermMax = 0;
                            
                            $subjectPrevObtained = $subjectPrevResults->sum('obtain_number');
                            $subjectPrevMax = $subjectPrevResults->sum('subject_number');
                        @endphp
                        <tr>
                            <td>{{ optional(ExamResult::where('subject_id', $subjectId)->first()?->subject)->name ?? 'N/A' }}</td>
                            @foreach($exams as $exam)
                                @php
                                    $res = $subjectCurrentResults->where('exam_id', $exam->id)->first();
                                    $obt = $res->obtain_number ?? 0;
                                    $max = $res->subject_number ?? 0;
                                    $subjectTermObtained += $obt;
                                    $subjectTermMax += $max;
                                @endphp
                                <td>{{ fmod($obt, 1) == 0 ? (int)$obt : number_format($obt, 2) }}</td>
                            @endforeach
                            <td>{{ fmod($subjectPrevObtained, 1) == 0 ? (int)$subjectPrevObtained : number_format($subjectPrevObtained, 2) }}</td>
                            
                            @php
                                $totalMaxRow = $subjectTermMax + $subjectPrevMax;
                                $totalObtRow = $subjectTermObtained + $subjectPrevObtained;
                                $grandSubjectTotalMax += $totalMaxRow;
                                $grandSubjectTotalObtained += $totalObtRow;
                            @endphp
                            <td><strong>{{ (int)$totalMaxRow }}</strong></td>
                            <td><strong>{{ fmod($totalObtRow, 1) == 0 ? (int)$totalObtRow : number_format($totalObtRow, 2) }}</strong></td>
                        </tr>
                    @endforeach
                    <tr>
                        <td colspan="{{ count($exams) + 2 }}"><strong>Grand Total</strong></td>
                        <td><strong>{{ (int)$grandSubjectTotalMax }}</strong></td>
                        <td><strong>{{ fmod($grandSubjectTotalObtained, 1) == 0 ? (int)$grandSubjectTotalObtained : number_format($grandSubjectTotalObtained, 2, '.', '') }}</strong></td>
                    </tr>
                </tbody>
            </table>

            @php
                $percentage = $grandSubjectTotalMax > 0 ? ($grandSubjectTotalObtained / $grandSubjectTotalMax) * 100 : 0;
                $studentResultRecord = $rankQuery->where('student_id', $student->id)->first();
                $isPass = $studentResultRecord && $studentResultRecord->grade != 'F';
                
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
                    <td><strong>Percentage:</strong> {{ $isPass ? round($percentage, 2).'%' : ' -' }}</td>
                    <td><strong>Grade:</strong> {{ $isPass ? $grade : ' -' }}</td>
                    <td><strong>Rank:</strong> {{ $isPass ? ($studentResultRecord->rank ?? ' -') : ' -' }}</td>
                </tr>
                <tr>
                    <td colspan="3"><strong>Result:</strong> {{ $isPass ? 'Passed' : 'Failed' }}</td>
                </tr>
            </table>

                <table class="remarks-table">
                <tr>
                    <td>Teacher's Remarks: {{ $remarks }}</td>
                </tr>
            </table>

            <table class="remarks-table" style="margin-top: 10px;">
                <tr>
                    <td colspan="4" style="text-align: left;">
                        <strong>Social Behaviour:</strong>
                    </td>
                </tr>
                <tr>
                    <td style="width: 25%;"><input type="checkbox" disabled> Obedient</td>
                    <td style="width: 25%;"><input type="checkbox" disabled> Punctual</td>
                    <td style="width: 25%;"><input type="checkbox" disabled> Responsible</td>
                    <td style="width: 25%;"><input type="checkbox" disabled> Hardworking</td>
                </tr>
                <tr>
                    <td><input type="checkbox" disabled> Respectful</td>
                    <td><input type="checkbox" disabled> Cooperative</td>
                    <td><input type="checkbox" disabled> Attentive</td>
                    <td><input type="checkbox" disabled> Disciplined</td>
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
                    $signaturePath = public_path('images/Afshan.png'); // Fallback
                    if (file_exists($signaturePath)) {
                        $signature = "data:image/png;base64," . base64_encode(file_get_contents($signaturePath));
                    }
                }
            @endphp
            
            <table class="signatures-table">
                <tr>
                    <td style="text-align: center; vertical-align: bottom; padding-bottom: 10px;">
                        Teacher's Signature
                    </td>
                    <td style="text-align: center;">
                        @if($signature)
                            <img src="{{ $signature }}" alt="Principal's Signature" style="width: 80px; height: auto; display: block; margin: 0 auto;">
                        @endif
                        <div>Principal's Signature</div>
                    </td>
                </tr>
            </table>
        </div>
    @endforeach

</body>

</html>
