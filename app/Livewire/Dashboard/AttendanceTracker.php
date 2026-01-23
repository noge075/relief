<?php

namespace App\Livewire\Dashboard;

use App\Models\AttendanceLog;
use Carbon\Carbon;
use Flux\Flux;
use Livewire\Component;

class AttendanceTracker extends Component
{
    public $currentLog;

    public function mount()
    {
        $this->loadCurrentLog();
    }

    public function loadCurrentLog()
    {
        $this->currentLog = AttendanceLog::where('user_id', auth()->id())
            ->where('date', Carbon::today())
            ->whereNull('check_out')
            ->latest()
            ->first();
    }

    public function checkIn()
    {
        AttendanceLog::create([
            'user_id' => auth()->id(),
            'date' => Carbon::today(),
            'check_in' => Carbon::now(),
            'status' => 'present',
        ]);

        $this->loadCurrentLog();
        Flux::toast(__('Checked in successfully.'), variant: 'success');
    }

    public function checkOut()
    {
        if ($this->currentLog) {
            $checkOut = Carbon::now();
            $workedHours = $this->currentLog->check_in->diffInHours($checkOut);

            $this->currentLog->update([
                'check_out' => $checkOut,
                'worked_hours' => $workedHours,
            ]);
            
            $this->loadCurrentLog();
            Flux::toast(__('Checked out successfully.'), variant: 'success');
        }
    }

    public function render()
    {
        return view('livewire.dashboard.attendance-tracker');
    }
}
