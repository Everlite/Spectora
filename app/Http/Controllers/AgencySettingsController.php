<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;

class AgencySettingsController extends Controller
{
    public function updateLogo(Request $request)
    {
        $request->validate([
            'agency_logo' => 'required|image|max:2048', // Max 2MB
        ]);

        $user = Auth::user();

        if ($request->hasFile('agency_logo')) {
            // Delete old logo if exists
            if ($user->agency_logo_path) {
                Storage::disk('public')->delete($user->agency_logo_path);
            }

            // Store new logo
            $path = $request->file('agency_logo')->store('logos', 'public');

            // Update user
            $user->agency_logo_path = $path;
            $user->save();

            return back()->with('status', 'agency-logo-updated');
        }

        return back()->withErrors(['agency_logo' => 'File upload failed.']);
    }
}
