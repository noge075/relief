<?php

namespace App\Http\Controllers;

use Dcblogdev\MsGraph\Facades\MsGraph;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MicrosoftAuthController extends Controller
{
    // 1. Indítás: Átirányít a Microsoft bejelentkező oldalra
    public function connect()
    {
        // Csak admin férhet hozzá!
        if (!Auth::user()?->hasRole('super-admin')) {
            abort(403);
        }
        return MsGraph::connect();
    }

    // 2. Visszatérés: A Microsoft ide küld vissza, a csomag elmenti a tokent
    public function callback()
    {
        return MsGraph::connect(); // A csomag automatikusan kezeli a visszatérést is
    }

    // 3. Kijelentkezés
    public function logout()
    {
        return MsGraph::disconnect();
    }
}