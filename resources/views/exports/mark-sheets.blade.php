<!DOCTYPE html>
<html>

<head>
    @php
        $watermarkPath = public_path('images/Logo.png'); // Ensure this file exists
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
            /* Adjust this value (0.1-0.2 for very light) */
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

        F

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

        php
    </style>
</head>

<body>

    @php
        use App\Models\Exam;
        use App\Models\ClassSubject;
        use App\Models\ExamResult;
        use App\Models\Term;

        $exams = Exam::where('term_id', $termId)->get();
        $term = Term::find($termId);
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
            $path = public_path('images/schoolLogo.png'); // Ensure this file exists
            $logo = "data:image/png;base64," . base64_encode(file_get_contents($path));
        @endphp




        <div class="report-card">
            <div style="text-align: center;">
                <img src="{{ $logo }}" alt="School Logo"
                    style="width: 550px; height: 100px; display: block; margin: 0 auto;">
            </div>
            <div class="header">


                <div class="title">Progress Report</div>
                <div class="subtitle">{{ $term->name }} Examination - Session 2024-25</div>
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
                            <th>{{ $exam->name }}</th>
                            <th>Secured Marks</th>
                        @endforeach
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
                                                <td>{{ number_format($maxMarks, 0) }}</td>
                                                <td>{{ $obtainedMarks }}</td>
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
                        <td colspan="{{ 2 * count($exams) + 1 }}"><strong>Grand Total</strong></td>
                        <td><strong>{{ $totalMarks }}</strong></td>
                        <td><strong>{{ $totalObtained }}</strong></td>
                    </tr>
                </tbody>
            </table>

            <!-- Summary Table (With Attendance) -->
            <table class="summary-table">
                <tr>
                    <td><strong>Percentage:</strong> {{ round(($totalObtained / $totalMarks) * 100, 2) }}%</td>
                    <td><strong>Grade:</strong>
                        @php
                            $percentage = ($totalObtained / $totalMarks) * 100;
                            if ($percentage >= 80) {
                                $grade = 'A+1';
                            } elseif ($percentage >= 70) {
                                $grade = 'A';
                            } elseif ($percentage >= 60) {
                                $grade = 'B';
                            } elseif ($percentage >= 50) {
                                $grade = 'C';
                            } elseif ($percentage >= 40) {
                                $grade = 'D';
                            } elseif ($percentage >= 30) {
                                $grade = 'E';
                            } else {
                                $grade = 'F';
                            }
                        @endphp
                        {{ $grade }}
                    </td>

                    <td><strong>Rank:</strong> __________________</td>
                </tr>
                <tr>
                    <td><strong>Result:</strong> __________________</td>
                    <td colspan="2"><strong>Attendance:</strong> _______ / _______</td>
                </tr>
            </table>

            <!-- Remarks Section -->
            <table class="remarks-table">
                <tr>
                    <td>Teacher's Remarks: __________________________________________________________________________</td>
                </tr>
                <tr>
                    <td>_____________________________________________________________________________________________</td>
                </tr>
            </table>
            @php
                $signaturePath = public_path('images/madamSignature.png'); // Ensure this file exists
                $signature = "data:image/png;base64," . base64_encode(file_get_contents($signaturePath));
            @endphp
            <!-- Signatures Table -->
            <!-- Signatures Table -->
            <!-- Signatures Table -->
            <table class="signatures-table">
                <tr>
                    <td style="text-align: center; vertical-align: bottom; padding-bottom: 10px;">
                        Teacher's Signature
                    </td>
                    <td style="text-align: center;">
                        <img src="{{ $signature }}" alt="Principal's Signature"
                            style="width: 100px; height: auto; display: block; margin: 0 auto;">
                        <div>Principal's Signature</div>
                    </td>
                </tr>
            </table>



        </div>
    @endforeach

</body>

</html>