<?php

namespace App\Livewire\Crud\Concerns;

use Livewire\WithPagination;

trait WithListState
{
    use WithPagination;

    public string $search = '';
    public int $perPage = 10;

    public string $sortField = 'id';
    public string $sortDirection = 'desc';

    protected array $queryString = [
        'search' => ['except' => ''],
        'perPage' => ['except' => 10],
        'sortField' => ['except' => 'id'],
        'sortDirection' => ['except' => 'desc'],
    ];

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingPerPage(): void
    {
        $this->resetPage();
    }

    public function sortBy(string $field): void
    {
        // a whitelist ellenőrzést a Base class intézi
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
            return;
        }

        $this->sortField = $field;
        $this->sortDirection = 'asc';
        $this->resetPage();
    }
}
