<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\Attributes\On;

class UserAvatar extends Component
{
    #[On('avatar-updated')]
    public function refresh()
    {
        // This method is intentionally left empty.
        // The #[On] attribute will trigger a re-render of the component.
    }

    public function render()
    {
        return view('livewire.user-avatar');
    }
}
