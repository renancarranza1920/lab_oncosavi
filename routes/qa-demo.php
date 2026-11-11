<?php

use App\Http\Controllers\QaDemo\QaDemoController;
use App\Http\Middleware\QaDemoAccess;
use App\Http\Middleware\QaDemoContext;
use Illuminate\Support\Facades\Route;

Route::prefix('qa-quimica')->name('qa-demo.')->middleware(['web', QaDemoContext::class, QaDemoAccess::class])->group(function (): void {
    Route::get('/', [QaDemoController::class, 'index'])->name('index');
    Route::get('/mantenimiento', [QaDemoController::class, 'configuration'])->name('configuration.index');
    Route::get('/configuracion', fn () => redirect()->route('qa-demo.configuration.index', request()->query()))->name('configuration.legacy');
    Route::post('/equipos', [QaDemoController::class, 'storeEquipment'])->name('equipment.store');
    Route::put('/equipos/{equipment}', [QaDemoController::class, 'updateEquipment'])->whereNumber('equipment')->name('equipment.update');
    Route::post('/productos', [QaDemoController::class, 'storeProduct'])->name('products.store');
    Route::post('/lotes', [QaDemoController::class, 'storeLot'])->name('lots.store');
    Route::post('/pruebas', [QaDemoController::class, 'storeTest'])->name('tests.store');
    Route::put('/pruebas/{test}', [QaDemoController::class, 'updateTest'])->whereNumber('test')->name('tests.update');
    Route::post('/configuraciones', [QaDemoController::class, 'storeConfiguration'])->name('configuration.store');
    Route::get('/mediciones', [QaDemoController::class, 'runs'])->name('runs.index');
    Route::get('/mediciones/nueva', [QaDemoController::class, 'createRun'])->name('runs.create');
    Route::post('/mediciones', [QaDemoController::class, 'storeRun'])->name('runs.store');
    Route::get('/mediciones/{run}', [QaDemoController::class, 'showRun'])->whereNumber('run')->name('runs.show');
    Route::get('/mediciones/{run}/editar', [QaDemoController::class, 'editRun'])->whereNumber('run')->name('runs.edit');
    Route::put('/mediciones/{run}', [QaDemoController::class, 'updateRun'])->whereNumber('run')->name('runs.update');
    Route::delete('/mediciones/{run}', [QaDemoController::class, 'destroyRun'])->whereNumber('run')->name('runs.destroy');
    Route::post('/mediciones/{run}/revision', [QaDemoController::class, 'reviewRun'])->whereNumber('run')->name('runs.review');
    Route::get('/graficas', [QaDemoController::class, 'charts'])->name('charts');
    Route::get('/eventos', [QaDemoController::class, 'events'])->name('events.index');
    Route::post('/eventos', [QaDemoController::class, 'storeEvent'])->name('events.store');
    Route::get('/exportar.csv', [QaDemoController::class, 'csv'])->name('export.csv');
    Route::get('/informe.pdf', [QaDemoController::class, 'pdf'])->name('export.pdf');
});
