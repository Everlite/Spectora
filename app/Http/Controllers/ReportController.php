<?php

namespace App\Http\Controllers;

use App\Models\Domain;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Auth;

class ReportController extends Controller
{
    public function download(Domain $domain, \App\Services\ReportService $reportService)
    {
        \Illuminate\Support\Facades\Log::info('PDF Download requested for domain: ' . $domain->id);

        // Ensure user owns the domain
        if ($domain->user_id !== Auth::id()) {
            \Illuminate\Support\Facades\Log::warning('Unauthorized PDF access attempt.');
            abort(403);
        }

        try {
            $pdf = $reportService->generatePdf($domain);
            $filename = 'report-' . str_replace(['/', '\\', ':', '*'], '-', $domain->url) . '-' . now()->format('Y-m') . '.pdf';
            return $pdf->download($filename);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('PDF Generation failed: ' . $e->getMessage());
            \Illuminate\Support\Facades\Log::error($e->getTraceAsString());
            return redirect()->back()->with('error', 'PDF konnte nicht erstellt werden: ' . $e->getMessage());
        }
    }
}
