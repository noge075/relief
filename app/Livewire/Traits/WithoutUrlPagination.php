<?php

namespace App\Livewire\Traits;

trait WithoutUrlPagination
{
    public function initializeWithoutUrlPagination()
    {
        $this->page = 1;
    }

    public function getQueryString()
    {
        $queryString = parent::getQueryString();
        
        // Eltávolítjuk a 'page' kulcsot a query stringből
        if (isset($queryString['page'])) {
            unset($queryString['page']);
        }
        
        return $queryString;
    }
}
