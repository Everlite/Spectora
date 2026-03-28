<?php

use App\Http\Controllers\SettingsController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Http\Request;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth'])
    ->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/domains/{domain}/history', [\App\Http\Controllers\DomainController::class, 'history'])->name('domains.history');
    Route::get('/domains/{domain}', [\App\Http\Controllers\DomainController::class, 'show'])->name('domains.show');
    Route::get('/domains/{domain}/status', [\App\Http\Controllers\DomainController::class, 'status'])->name('domains.status');
    Route::post('/domains/{domain}/analyze', [\App\Http\Controllers\DomainController::class, 'analyze'])->name('domains.analyze');
    Route::post('/domains', [\App\Http\Controllers\DomainController::class, 'store'])->name('domains.store');
    Route::delete('/domains/{domain}', [\App\Http\Controllers\DomainController::class, 'destroy'])->name('domains.destroy');
    Route::get('/domains/{domain}/analytics', [App\Http\Controllers\AnalyticsController::class, 'show'])->name('domains.analytics');
    Route::get('/domains/{domain}/report', [\App\Http\Controllers\ReportController::class, 'download'])->name('domains.report');
    Route::get('/settings', [SettingsController::class, 'edit'])->name('settings.edit');
    Route::patch('/settings', [SettingsController::class, 'update'])->name('settings.update');
    
    // Agency Settings
    Route::post('/profile/agency-logo', [\App\Http\Controllers\AgencySettingsController::class, 'updateLogo'])->name('agency.logo.update');



    // Domain Notes
    Route::get('/domains/{domain}/notes', [\App\Http\Controllers\DomainNoteController::class, 'index'])->name('domains.notes.index');
    Route::post('/domains/{domain}/notes', [\App\Http\Controllers\DomainNoteController::class, 'store'])->name('domains.notes.store');
    Route::patch('/notes/{note}', [\App\Http\Controllers\DomainNoteController::class, 'update'])->name('notes.update');
    Route::delete('/notes/{note}', [\App\Http\Controllers\DomainNoteController::class, 'destroy'])->name('notes.destroy');

    // Web Push Subscriptions
    Route::post('/subscriptions', [\App\Http\Controllers\PushSubscriptionController::class, 'store'])->name('subscriptions.store');

    // Web Push Subscriptions
    Route::post('/subscriptions', [\App\Http\Controllers\PushSubscriptionController::class, 'store'])->name('subscriptions.store');

});

require __DIR__.'/auth.php';
