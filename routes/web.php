<?php

use App\Http\Controllers\Auth\GitHubController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\Settings\ConnectorController;
use App\Http\Controllers\TaskCompletionController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\TelegramConnectionController;
use App\Http\Controllers\TelegramWebhookController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'welcome')->name('home');

Route::post('/telegram/webhook', TelegramWebhookController::class)->name('telegram.webhook');

Route::middleware('guest')->group(function () {
    Route::get('/auth/github/redirect', [GitHubController::class, 'redirect'])->name('auth.github.redirect');
    Route::get('/auth/github/callback', [GitHubController::class, 'callback'])->name('auth.github.callback');
});

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    Route::get('/projects', [ProjectController::class, 'index'])->name('projects.index');
    Route::post('/projects', [ProjectController::class, 'store'])->name('projects.store');
    Route::get('/projects/{project}', [ProjectController::class, 'show'])->name('projects.show')->can('view', 'project');
    Route::get('/projects/{project}/configuracoes', [ProjectController::class, 'edit'])->name('projects.edit')->can('update', 'project');
    Route::put('/projects/{project}', [ProjectController::class, 'update'])->name('projects.update')->can('update', 'project');
    Route::delete('/projects/{project}', [ProjectController::class, 'destroy'])->name('projects.destroy')->can('delete', 'project');

    Route::post('/tasks', [TaskController::class, 'store'])->name('tasks.store');
    Route::put('/tasks/{task}', [TaskController::class, 'update'])->name('tasks.update')->can('update', 'task');
    Route::delete('/tasks/{task}', [TaskController::class, 'destroy'])->name('tasks.destroy')->can('delete', 'task');

    Route::post('/tasks/{task}/completion', [TaskCompletionController::class, 'store'])->name('tasks.completion.store')->can('update', 'task');
    Route::delete('/tasks/{task}/completion', [TaskCompletionController::class, 'destroy'])->name('tasks.completion.destroy')->can('update', 'task');

    Route::get('/perfil', ProfileController::class)->name('profile');

    Route::redirect('/configuracoes', '/configuracoes/conectores')->name('settings');
    Route::get('/configuracoes/conectores', ConnectorController::class)->name('settings.connectors');

    Route::post('/telegram/connection', [TelegramConnectionController::class, 'store'])->name('telegram.connection.store');
    Route::delete('/telegram/connection', [TelegramConnectionController::class, 'destroy'])->name('telegram.connection.destroy');
});
