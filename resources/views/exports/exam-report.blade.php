<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Exam Report - {{ $className }} - {{ $termName }}</title>
    <style>
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 10px;
            margin: 10px;
        }
        .header {
            text-align: center;
            margin-bottom: 15px;
        }
        .header h1 {
            margin-bottom: 5px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 4px;
            text-align: center;
            font-size: 9px;
        }
        th {
            background-color: #f2f2f2;
            font-weight: bold;
        }
        .student-name, .father-name {
            text-align: left;
        }
        .center {
            text-align: center;
        }
        .bold {
            font-weight: bold;
        }
        .page-break {
            page-break-after: always;
        }
        .fixed-columns {
            position: sticky;
            left: 0;
            background-color: #fff;
            z-index: 1;
        }
        .summary-row {
            background-color: #f9f9f9;
        }
        @page {
            size: landscape;
        }
    </style>
</head>
<body>
    <!-- First Page: Student Information + First Half of Subjects -->
    <div class="header">
        <h1>Student Exam Report</h1>
        <p>Class: {{ $className }} | Term: {{ $termName }} | Date: {{ date('Y-m-d') }}</p>
        <p>Page 1 of 2</p>
    </div>
    
    <table>
        <thead>
            <tr>
                <th rowspan="2" width="12%" class="fixed-columns">Student Name</th>
                <th rowspan="2" width="12%" class="fixed-columns">Father Name</th>
                @php $subjectCount = count($subjects); @endphp
                @foreach ($subjects as $index => $subject)
                    @if ($index < ceil($subjectCount/2))
                        <th colspan="{{ count($exams) + 1 }}" class="center">
                            {{ $subject->name }}
                        </th>
                    @endif
                @endforeach
            </tr>
            <tr>
                @foreach ($subjects as $index => $subject)
                    @if ($index < ceil($subjectCount/2))
                        @foreach ($exams as $examId)
                            <th>{{ $examNames[$examId] ?? 'Unknown' }}</th>
                        @endforeach
                        <th>Total</th>
                    @endif
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach ($scores as $score)
                <tr>
                    <td class="student-name fixed-columns">{{ $score['name'] }}</td>
                    <td class="father-name fixed-columns">{{ $score['father_name'] }}</td>
                    @foreach ($subjects as $index => $subject)
                        @if ($index < ceil($subjectCount/2))
                            @foreach ($exams as $examId)
                                <td>{{ $score[$subject->name]['exams'][$examId] ?? '-' }}</td>
                            @endforeach
                            <td class="bold">{{ $score[$subject->name]['total'] }}</td>
                        @endif
                    @endforeach
                </tr>
            @endforeach
        </tbody>
    </table>
    
    <div style="margin-top: 20px; text-align: right;">
        <p>Page 1 - Generated on: {{ date('Y-m-d H:i') }}</p>
    </div>

    <div class="page-break"></div>
    
    <!-- Second Page: Student Information + Second Half of Subjects + Summary -->
    <div class="header">
        <h1>Student Exam Report (Continued)</h1>
        <p>Class: {{ $className }} | Term: {{ $termName }} | Date: {{ date('Y-m-d') }}</p>
        <p>Page 2 of 2</p>
    </div>
    
    <table>
        <thead>
            <tr>
                <th rowspan="2" width="12%" class="fixed-columns">Student Name</th>
                <th rowspan="2" width="12%" class="fixed-columns">Father Name</th>
                @foreach ($subjects as $index => $subject)
                    @if ($index >= ceil($subjectCount/2))
                        <th colspan="{{ count($exams) + 1 }}" class="center">
                            {{ $subject->name }}
                        </th>
                    @endif
                @endforeach
                <th rowspan="2" class="center" width="5%">1st Grand Test</th>
                <th rowspan="2" class="center" width="5%">Total</th>
                <th rowspan="2" class="center" width="5%">Percentage</th>
                <th rowspan="2" class="center" width="5%">Grade</th>
            </tr>
            <tr>
                @foreach ($subjects as $index => $subject)
                    @if ($index >= ceil($subjectCount/2))
                        @foreach ($exams as $examId)
                            <th>{{ $examNames[$examId] ?? 'Unknown' }}</th>
                        @endforeach
                        <th>Total</th>
                    @endif
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach ($scores as $score)
                <tr>
                    <td class="student-name fixed-columns">{{ $score['name'] }}</td>
                    <td class="father-name fixed-columns">{{ $score['father_name'] }}</td>
                    @foreach ($subjects as $index => $subject)
                        @if ($index >= ceil($subjectCount/2))
                            @foreach ($exams as $examId)
                                <td>{{ $score[$subject->name]['exams'][$examId] ?? '-' }}</td>
                            @endforeach
                            <td class="bold">{{ $score[$subject->name]['total'] }}</td>
                        @endif
                    @endforeach
                    <td class="bold">{{ $score['ExtraObtain'] }}</td>
                    <td class="bold">{{ $score['total'] }}</td>
                    <td>{{ number_format($score['percentage'], 2) }}%</td>
                    <td>{{ $score['grade'] }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
    
    <div style="margin-top: 20px; text-align: right;">
        <p>Page 2 - Generated on: {{ date('Y-m-d H:i') }}</p>
    </div>
</body>
</html>