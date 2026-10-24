<?php



use App\Http\Controllers\ZplController;
use App\Http\Controllers\QzController;
use App\Models\DetalleOrden;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use App\Models\Orden;
use Barryvdh\DomPDF\Facade\Pdf;
Route::redirect('/', '/admin');

Route::prefix('expediente')->name('expediente.')->middleware(\App\Http\Middleware\ExpedientePrivado::class)->group(function () {
    Route::get('/ingresar', [\App\Http\Controllers\Expediente\AuthMedicoController::class, 'create'])->name('login');
    Route::post('/ingresar', [\App\Http\Controllers\Expediente\AuthMedicoController::class, 'store'])->middleware('throttle:20,1')->name('login.store');
    Route::middleware(\App\Http\Middleware\PortalMedico::class)->group(function () {
        Route::get('/', [\App\Http\Controllers\Expediente\ExpedienteController::class, 'index'])->name('index');
        Route::get('/pacientes/{paciente}', [\App\Http\Controllers\Expediente\ExpedienteController::class, 'show'])->whereNumber('paciente')->name('show');
        Route::get('/ordenes/{orden}/pdf', [\App\Http\Controllers\Expediente\ExpedienteController::class, 'pdf'])->whereNumber('orden')->name('pdf');
        Route::post('/salir', [\App\Http\Controllers\Expediente\AuthMedicoController::class, 'destroy'])->name('logout');
    });
});
/*
Route::get('/', function () {
    return view('welcome');
});
*/
Route::middleware(['auth', 'can:imprimir_etiquetas_kanban'])->group(function () {
    // Ruta para una sola etiqueta
    Route::get('/detalles/zpl/{id}', [ZplController::class, 'single'])->name('detalles.zpl');
    
    // Ruta para un grupo (columna)
    Route::get('/grupo/{status}/zpl/{ordenId}', [ZplController::class, 'group'])->name('grupo.zpl');
    
    // Ruta para TODAS (Header action)
    Route::get('/orden/zpl-all/{ordenId}', [ZplController::class, 'all'])->name('zpl.all');
});
Route::get('/orden/{orden}/boleta', function (App\Models\Orden $orden) {
    abort_unless(auth()->user()->can('view_orden') && auth()->user()->can('create_orden'), 403);
    $data = [
        'orden' => $orden->load(['cliente', 'detalleOrden']),
        'usuario' => auth()->user()->name,
    ];

    $pdf = Pdf::loadView('pdf.boleta-simple', $data)
        ->setPaper('letter', 'portrait');

    return $pdf->stream("boleta-{$orden->id}.pdf");
})->name('orden.boleta.pdf')->middleware('auth');

Route::get('/orden/{orden}/reporte-guardado', function (Request $request, App\Models\Orden $orden) {
    abort_unless(auth()->user()->can('view_orden') && auth()->user()->can('descargar_reporte_orden'), 403);
    abort_unless($orden->reporteGuardadoExists(), 404);

    $headers = [
        'Content-Type' => 'application/pdf',
        'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
        'Pragma' => 'no-cache',
        'Expires' => '0',
    ];

    if ($request->boolean('descargar')) {
        abort_unless($orden->estado === 'finalizado', 404);

        return response()->download($orden->reporteGuardadoFullPath(), $orden->reporteGuardadoFileName(), $headers);
    }

    return response()->file($orden->reporteGuardadoFullPath(), $headers)
        ->setContentDisposition('inline', $orden->reporteGuardadoFileName());
})->name('orden.reporte.guardado')->middleware('auth');


Route::middleware(['auth', 'can:imprimir_etiquetas_kanban'])->group(function () {
    Route::get('/qz/certificado', [QzController::class, 'certificado'])->name('qz.certificado');
    Route::post('/qz/firmar', [QzController::class, 'firmar'])->name('qz.firmar');
    Route::post('/sign-message', [QzController::class, 'firmar']);
    Route::get('/test-key', [QzController::class, 'estado']);
});



Route::post('/ordenes/ordenar', function (Request $request) {
    abort_unless(auth()->user()->can('mover_etiquetas_kanban'), 403);
    foreach ($request->ids as $index => $id) {
        DetalleOrden::where('id', $id)
            ->where('orden_id', $request->orden_id)
            ->update(['orden_en_recipiente' => $index]);
    }

    return response()->json(['status' => 'ok']);
})->middleware('auth');
