<!DOCTYPE html>
<html lang="hu">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>{{ __('Attendance Sheet') }}</title>
    <style>
        @page { margin: 20px; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 10px; }
        .header { text-align: center; margin-bottom: 10px; }
        .header h1 { margin: 0; font-size: 16px; }
        .header p { margin: 2px 0; }
        .info { margin-bottom: 10px; }
        .info table { width: 100%; }
        .info td { padding: 2px; }
        .attendance-table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        .attendance-table th, .attendance-table td { border: 1px solid #ccc; padding: 3px; text-align: center; }
        .attendance-table th { background-color: #f0f0f0; font-size: 9px; }
        .weekend { background-color: #f9f9f9; color: #888; }
        .signature-section { margin-top: 20px; page-break-inside: avoid; }
        .signature-box { width: 200px; text-align: center; float: right; }
        .signature-img { max-width: 150px; max-height: 50px; margin-bottom: 5px; }
        .signature-line { border-top: 1px solid #000; margin-top: 5px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ __('Attendance Sheet') }}</h1>
        <p>{{ $year }}. {{ \Carbon\Carbon::create(null, $month)->translatedFormat('F') }}</p>
    </div>

    <div class="info">
        <table>
            <tr>
                <td><strong>{{ __('Name') }}:</strong> {{ $user->name }}</td>
                <td><strong>{{ __('Department') }}:</strong> {{ $user->department->name ?? '-' }}</td>
            </tr>
            <tr>
                <td><strong>{{ __('Employment Type') }}:</strong> {{ $user->employment_type?->label() ?? '-' }}</td>
                <td><strong>{{ __('Work Schedule') }}:</strong> {{ $user->workSchedule->name ?? '-' }}</td>
            </tr>
        </table>
    </div>

    <table class="attendance-table">
        <thead>
            <tr>
                <th>{{ __('Date') }}</th>
                <th>{{ __('Check In') }}</th>
                <th>{{ __('Check Out') }}</th>
                <th>{{ __('Worked Hours') }}</th>
                <th>{{ __('Status') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach($days as $day)
                <tr class="{{ $day['is_weekend'] || $day['is_holiday'] ? 'weekend' : '' }}">
                    <td>{{ $day['date']->format('Y.m.d') }} ({{ $day['date']->translatedFormat('D') }})</td>
                    <td>{{ $day['check_in'] ?? '-' }}</td>
                    <td>{{ $day['check_out'] ?? '-' }}</td>
                    <td>
                        @if($day['worked_hours'] > 0)
                            {{ number_format($day['worked_hours'], 2) }} h
                        @else
                            -
                        @endif
                    </td>
                    <td>
                        @if($day['status'] && $day['status'] !== '-')
                            {{ __($day['status']) }}
                        @else
                            -
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="signature-section">
        <div class="signature-box">
            @if($user->signature_path && Storage::disk('public')->exists($user->signature_path))
                <img src="data:image/png;base64,{{ base64_encode(Storage::disk('public')->get($user->signature_path)) }}" class="signature-img" alt="Signature">
            @else
                <div style="height: 50px;"></div>
            @endif
            <div class="signature-line"></div>
            <p>{{ __('Employee Signature') }}</p>
            <p>{{ now()->format('Y.m.d') }}</p>
        </div>
    </div>
</body>
</html>
