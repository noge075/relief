<?php

namespace App\Livewire\Traits;

trait WithSorting
{
    public $sortCol = 'created_at';
    public $sortAsc = true;

    public function sortBy($column)
    {
        if ($this->sortCol === $column) {
            $this->sortAsc = !$this->sortAsc;
        } else {
            $this->sortCol = $column;
            $this->sortAsc = true;
        }
    }
}
