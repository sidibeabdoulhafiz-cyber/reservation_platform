<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function index()
    {
        $users = User::all();
        return view('admin.dashboard', compact('users'));
    }

    public function updateRole(Request $request, User $user)
    {
        $request->validate(['role' => 'required|in:admin,user']);
        $user->update(['role' => $request->role]);
        return redirect()->back()->with('success', 'Rôle mis à jour.');
    }

    public function toggleStatus(User $user)
    {
        $user->update(['actif' => !$user->actif]);
        return redirect()->back()->with('success', 'Statut mis à jour.');
    }
}
