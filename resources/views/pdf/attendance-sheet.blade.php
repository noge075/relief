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
        .weekend, .holiday { background-color: #f9f9f9; color: #888; }

        .footer-container { width: 100%; margin-top: 20px; page-break-inside: avoid; }
        .footer-container td { vertical-align: bottom; }

        .summary-table { width: 65%; border-collapse: collapse; }
        .summary-table td { border: 1px solid #ccc; padding: 4px; font-weight: bold; }
        .summary-table .label { text-align: right; background-color: #f0f0f0; }

        .signature-box { width: 200px; text-align: center; float: right; }
        .signature-img { max-width: 150px; max-height: 50px; margin-bottom: 5px; }
        .signature-line { border-top: 1px solid #000; margin-top: 5px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ __('Attendance Sheet') }}</h1>
        <p>{{ $year }}. {{ $monthName }}</p>
    </div>

    <div class="info">
        <table>
            <tr>
                <td><strong>{{ __('Name') }}:</strong> {{ $user->name }}</td>
                <td><strong>{{ __('Department') }}:</strong> {{ $user->departments->pluck('name')->implode(', ') }}</td>
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
                @php
                    $isWeekend = $day->status === \App\Enums\AttendanceStatusType::WEEKEND;
                    $isHoliday = $day->status === \App\Enums\AttendanceStatusType::HOLIDAY;
                @endphp
                <tr class="{{ $isWeekend || $isHoliday ? 'weekend' : '' }}">
                    <td>{{ $day->date->format('Y.m.d') }} ({{ $day->date->translatedFormat('D') }})</td>
                    <td>{{ $day->check_in ? $day->check_in->format('H:i') : '-' }}</td>
                    <td>{{ $day->check_out ? $day->check_out->format('H:i') : '-' }}</td>
                    <td>
                        @if($day->worked_hours > 0)
                            {{ number_format($day->worked_hours, 2) }} h
                        @else
                            -
                        @endif
                    </td>
                    <td>
                        {{ $day->status->label() }}
                        @if($isHoliday && $day->holiday_name)
                            ({{ $day->holiday_name }})
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="footer-container">
        <tr>
            <td>
                <table class="summary-table">
                    <tr>
                        <td class="label">{{ __('Total Worked Hours') }}:</td>
                        <td>{{ number_format($summaryStats['total_worked_hours'], 2) }} óra</td>
                    </tr>
                    <tr>
                        <td class="label">{{ __('Present') }}:</td>
                        <td>{{ $summaryStats['present'] }} nap</td>
                    </tr>
                    <tr>
                        <td class="label">{{ __('Home Office') }}:</td>
                        <td>{{ $summaryStats['home_office'] }} nap</td>
                    </tr>
                    <tr>
                        <td class="label">{{ __('Vacation') }}:</td>
                        <td>{{ $summaryStats['vacation'] }} nap</td>
                    </tr>
                    <tr>
                        <td class="label">{{ __('Sick Leave') }}:</td>
                        <td>{{ $summaryStats['sick_leave'] }} nap</td>
                    </tr>
                </table>
            </td>
            <td>
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
            </td>
        </tr>
    </table>
</body>
</html>
