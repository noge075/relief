<?php

namespace App\Livewire\Employees;

use App\Livewire\Crud\BaseCrudIndex;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class Index extends BaseCrudIndex
{
    public static function model(): string
    {
        return User::class;
    }

    public function title(): string
    {
        return 'Employees';
    }

    public static function viewPrefix(): string
    {
        return 'livewire.employees';
    }

    public static function searchable(): array
    {
        return ['name', 'email'];
    }

    public static function sortable(): array
    {
        return ['name', 'email', 'id', 'created_at'];
    }

    public static function defaultSortField(): string
    {
        return 'name';
    }

    public static function defaultSortDirection(): string
    {
        return 'asc';
    }

    public function rules(): array
    {
        return [];
    }

    protected function baseQuery(): Builder
    {
        return User::query();
    }
}
