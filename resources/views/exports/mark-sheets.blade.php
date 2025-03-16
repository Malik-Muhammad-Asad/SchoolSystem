<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Student Mark Sheets</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            font-size: 12px;
        }

        .page-break {
            page-break-after: always;
        }

        .mark-sheet {
            padding: 20px;
            max-width: 800px;
            margin: 0 auto;
        }

        .header {
            text-align: center;
            margin-bottom: 20px;
        }

        .header img {
            max-width: 300px;
        }

        .title {
            font-size: 18px;
            font-weight: bold;
            text-align: center;
            margin: 10px 0;
        }

        .student-info {
            margin-bottom: 20px;
            border-bottom: 1px solid #000;
            padding-bottom: 10px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        table,
        th,
        td {
            border: 1px solid #000;
        }

        th,
        td {
            padding: 5px;
            text-align: left;
        }

        th {
            background-color: #f2f2f2;
        }

        .summary {
            margin-top: 20px;
            border: 1px solid #000;
            padding: 10px;
        }

        .assessment {
            margin-top: 20px;
            border: 1px solid #000;
            padding: 10px;
        }

        .signatures {
            margin-top: 30px;
            display: flex;
            justify-content: space-between;
        }

        .signature {
            text-align: center;
            width: 30%;
        }

        .grading {
            margin-top: 20px;
            border: 1px solid #000;
            padding: 10px;
        }
    </style>
</head>

<body>
    @foreach($students as $student)
        @php
            $results = App\Models\ExamResult::where('student_id', $student->id)
                ->where('term_id', $termId)
                ->get()
                ->groupBy('subject_id');

            $term = App\Models\Term::find($termId);

            // Calculate totals and percentages
            $totalMarks = 0;
            $totalObtained = 0;

            // Get attendance
            // $attendance = $student->attendances()->count();
            $attendance = 270;
            $totalDays = 180; // Modify as needed
        @endphp

        <div class="mark-sheet">
            <div class="header">
                <img src="{{ public_path('images/school-logo.jpeg') }}" alt="School Logo">
                <div class="title">Detailed Mark sheet</div>
                <div>{{ $term->name }} Examination (session 2024-25)</div>
            </div>

            <div class="student-info">
                <strong>Student name:</strong> {{ $student->name }}
                <strong>Father name:</strong> {{ $student->father_name }}<br>
                <strong>Class:</strong> {{ $student->classes->name }}
                <strong>Section:</strong> {{ $student->classes->name  }}
                <strong>Roll.no:</strong> {{ $student->id }}
            </div>

            <table>
                <thead>
                    <tr>
                        <th>Subjects</th>
                        <th>Oral</th>
                        <th>Secured marks</th>
                        <th>Written</th>
                        <th>Secured marks</th>
                        <th>1st grand test</th>
                        <th>Secured marks</th>
                        <th>Total Marks</th>
                        <th>Total Obtained Marks</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach(App\Models\Subject::all() as $subject)
                                @php
                                    
                                    $subjectResults = $results[$subject->id] ?? collect();
                                    $oralMax =  20;
                                    $writtenMax =  50;
                                    $testMax = $subject->name == 'Nazra' || $subject->name == 'Summer vacation' ? 0 : 30;
                                    $totalMax = $oralMax + $writtenMax + $testMax;

                                    $oralObtained = $subjectResults->where('exam_id', 1)->first()->obtain_number ?? 0;
                                    $writtenObtained = $subjectResults->where('exam_id', 2)->first()->obtain_number ?? 0;
                                    $testObtained = $subjectResults->where('exam_id', operator: 3)->first()->obtain_number ?? 0;
                                    $totalObtained += $oralObtained + $writtenObtained + $testObtained;
                                    $totalMarks += $totalMax;
                                @endphp
                                <tr>
                                    <td>{{ $subject->name }}</td>
                                    <td>{{ $oralMax }}</td>
                                    <td>{{ $oralObtained }}</td>
                                    <td>{{ $writtenMax }}</td>
                                    <td>{{ $writtenObtained }}</td>
                                    <td>{{ $testMax }}</td>
                                    <td>{{ $testObtained }}</td>
                                    <td>{{ $totalMax }}</td>
                                    <td>{{ $oralObtained + $writtenObtained + $testObtained }}</td>
                                </tr>
                    @endforeach
                    <tr>
                        <td colspan="7"><strong>Grand total</strong></td>
                        <td><strong>{{ $totalMarks }}</strong></td>
                        <td><strong>{{ $totalObtained }}</strong></td>
                    </tr>
                </tbody>
            </table>

            <div class="summary">
                <strong>Summary of result:</strong><br>
                <strong>Percentage:</strong> {{ round(($totalObtained / $totalMarks) * 100, 2) }}%
                <strong>Grade:</strong>
                @php
                    $percentage = ($totalObtained / $totalMarks) * 100;
                    $grade = 'F';
                    if ($percentage >= 80)
                        $grade = 'A+';
                    elseif ($percentage >= 70)
                        $grade = 'A';
                    elseif ($percentage >= 60)
                        $grade = 'B';
                    elseif ($percentage >= 50)
                        $grade = 'C';
                @endphp
                {{ $grade }}
                <strong>Rank:</strong> {{ $student->rank ?? '' }}<br>
                <strong>Attendance {{ $attendance }} out of {{ $totalDays }}</strong>
            </div>

            <div class="assessment">
                <strong>Personal Assessment:</strong><br>
                Regularity: {{ $student->regularity ?? '' }}
                Behavior toward Teachers: {{ $student->behavior_teachers ?? '' }}<br>
                Confidence: {{ $student->confidence ?? '' }}
                Behavior toward Students: {{ $student->behavior_students ?? '' }}<br>
                Extra circular activates: {{ $student->extra_activities ?? '' }}
                Extra ORT & Phonic Reader: {{ $student->extra_ort ?? '' }}<br>
                Listening: {{ $student->listening ?? 'G' }}
                Recognizing: {{ $student->recognizing ?? 'G' }}
                Reading: {{ $student->reading ?? 'G' }}
            </div>

            <div>
                <strong>Remarks:</strong> {{ $student->remarks ?? '' }}
            </div>

            <div class="signatures">
                <div class="signature">Teacher's signature</div>
                <div class="signature">Principal's signature</div>
                <div class="signature">Parent's signature</div>
            </div>

            <div class="grading">
                <strong>Grading scale:</strong><br>
                <strong>E</strong> - Excellent
                <strong>VG</strong> - Very Good
                <strong>G</strong> - Good
                <strong>S</strong> - Satisfactory
                <strong>NPA</strong> - Need Proper Attention<br>
                <strong>A+</strong> Above 80%
                <strong>A</strong> - 70-79%
                <strong>B</strong> - 60-69%
                <strong>C</strong> - 50-59%
                <strong>F</strong> - Below 50% Fail
            </div>
        </div>

        @if(!$loop->last)
            <div class="page-break"></div>
        @endif
    @endforeach
</body>

</html>