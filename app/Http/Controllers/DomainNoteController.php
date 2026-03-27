<?php

namespace App\Http\Controllers;

use App\Models\Domain;
use App\Models\DomainNote;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DomainNoteController extends Controller
{
    public function index(Domain $domain)
    {
        try {
            if ($domain->user_id !== Auth::id()) {
                abort(403);
            }

            return response()->json($domain->notes()->orderBy('created_at', 'desc')->get());
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function store(Request $request, Domain $domain)
    {
        try {
            if ($domain->user_id !== Auth::id()) {
                abort(403);
            }

            $request->validate([
                'content' => 'required|string',
            ]);

            // We add user_id just in case the table requires it (based on user's screenshot)
            $note = $domain->notes()->create([
                'content' => $request->content,
            ]);

            return response()->json($note);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function update(Request $request, DomainNote $note)
    {
        try {
            if ($note->domain->user_id !== Auth::id()) {
                abort(403);
            }

            $request->validate([
                'content' => 'required|string',
            ]);

            $note->update([
                'content' => $request->content,
            ]);

            return response()->json($note);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function destroy(DomainNote $note)
    {
        try {
            if ($note->domain->user_id !== Auth::id()) {
                abort(403);
            }

            $note->delete();

            return response()->json(['message' => 'Note deleted']);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
