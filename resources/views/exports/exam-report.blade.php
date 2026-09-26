<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Exam Report - {{ $className }} - {{ $termName }}</title>
    <style>
        @page {
            size: a4 landscape;
            margin: 10mm;
        }
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 9px;
            color: #374151; /* gray-700 */
            margin: 0;
            padding: 0;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
        }
        .header h1 {
            font-size: 18px;
            margin: 0 0 5px 0;
            color: #111827; /* gray-900 */
        }
        .header p {
            font-size: 11px;
            margin: 0;
            color: #4b5563; /* gray-600 */
        }
        table {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid #d1d5db; /* gray-300 */
        }
        th, td {
            border: 1px solid #d1d5db; /* gray-300 */
            padding: 5px 3px;
            text-align: center;
        }
        th {
            background-color: #f3f4f6; /* gray-100 */
            font-weight: 600;
            color: #374151; /* gray-700 */
            font-size: 8px;
        }
        .text-left {
            text-align: left;
        }
        .font-bold {
            font-weight: bold;
        }
        .bg-blue-100 { background-color: #dbeafe; color: #1e40af; } /* blue-100 / blue-800 */
        .bg-blue-50 { background-color: #eff6ff; color: #1e40af; } /* blue-50 / blue-800 */
        .bg-green-100 { background-color: #dcfce7; color: #166534; } /* green-100 / green-800 */
        .bg-green-50 { background-color: #f0fdf4; color: #166534; } /* green-50 / green-800 */
        .bg-gray-50 { background-color: #f9fafb; } /* gray-50 */
        
        .page-break {
            page-break-after: always;
        }
        thead {
            display: table-header-group;
        }
        .watermark {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 60%;
            max-width: 500px;
            opacity: 0.1;
            z-index: -1000;
        }
        .header-image {
            width: 100%;
            max-height: 100px;
            object-fit: contain;
            margin-bottom: 10px;
        }
        .signature-container {
            margin-top: 30px;
            text-align: right;
        }
        .signature-image {
            max-width: 150px;
            max-height: 60px;
        }
    </style>
</head>
<body>
    @php
        $watermark = null;
        if (isset($schoolSettings) && $schoolSettings->watermark_image) {
            $path = public_path('storage/' . $schoolSettings->watermark_image);
            if (file_exists($path)) {
                $watermark = "data:image/png;base64," . base64_encode(file_get_contents($path));
            }
        }



        $signature = null;
        if (isset($schoolSettings) && $schoolSettings->signature_image) {
            $path = public_path('storage/' . $schoolSettings->signature_image);
            if (file_exists($path)) {
                $signature = "data:image/png;base64," . base64_encode(file_get_contents($path));
            }
        }
    @endphp

    @if($watermark)
        <img src="{{ $watermark }}" class="watermark">
    @endif

    @php
        // Split subjects into chunks. Adjust chunk size (e.g., 4 or 5) based on page width.
        $subjectChunks = $subjects->chunk(4);
    @endphp

    @foreach ($subjectChunks as $chunkIndex => $subjectChunk)
        <div class="header">

            <h1>Student Exam Report</h1>
            <p>Class: {{ $className }} | Term: {{ $termName }} | Date: {{ date('Y-m-d') }}</p>
            @if($subjectChunks->count() > 1)
                <p>Page {{ $chunkIndex + 1 }} of {{ $subjectChunks->count() }} (Subjects Collection)</p>
            @endif
        </div>

        
        <table>
            <thead>
                <tr>
                    <th rowspan="2" class="text-left" style="width: 100px;">Student Name</th>
                    <th rowspan="2" class="text-left" style="width: 100px;">Father Name</th>
                    @foreach ($subjectChunk as $subject)
                        <th colspan="{{ count($exams) + 1 }}">
                            {{ $subject->name }}
                        </th>
                    @endforeach

                    {{-- Summary headers only on the last chunk --}}
                    @if ($loop->last)
                        <th rowspan="2" class="bg-blue-100">Total</th>
                        @if ($searchedPreviousTerm)
                            <th rowspan="2" class="bg-green-100">Prev Term Total</th>
                        @endif
                        @if ($searchedExtraExams)
                            <th rowspan="2">Extra Test</th>
                        @endif
                        <th rowspan="2">Obtained Grand Total</th>
                        <th rowspan="2">Total Marks</th>
                        <th rowspan="2">Percentage</th>
                        <th rowspan="2">Grade</th>
                    @endif
                </tr>
                <tr>
                    @foreach ($subjectChunk as $subject)
                        @foreach ($exams as $examId)
                            <th>{{ $examNames[$examId] ?? 'Unknown' }}</th>
                        @endforeach
                        <th>Total</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach ($scores as $score)
                    <tr>
                        <td class="text-left font-bold">{{ $score['name'] }}</td>
                        <td class="text-left">{{ $score['father_name'] }}</td>
                        @foreach ($subjectChunk as $subject)
                            @foreach ($exams as $examId)
                                <td>
                                    {{ $score[$subject->name]['exams'][$examId] ?? '-' }}
                                </td>
                            @endforeach
                            <td class="font-bold">
                                {{ $score[$subject->name]['total'] }}
                            </td>
                        @endforeach
                        
                        {{-- Summary columns only on the last chunk --}}
                        @if ($loop->parent->last)
                            <td class="font-bold bg-blue-50">
                                {{ $score['termTotal'] }}
                            </td>
                            
                            @if ($searchedPreviousTerm)
                                <td class="font-bold bg-green-50">
                                    {{ $score['prevTermTotal'] }}
                                </td>
                            @endif
                            
                            @if ($searchedExtraExams)
                                <td class="font-bold">
                                    {{ $score['ExtraObtain'] }}
                                </td>
                            @endif
                            
                            <td class="font-bold bg-gray-50">
                                {{ $score['total'] }}
                            </td>
                            
                            <td class="font-bold bg-gray-50">
                                {{ $score['grandMaxTotal'] }}
                            </td>
                            
                            <td>
                                {{ number_format($score['percentage'], 2) }}%
                            </td>
                            
                            <td class="font-bold">
                                {{ $score['grade'] }}
                            </td>
                        @endif
                    </tr>
                @endforeach
            </tbody>
        </table>

        @if (!$loop->last)
            <div class="page-break"></div>
        @endif
    @endforeach
    
    <div style="margin-top: 20px; text-align: left; font-size: 8px; color: #9ca3af;">
        Generated on: {{ date('Y-m-d H:i:s') }}
    </div>

    @if($signature)
        <div class="signature-container">
            <img src="{{ $signature }}" class="signature-image">
            <p style="margin-top: 5px;">Principal Signature</p>
        </div>
    @endif
</body>
</html>
