<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\AdaptiveLearningController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\CheckContentController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\AdminScenarioController;
use App\Http\Controllers\AdminResultController;
use App\Http\Controllers\RefresherController;
use App\Http\Controllers\DiagnosticController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () { return view('welcome'); })->name('home');

// Auth Routes
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.post');
Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
Route::post('/register', [AuthController::class, 'register'])->name('register.post');
Route::get('/verify-otp', [AuthController::class, 'showVerifyOtp'])->name('verify.otp');
Route::post('/verify-otp', [AuthController::class, 'verifyOtp'])->name('verify.otp.post');
Route::post('/resend-otp', [AuthController::class, 'resendOtp'])->name('verify.otp.resend');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Forgot Password Routes
Route::get('/forgot-password', [AuthController::class, 'showForgotPassword'])->name('password.request');
Route::post('/forgot-password', [AuthController::class, 'sendResetOtp'])->name('password.email');
Route::get('/verify-reset-otp', [AuthController::class, 'showVerifyResetOtp'])->name('password.verify');
Route::post('/verify-reset-otp', [AuthController::class, 'verifyResetOtp'])->name('password.verify.post');
Route::get('/reset-password', [AuthController::class, 'showResetPassword'])->name('password.reset');
Route::post('/reset-password', [AuthController::class, 'resetPassword'])->name('password.update');

// Student Routes
Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/profile', [DashboardController::class, 'profile'])->name('profile');

    // Practice
    Route::get('/practice', [AdaptiveLearningController::class, 'selectScenario'])->name('practice.select');
    Route::post('/practice/submit', [AdaptiveLearningController::class, 'submit'])->name('learning.submit');
    Route::get('/practice/result/{record}', [AdaptiveLearningController::class, 'result'])->name('learning.result');
    Route::get('/practice/retry/{record}', [AdaptiveLearningController::class, 'retry'])->name('learning.retry');
    Route::get('/practice/{scenario}', [AdaptiveLearningController::class, 'start'])->name('learning.start');
    Route::get('/learning/{scenario}/next', [AdaptiveLearningController::class, 'nextExample'])->name('learning.next');

    // Diagnostic (for new users)
    Route::get('/diagnostic', [DiagnosticController::class, 'start'])->name('diagnostic.start');
    Route::post('/diagnostic/submit', [DiagnosticController::class, 'submit'])->name('diagnostic.submit');

    // Refresher (for returning users)
    Route::get('/refresher', [RefresherController::class, 'start'])->name('refresher.start');
    Route::post('/refresher/submit', [RefresherController::class, 'submit'])->name('refresher.submit');

    // Check Content
    Route::get('/check', [CheckContentController::class, 'index'])->name('check.index');
    Route::post('/check/analyze', [CheckContentController::class, 'analyze'])->name('check.analyze');
    
    // 👇 THIS IS THE NEW FIX 👇
    // If a user accidentally types /check/analyze in the URL bar (GET request), 
    // redirect them safely back to the form.
    Route::get('/check/analyze', function () {
        return redirect()->route('check.index');
    });
});

// Admin Routes
Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [AdminController::class, 'index'])->name('dashboard');
    
    // System Monitor
    Route::get('/system-status', [AdminController::class, 'systemStatus'])->name('system.status');
    Route::post('/system-status/toggle', [AdminController::class, 'toggleService'])->name('system.toggle');
    
    Route::resource('scenarios', AdminScenarioController::class);
    Route::post('/scenarios/{scenario}/approve', [AdminScenarioController::class, 'approve'])->name('scenarios.approve');
    Route::post('/scenarios/{scenario}/reject', [AdminScenarioController::class, 'reject'])->name('scenarios.reject');
    Route::get('/results', [AdminResultController::class, 'index'])->name('results');
    Route::get('/export', [AdminController::class, 'export'])->name('export');
    Route::post('/upload-dataset', [AdminController::class, 'uploadDataset'])->name('datasets.upload');
    Route::post('/train-model', [AdminController::class, 'trainModel'])->name('train');
});