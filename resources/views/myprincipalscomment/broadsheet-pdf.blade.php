<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Broadsheet</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; margin: 20px; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th, td { border: 1px solid #000; padding: 8px; text-align: center; }
        th { background-color: #f0f0f0; }
        .header { text-align: center; margin-bottom: 30px; }
        .student-photo { width: 50px; height: 50px; object-fit: cover; border-radius: 50%; }
        .comment { text-align: left; white-space: pre-line; font-size: 11px; }
        .analytics { font-size: 11px; background-color: #f9f9f9; }
    </style>
</head>
<body>
    <div class="header">
        <h2>Student Broadsheet Report</h2>
        <h3>{{ $schoolclass->schoolclass }} {{ $schoolclass->arm?->arm ?? '' }} - {{ $schoolterm }} Term, {{ $schoolsession }}</h3>
        <p><strong>Class Average:</strong> {{ $classAnalytics['average'] }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th>SN</th>
                <th>Photo</th>
                <th>Student</th>
                <th>Avg</th>
                <th>Pos</th>
                @foreach ($subjects as $subject)
                    <th>{{ $subject }}</th>
                @endforeach
                <th>Comment</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($students as $index => $student)
                @php
                    $analytics = $studentAnalytics[$student->id] ?? [];
                    $comment = $profiles[$student->id] ?? $intelligentComments[$student->id] ?? 'No comment';
                    $picture = $student->picture ? asset('storage/student_avatars/' . basename($student->picture)) : asset('images/default-avatar.jpg');
                @endphp
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td><img src="{{ $picture }}" class="student-photo" alt=""></td>
                    <td style="text-align:left;">
                        <strong>{{ $student->lastname }} {{ $student->firstname }}</strong><br>
                        Adm: {{ $student->admissionNo }}
                    </td>
                    <td><strong>{{ $analytics['average'] ?? '0' }}</strong></td>
                    <td><strong>{{ $analytics['position_text'] ?? '-' }}</strong></td>
                    @foreach ($subjects as $subject)
                        @php
                            $score = $scores->where('student_id', $student->id)
                                           ->whereIn('subject_id', \App\Models\Subject::where('subject', $subject)->pluck('id'))
                                           ->first();
                        @endphp
                        <td>{{ $score?->total ?? '-' }}</td>
                    @endforeach
                    <td class="comment">{{ $comment }}</td>
                </tr>
                <tr class="analytics">
                    <td colspan="{{ count($subjects) + 5 }}" style="text-align:left; font-size:10px;">
                        <strong>Analytics:</strong> 
                        Total: {{ $analytics['total_score'] ?? 0 }} | 
                        Subjects: {{ $analytics['subjects'] ?? 0 }} | 
                        Grades: A({{ $analytics['grade_counts']['A'] ?? 0 }}) 
                        B({{ $analytics['grade_counts']['B'] ?? 0 }}) 
                        C({{ $analytics['grade_counts']['C'] ?? 0 }}) 
                        D/F({{ ($analytics['grade_counts']['D'] ?? 0) + ($analytics['grade_counts']['F'] ?? 0) }})
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div style="text-align:center; margin-top:50px; font-size:11px;">
        Generated on {{ now()->format('F j, Y') }} | Powered by ViteSchool | Developed by Qudroid Systems
    </div>
</body>
</html>