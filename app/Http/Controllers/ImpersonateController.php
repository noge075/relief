<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ImpersonateController extends Controller
{
    public function impersonate(Request $request, User $user)
    {
        $currentUser = Auth::user();

        // Jogosultság ellenőrzés
        // HR/Admin bárkit, Manager csak a beosztottjait
        if ($currentUser->can('view all users')) {
            // OK
        } elseif ($currentUser->can('view users') && $user->manager_id === $currentUser->id) {
            // OK
        } else {
            abort(403, 'Unauthorized to impersonate this user.');
        }

        // Nem lehet saját magát, vagy szuperadmint (ha nem szuperadmin)
        if ($user->id === $currentUser->id) {
            return back()->with('error', 'Cannot impersonate yourself.');
        }
        
        if ($user->hasRole('super-admin') && !$currentUser->hasRole('super-admin')) {
             abort(403, 'Cannot impersonate a Super Admin.');
        }

        // Csomag használata
        $currentUser->impersonate($user);

        return redirect()->route('dashboard');
    }

    public function stopImpersonating()
    {
        Auth::user()->leaveImpersonation();
        return redirect()->route('employees.index');
    }
}
