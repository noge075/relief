<?php

namespace App\Livewire\Crud\Contracts;

interface CrudResourceContract
{
    /** Eloquent model class, pl. \App\Models\User::class */
    public static function model(): string;

    /** Blade view prefix, pl. 'livewire.employees' */
    public static function viewPrefix(): string;

    /** Mely mezőkön keressünk LIKE-kal */
    public static function searchable(): array;

    /** Mely mezők szerint engedélyezett a rendezés */
    public static function sortable(): array;

    /** Livewire rules (create/update-hoz is használható) */
    public function rules(): array;

    /** default sort */
    public static function defaultSortField(): string;
    public static function defaultSortDirection(): string;
}
