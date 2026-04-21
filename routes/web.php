<?php

use App\Http\Controllers\ClientController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FinanceController;
use App\Http\Controllers\LotController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ReservationController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return redirect()->route('login');
});

// Authenticated routes
Route::middleware(['auth', 'verified'])->group(function () {

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Projects
    Route::get('/projects', [ProjectController::class, 'index'])->name('projects.index');
    Route::post('/projects', [ProjectController::class, 'store'])->name('projects.store');
    Route::get('/projects/{project}', [ProjectController::class, 'show'])->name('projects.show');
    Route::put('/projects/{project}', [ProjectController::class, 'update'])->name('projects.update');
    Route::delete('/projects/{project}', [ProjectController::class, 'destroy'])->name('projects.destroy');

    // Lots
    Route::get('/lots/{lot}', [LotController::class, 'show'])->name('lots.show');
    Route::put('/lots/{lot}', [LotController::class, 'update'])->name('lots.update');

    // Clients
    Route::get('/clients', [ClientController::class, 'index'])->name('clients.index');
    Route::get('/clients/create', [ClientController::class, 'create'])->name('clients.create');
    Route::post('/clients', [ClientController::class, 'store'])->name('clients.store');
    Route::get('/clients/{client}', [ClientController::class, 'show'])->name('clients.show');
    Route::put('/clients/{client}', [ClientController::class, 'update'])->name('clients.update');
    Route::post('/clients/{client}/documents', [ClientController::class, 'uploadDocument'])->name('clients.documents.upload');
    Route::get('/clients/{client}/documents/{document}/download', [ClientController::class, 'downloadDocument'])->name('clients.documents.download');
    Route::delete('/clients/{client}/documents/{document}', [ClientController::class, 'deleteDocument'])->name('clients.documents.delete');

    // Reservations
    Route::post('/reservations', [ReservationController::class, 'store'])->name('reservations.store');
    Route::post('/reservations/{reservation}/cancel', [ReservationController::class, 'cancel'])->name('reservations.cancel');
    Route::post('/reservations/{reservation}/confirm', [ReservationController::class, 'confirm'])->name('reservations.confirm');
    Route::post('/reservations/{reservation}/approve', [ReservationController::class, 'approve'])->name('reservations.approve');

    // Finances (admin/accountant only)
    Route::get('/finances', [FinanceController::class, 'index'])->name('finances.index');
    Route::get('/finances/plans/{paymentPlan}', [FinanceController::class, 'showPlan'])->name('finances.plan');
    Route::get('/finances/plans/{paymentPlan}/certificate', [FinanceController::class, 'generateCompletionCertificate'])->name('finances.plan.certificate');
    Route::post('/finances/plans/{paymentPlan}/cancel', [FinanceController::class, 'cancelPlan'])->name('finances.plan.cancel');
    Route::post('/finances/payments/{payment}/record', [FinanceController::class, 'recordPayment'])->name('finances.payment.record');
    Route::get('/finances/payments/{payment}/receipt', [FinanceController::class, 'generateReceipt'])->name('finances.payment.receipt');

    // Notifications (JSON API)
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/{notification}/read', [NotificationController::class, 'markAsRead'])->name('notifications.read');
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllAsRead'])->name('notifications.readAll');

    // Settings
    Route::get('/settings', function () {
        return Inertia::render('Settings/Index', [
            'tenant' => request()->user()->tenant,
        ]);
    })->name('settings.index');

    // Profile
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__ . '/auth.php';
