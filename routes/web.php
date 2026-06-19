<?php

use App\Http\Controllers\ClientController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FinanceController;
use App\Http\Controllers\LotController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ReservationController;
use App\Http\Controllers\UserController;
use App\Models\User;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return redirect()->route('login');
});

// Authenticated routes
Route::middleware(['auth', 'verified'])->group(function () {

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Projects (all roles can view; only admin can mutate)
    Route::get('/projects', [ProjectController::class, 'index'])->name('projects.index');
    Route::get('/projects/{project}', [ProjectController::class, 'show'])->name('projects.show');
    Route::middleware('role:admin')->group(function () {
        Route::post('/projects', [ProjectController::class, 'store'])->name('projects.store');
        Route::put('/projects/{project}', [ProjectController::class, 'update'])->name('projects.update');
        Route::delete('/projects/{project}', [ProjectController::class, 'destroy'])->name('projects.destroy');
    });

    // Lots (all roles can view; admin can edit; all can view history)
    Route::get('/lots/{lot}', [LotController::class, 'show'])->name('lots.show');
    Route::get('/lots/{lot}/history', [LotController::class, 'history'])->name('lots.history');
    Route::put('/lots/{lot}', [LotController::class, 'update'])->name('lots.update')->middleware('role:admin');

    // Clients (all roles can view; admin + secretary can write)
    Route::get('/clients', [ClientController::class, 'index'])->name('clients.index');
    Route::get('/clients/create', [ClientController::class, 'create'])->name('clients.create')->middleware('role:admin,secretary');
    Route::post('/clients', [ClientController::class, 'store'])->name('clients.store')->middleware('role:admin,secretary');
    Route::get('/clients/{client}', [ClientController::class, 'show'])->name('clients.show');
    Route::put('/clients/{client}', [ClientController::class, 'update'])->name('clients.update')->middleware('role:admin,secretary');
    Route::post('/clients/{client}/documents', [ClientController::class, 'uploadDocument'])->name('clients.documents.upload')->middleware('role:admin,secretary');
    Route::get('/clients/{client}/documents/{document}/download', [ClientController::class, 'downloadDocument'])->name('clients.documents.download');
    Route::delete('/clients/{client}/documents/{document}', [ClientController::class, 'deleteDocument'])->name('clients.documents.delete')->middleware('role:admin,secretary');

    // Reservations
    Route::post('/reservations', [ReservationController::class, 'store'])->name('reservations.store')->middleware('role:admin,secretary');
    Route::post('/reservations/{reservation}/cancel', [ReservationController::class, 'cancel'])->name('reservations.cancel')->middleware('role:admin,accountant');
    Route::post('/reservations/{reservation}/confirm', [ReservationController::class, 'confirm'])->name('reservations.confirm')->middleware('role:admin,accountant');
    Route::post('/reservations/{reservation}/approve', [ReservationController::class, 'approve'])->name('reservations.approve')->middleware('role:admin,accountant');

    // Finances (admin + accountant only)
    Route::middleware('role:admin,accountant')->group(function () {
        Route::get('/finances', [FinanceController::class, 'index'])->name('finances.index');
        Route::get('/finances/plans/{paymentPlan}', [FinanceController::class, 'showPlan'])->name('finances.plan');
        Route::get('/finances/plans/{paymentPlan}/certificate', [FinanceController::class, 'generateCompletionCertificate'])->name('finances.plan.certificate');
        Route::post('/finances/plans/{paymentPlan}/cancel', [FinanceController::class, 'cancelPlan'])->name('finances.plan.cancel');
        Route::post('/finances/plans/{paymentPlan}/initial-payment', [FinanceController::class, 'registerInitialPayment'])->name('finances.plan.initial-payment');
        Route::post('/finances/payments/{payment}/record', [FinanceController::class, 'recordPayment'])->name('finances.payment.record');
        Route::get('/finances/payments/{payment}/receipt', [FinanceController::class, 'generateReceipt'])->name('finances.payment.receipt');
    });

    // Notifications (JSON API — all roles)
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/{notification}/read', [NotificationController::class, 'markAsRead'])->name('notifications.read');
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllAsRead'])->name('notifications.readAll');

    // Settings + User Management (admin only)
    Route::middleware('role:admin')->group(function () {
        Route::get('/settings', function () {
            $tenantId = request()->user()->tenant_id;

            $users = User::where('tenant_id', $tenantId)
                ->orderBy('name')
                ->get()
                ->map(fn($u) => [
                    'id'         => $u->id,
                    'name'       => $u->name,
                    'email'      => $u->email,
                    'role'       => $u->role,
                    'is_active'  => $u->is_active,
                    'created_at' => $u->created_at->format('Y-m-d'),
                ]);

            return Inertia::render('Settings/Index', [
                'tenant' => request()->user()->tenant,
                'users'  => $users,
            ]);
        })->name('settings.index');

        Route::post('/users', [UserController::class, 'store'])->name('users.store');
        Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
        Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');
    });

    // Profile
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__ . '/auth.php';
