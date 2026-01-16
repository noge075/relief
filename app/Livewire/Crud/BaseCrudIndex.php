<?php

namespace App\Livewire\Crud;

use App\Livewire\Crud\Concerns\WithDeleteModal;
use App\Livewire\Crud\Concerns\WithListState;
use App\Livewire\Crud\Contracts\CrudResourceContract;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Livewire\Component;

abstract class BaseCrudIndex extends Component implements CrudResourceContract
{
    use WithListState;
    use WithDeleteModal;

    public function mount(): void
    {
        $this->sortField = static::defaultSortField();
        $this->sortDirection = static::defaultSortDirection();
    }

    protected function resourceName(): string
    {
        return Str::snake(class_basename(static::model())); // pl. user
    }

    protected function resourcePlural(): string
    {
        return Str::plural($this->resourceName()); // users
    }

    protected function title(): string
    {
        return Str::headline($this->resourcePlural()); // Users
    }

    /**
     * Default: route('users.create') jelleg
     * Override-olható ha eltér a route naming.
     */
    protected function createUrl(): ?string
    {
        $name = $this->resourcePlural();
        $route = $name . '.create';

        return \Illuminate\Support\Facades\Route::has($route)
            ? route($route)
            : null;
    }

    /** Alap query – felülírható ha kell join/with/where stb. */
    protected function baseQuery(): Builder
    {
        $modelClass = static::model();
        return $modelClass::query();
    }

    protected function applySearch(Builder $query): Builder
    {
        $term = trim($this->search);
        if ($term === '') {
            return $query;
        }

        $fields = static::searchable();

        return $query->where(function (Builder $q) use ($fields, $term) {
            foreach ($fields as $field) {
                $q->orWhere($field, 'like', "%{$term}%");
            }
        });
    }

    protected function applySort(Builder $query): Builder
    {
        $sortable = static::sortable();

        // whitelist: ha nem engedélyezett, essünk vissza a default-ra
        if (!in_array($this->sortField, $sortable, true)) {
            $this->sortField = static::defaultSortField();
        }

        $direction = $this->sortDirection === 'asc' ? 'asc' : 'desc';

        return $query->orderBy($this->sortField, $direction);
    }

    public function getItemsProperty()
    {
        $query = $this->baseQuery();
        $query = $this->applySearch($query);
        $query = $this->applySort($query);

        return $query->paginate($this->perPage);
    }

    public function delete(): void
    {
        if (!$this->idBeingDeleted) {
            return;
        }

        /** @var class-string<Model> $modelClass */
        $modelClass = static::model();
        $model = $modelClass::findOrFail($this->idBeingDeleted);

        $model->delete();

        $this->cancelDelete();
        session()->flash('message', 'Deleted successfully.');
        $this->resetPage();
    }

    public function render(): View
    {
        return view(static::viewPrefix() . '.index', [
            'items' => $this->items,
            'crudMeta' => [
                'title' => $this->title(),
                'resourceName' => $this->resourceName(),
                'resourcePlural' => $this->resourcePlural(),
                'createUrl' => $this->createUrl(),
            ],
        ]);
    }
}
