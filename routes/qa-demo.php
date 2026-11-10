<?php

use App\Http\Controllers\QaDemo\QaDemoController;
use App\Http\Middleware\QaDemoContext;
use Illuminate\Support\Facades\Route;

// Fuera de "web": la demo utiliza exclusivamente sus sesiones y datos privados.
Route::prefix('qa-quimica')->name('qa-demo.')->middleware(QaDemoContext::class)->group(function (): void {
    Route::get('/', [QaDemoController::class, 'index'])->name('index');
    Route::get('/configuracion', [QaDemoController::class, 'configuration'])->name('configuration.index');
    Route::post('/equipos', [QaDemoController::class, 'storeEquipment'])->name('equipment.store');
    Route::put('/equipos/{equipment}', [QaDemoController::class, 'updateEquipment'])->whereNumber('equipment')->name('equipment.update');
    Route::post('/configuraciones', [QaDemoController::class, 'storeConfiguration'])->name('configuration.store');
    Route::get('/mediciones', [QaDemoController::class, 'runs'])->name('runs.index');
    Route::get('/mediciones/nueva', [QaDemoController::class, 'createRun'])->name('runs.create');
    Route::post('/mediciones', [QaDemoController::class, 'storeRun'])->name('runs.store');
    Route::get('/mediciones/{run}', [QaDemoController::class, 'showRun'])->whereNumber('run')->name('runs.show');
    Route::get('/mediciones/{run}/editar', [QaDemoController::class, 'editRun'])->whereNumber('run')->name('runs.edit');
    Route::put('/mediciones/{run}', [QaDemoController::class, 'updateRun'])->whereNumber('run')->name('runs.update');
    Route::post('/mediciones/{run}/revision', [QaDemoController::class, 'reviewRun'])->whereNumber('run')->name('runs.review');
    Route::get('/graficas', [QaDemoController::class, 'charts'])->name('charts');
    Route::get('/eventos', [QaDemoController::class, 'events'])->name('events.index');
    Route::post('/eventos', [QaDemoController::class, 'storeEvent'])->name('events.store');
    Route::get('/exportar.csv', [QaDemoController::class, 'csv'])->name('export.csv');
    Route::get('/informe.pdf', [QaDemoController::class, 'pdf'])->name('export.pdf');
});
