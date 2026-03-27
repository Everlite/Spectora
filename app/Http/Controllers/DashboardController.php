<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $domains = $user->domains()
            ->withCount(['analyticsVisits as visitors_today' => function ($query) {
                $query->whereDate('created_at', now());
            }])
            ->orderBy('url')
            ->get();

        return view('dashboard', compact('domains'));
    }
}
