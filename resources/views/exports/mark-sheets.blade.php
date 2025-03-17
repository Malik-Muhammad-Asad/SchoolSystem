<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Student Report Card</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            background-color: #f5f5f5;
        }

        .report-card {
            width: 100%;
            max-width: 1100px;
            margin: 20px auto;
            background: #fff;
            padding: 30px;
            border: 2px solid #333;
            box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.2);
            border-radius: 10px;
            overflow-x: auto;
        }

        .header {
            text-align: center;
            margin-bottom: 20px;
        }

        .header img {
            max-width: 100px;
        }

        .title {
            font-size: 22px;
            font-weight: bold;
            margin-top: 10px;
            color: #333;
        }

        .student-info {
            background: #f2f2f2;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: left;
            font-size: 14px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            background: #fff;
            table-layout: auto;
        }

        th, td {
            padding: 8px;
            text-align: center;
            border: 1px solid #ddd;
            font-size: 14px;
            word-wrap: break-word;
        }

        th {
            background-color: #007bff;
            color: #fff;
            font-weight: bold;
        }

        .summary {
            margin-top: 20px;
            padding: 15px;
            background: #e9ecef;
            border-radius: 8px;
            font-size: 16px;
            text-align: center;
        }

        .signatures {
            margin-top: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            width: 100%;
            flex-wrap: nowrap;
            gap: 10px;
        }

        .signature {
            text-align: center;
            flex: 1;
            font-weight: bold;
            border-top: 1px solid #000;
            padding-top: 5px;
            white-space: nowrap;
        }
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

        <div class="report-card">
            <div class="header">
                <img src="{{ public_path('images/school-logo.jpeg') }}" alt="School Logo">
                <div class="title">Student Report Card</div>
                <div>{{ $term->name }} Examination (Session 2024-25)</div>
            </div>

            <div class="student-info">
                <p><strong>Name:</strong> {{ $student->name }} | 
                <strong>Father's Name:</strong> {{ $student->father_name }} | 
                <strong>Class:</strong> {{ optional($student->classes)->name ?? 'N/A' }} | 
                <strong>Roll No:</strong> {{ $student->id }}</p>
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
                        <th>Obtained Marks</th>
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
                                <td>{{ $maxMarks }}</td>
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

            <div class="summary">
                <strong>Percentage:</strong> {{ round(($totalObtained / $totalMarks) * 100, 2) }}%<br>
                <strong>Grade:</strong> 
                {{ ($totalObtained / $totalMarks) * 100 >= 80 ? 'A+' : (($totalObtained / $totalMarks) * 100 >= 70 ? 'A' : (($totalObtained / $totalMarks) * 100 >= 60 ? 'B' : 'C')) }}
            </div>

            <div class="signatures">
                <div class="signature">Teacher's Signature</div>
                <div class="signature">Principal's Signature</div>
                <div class="signature">Parent's Signature</div>
            </div>
        </div>
    @endforeach

</body>

</html>
