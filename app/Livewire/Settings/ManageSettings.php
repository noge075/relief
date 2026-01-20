<?php

namespace App\Livewire\Settings;

use App\Enums\PermissionType;
use App\Models\Setting;
use Flux\Flux;
use Livewire\Component;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class ManageSettings extends Component
{
    use AuthorizesRequests;

    public $hoLimitDays;
    public $hoLimitPeriod;

    public function mount()
    {
        $this->authorize(PermissionType::MANAGE_SETTINGS->value);
        
        $this->hoLimitDays = Setting::where('key', 'ho_limit_days')->value('value') ?? 1;
        $this->hoLimitPeriod = Setting::where('key', 'ho_limit_period')->value('value') ?? 14;
    }

    public function save()
    {
        $this->authorize(PermissionType::MANAGE_SETTINGS->value);
        
        $this->validate([
            'hoLimitDays' => 'required|integer|min:0',
            'hoLimitPeriod' => 'required|integer|min:1',
        ]);

        Setting::updateOrCreate(['key' => 'ho_limit_days'], ['value' => $this->hoLimitDays]);
        Setting::updateOrCreate(['key' => 'ho_limit_period'], ['value' => $this->hoLimitPeriod]);

        Flux::toast(__('Settings saved successfully.'), variant: 'success');
    }

    public function render()
    {
        return view('livewire.settings.manage-settings')->title(__('System Settings'));
    }
}
