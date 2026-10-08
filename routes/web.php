<?php

use App\Http\Controllers\PhotoProxyController;
use App\Http\Controllers\SafetyWarriorController;
use Illuminate\Support\Facades\Route;

Route::get('/', [SafetyWarriorController::class, 'index'])->name('dashboard');
Route::post('/sync', [SafetyWarriorController::class, 'sync'])->name('sync');
Route::get('/settings', [SafetyWarriorController::class, 'settings'])->name('settings');
Route::post('/settings', [SafetyWarriorController::class, 'updateSettings'])->name('settings.update');
Route::get('/export-csv', [SafetyWarriorController::class, 'exportCsv'])->name('export.csv');

// Photo Proxy Route (Google Drive Image Resolver)
Route::get('/drive-image/{id}', [PhotoProxyController::class, 'show'])->name('drive.image');

// Auto Sync & Webhook Endpoints
Route::get('/api/auto-sync', [SafetyWarriorController::class, 'sync'])->name('api.auto_sync');
Route::post('/api/webhook/google-form', [SafetyWarriorController::class, 'webhook'])->name('api.webhook');
