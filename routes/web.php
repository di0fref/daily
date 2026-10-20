<?php

use App\Http\Controllers\ArchiveController;
use App\Http\Controllers\BoardController;
use App\Http\Controllers\FocusController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\TaskAttachmentController;
use App\Http\Controllers\TaskController;
use Illuminate\Support\Facades\Route;

Route::get('/', [FocusController::class, 'index'])->name('focus');
Route::get('/board', [BoardController::class, 'index'])->name('board');
Route::get('/archive', [ArchiveController::class, 'index'])->name('archive');

Route::get('/projects', [ProjectController::class, 'index'])->name('projects.index');
Route::post('/projects', [ProjectController::class, 'store'])->name('projects.store');
Route::get('/projects/{project}', [ProjectController::class, 'show'])->name('projects.show');
Route::put('/projects/{project}', [ProjectController::class, 'update'])->name('projects.update');
Route::delete('/projects/{project}', [ProjectController::class, 'destroy'])->name('projects.destroy');

Route::post('/tasks', [TaskController::class, 'store'])->name('tasks.store');
Route::post('/tasks/parse', [TaskController::class, 'parse'])->name('tasks.parse');
Route::post('/tasks/archive-done', [TaskController::class, 'archiveDone'])->name('tasks.archive-done');
Route::put('/tasks/{task}', [TaskController::class, 'update'])->name('tasks.update');
Route::delete('/tasks/{task}', [TaskController::class, 'destroy'])->name('tasks.destroy');
Route::post('/tasks/{task}/toggle', [TaskController::class, 'toggle'])->name('tasks.toggle');
Route::post('/tasks/{task}/move', [TaskController::class, 'move'])->name('tasks.move');
Route::post('/tasks/{task}/archive', [TaskController::class, 'archive'])->name('tasks.archive');
Route::post('/tasks/{task}/unarchive', [TaskController::class, 'unarchive'])->name('tasks.unarchive');

Route::scopeBindings()->group(function () {
    Route::get('/tasks/{task}/attachments', [TaskAttachmentController::class, 'index'])->name('tasks.attachments.index');
    Route::post('/tasks/{task}/attachments', [TaskAttachmentController::class, 'store'])->name('tasks.attachments.store');
    Route::get('/tasks/{task}/attachments/{attachment}', [TaskAttachmentController::class, 'show'])->name('tasks.attachments.show');
    Route::delete('/tasks/{task}/attachments/{attachment}', [TaskAttachmentController::class, 'destroy'])->name('tasks.attachments.destroy');
});
