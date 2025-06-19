<!DOCTYPE html>
<html>

<head>
    @php
    $watermarkPath = public_path('images/Logo.png');
    $watermark = "data:image/png;base64," . base64_encode(file_get_contents($watermarkPath));
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
            background-size: 200px;
            background-repeat: no-repeat;
            border: 3px solid #444;
            border-radius: 10px;
            background-position: center;
            background-color: #fff;
            position: relative;
            page-break-after: always;
        }

        .report-card::after {
            content: "";
            position: absolute;
            top: -70px;
            left: 0;
            width: 100%;
            height: 100%;
            background-image: url('{{ $watermark }}');
            background-size: 200px;
            background-repeat: no-repeat;
            background-position: center;
            opacity: 0.20;
            pointer-events: none;
            z-index: 1;
        }

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
            background-color: #6b236a;
            color: #fff;
        }

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

        .remarks-table {
            width: 100%;
            border: 1px solid #ccc;
            background: #f9f9f9;
            margin-top: 15px;
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
    </style>
</head>

<body>

    @php
    use App\Models\Exam;
    use App\Models\AcademicYear;
    use App\Models\ClassSubject;
    use App\Models\ExamResult;
    use App\Models\Term;

    $exam = Exam::where('term_id', $termId)->first();
    $term = Term::find($termId);
    $session = AcademicYear::where('is_current', true)->first();

    // Step 1: Collect scores
    $studentScores = collect($students)->map(function($stu) use ($termId, $exam) {
    $subjectIds = ClassSubject::where('class_id', $stu->class_id)->pluck('subject_id');
    $results = ExamResult::where('student_id', $stu->id)
    ->where('term_id', $termId)
    ->where('exam_id', $exam->id)
    ->whereIn('subject_id', $subjectIds)
    ->get();

    $obtained = $results->sum('obtain_number');
    return [
    'student_id' => $stu->id,
    'total_obtained' => $obtained,
    ];
    })->sortByDesc('total_obtained')->values();

    // Step 2: Assign ranks
    $rankings = [];
    $currentRank = 1;
    $previousMarks = null;
    $rankCounter = 1;

    foreach ($studentScores as $score) {
    if ($previousMarks !== null && $score['total_obtained'] < $previousMarks) {
        $currentRank=$rankCounter;
        }
        $rankings[$score['student_id']]=$currentRank;
        $previousMarks=$score['total_obtained'];
        $rankCounter++;
        }
        @endphp

        @foreach($students as $student)
        @php
        $subjects=ClassSubject::where('class_id', $student->class_id)->pluck('subject_id');
        $results = ExamResult::where('student_id', $student->id)
        ->where('term_id', $termId)
        ->where('exam_id', $exam->id)
        ->get()
        ->keyBy('subject_id');
        $totalMarks = 0;
        $totalObtained = 0;
        $logoPath = public_path('images/schoolLogo.png');
        $logo = "data:image/png;base64," . base64_encode(file_get_contents($logoPath));
        @endphp

        <div class="report-card">
            <div style="text-align: center;">
                <img src="{{ $logo }}" alt="School Logo" style="width: 550px; height: 100px; display: block; margin: 0 auto;">
            </div>

            <div class="header">
                <div class="title">Progress Report</div>
                <div class="subtitle">{{ $exam->name }} Examination - Session {{ $session->year }}</div>
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
                        <th>Subject</th>
                        <th>Total Marks</th>
                        <th>Marks Obtained</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($subjects as $subjectId)
                    @php
                    $result = $results[$subjectId] ?? null;
                    $maxMarks = $result->subject_number ?? 0;
                    $obtainedMarks = $result->obtain_number ?? 0;
                    $subjectName = optional($result?->subject)->name ?? 'N/A';
                    $totalMarks += $maxMarks;
                    $totalObtained += $obtainedMarks;
                    @endphp
                    <tr>
                        <td>{{ $subjectName }}</td>
                        <td>{{ number_format($maxMarks ,0)}}</td>
                        <td>{{ $obtainedMarks }}</td>
                    </tr>
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
            $isPass = true;
            $remarks = '';
            foreach ($subjects as $subjectId) {
            $res = $results[$subjectId] ?? null;
            $max = $res->subject_number ?? 0;
            $obt = $res->obtain_number ?? 0;
            if ($max > 0 && ($obt / $max) * 100 < 50) {
                $isPass=false;
                break;
                }
                }

                if ($isPass) {
                if ($percentage>= 90) {
                $grade = 'A + 1';
                $remarks = ' Exceptional work! Keep up the dedication and excellence.';
                } elseif ($percentage >= 80) {
                $grade = 'A';
                $remarks = ' Great job! Keep up the good work and aim even higher.';
                } elseif ($percentage >= 70) {
                $grade = 'B';
                $remarks = ' A decent performance! Aim for further improvement.';
                } elseif ($percentage >= 60) {
                $grade = 'C';
                $remarks = ' Fair effort, but there\'s room for improvement. Keep working!';
                } else {
                $grade = 'D';
                $remarks = ' Don\'t be discouraged! With hard work, you can do much better.';
                }
                } else {
                $remarks = ' Student needs to pass all subjects to receive final remarks.';
                }
                @endphp

                <table class="summary-table">
                    <tr>
                        <td><strong>Percentage:</strong> {{ $isPass ? round($percentage, 2).'%' : '   -' }}</td>
                        <td><strong>Grade:</strong> {{ $isPass ? $grade : '   -' }}</td>
                        <td><strong>Rank:</strong> {{ ($isPass && $IsRank)? ($rankings[$student->id] ?? '   -') : '   -' }}</td>
                    </tr>
                    <tr>
                        <td><strong>Result:</strong> {{ $isPass ? 'Passed' : 'Failed' }}</td>
                        <td colspan="2"></td>
                    </tr>
                </table>

                <table class="remarks-table">
                    <tr>
                        <td>Teacher's Remarks: {{ $remarks }}</td>
                    </tr>
                </table>

                @php
                $signaturePath = public_path('images/madamSignature.png');
                $signature = "data:image/png;base64," . base64_encode(file_get_contents($signaturePath));
                @endphp

                <table class="signatures-table">
                    <tr>
                        <td>Teacher's Signature</td>
                        <td>
                            <img src="{{ $signature }}" alt="Principal's Signature" style="width: 100px; height: auto; display: block; margin: 0 auto;">
                            <div>Principal's Signature</div>
                        </td>
                    </tr>
                </table>
        </div>
        @endforeach

</body>

</html>