<?php

namespace App\Livewire\Crud\Concerns;

trait WithDeleteModal
{
    public bool $showDeleteModal = false;
    public ?int $idBeingDeleted = null;

    public function confirmDelete(int $id): void
    {
        $this->idBeingDeleted = $id;
        $this->showDeleteModal = true;
    }

    public function cancelDelete(): void
    {
        $this->showDeleteModal = false;
        $this->idBeingDeleted = null;
    }
}
