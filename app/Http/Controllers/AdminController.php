<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class AdminController extends Controller
{
    public function index()
    {
        // Simple Admin Check (Middleware would be better, but inline for now as per plan)
        if (!Auth::user() || !Auth::user()->is_admin) {
            abort(403);
        }

        $users = User::orderBy('id', 'asc')->get();

        return view('admin.dashboard', compact('users'));
    }
}
