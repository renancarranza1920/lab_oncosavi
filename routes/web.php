<?php



use App\Http\Controllers\ZplController;
use App\Models\DetalleOrden;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use App\Models\Orden;
use Barryvdh\DomPDF\Facade\Pdf;
/*
Route::get('/', function () {
    return view('welcome');
});
*/
Route::get('/detalles/zpl/{id}', [ZplController::class, 'single'])->name('detalles.zpl');

Route::get('/grupo/{status}/zpl/{ordenId}', [ZplController::class, 'group'])
    ->name('grupo.zpl');
    
Route::middleware('auth')->group(function () {
    // Ruta para una sola etiqueta
    Route::get('/detalles/zpl/{id}', [ZplController::class, 'single'])->name('detalles.zpl');
    
    // Ruta para un grupo (columna)
    Route::get('/grupo/{status}/zpl/{ordenId}', [ZplController::class, 'group'])->name('grupo.zpl');
    
    // Ruta para TODAS (Header action)
    Route::get('/orden/zpl-all/{ordenId}', [ZplController::class, 'all'])->name('zpl.all');
});
Route::get('/orden/{orden}/boleta', function (App\Models\Orden $orden) {
    $data = [
        'orden' => $orden->load(['cliente', 'detalleOrden']),
        'usuario' => auth()->user()->name,
    ];

    $pdf = Pdf::loadView('pdf.boleta-simple', $data)
        ->setPaper('letter', 'portrait');

    return $pdf->stream("boleta-{$orden->id}.pdf");
})->name('orden.boleta.pdf')->middleware('auth');

Route::get('/orden/{orden}/reporte-guardado', function (App\Models\Orden $orden) {
    abort_unless($orden->reporteGuardadoExists(), 404);

    return response()->file($orden->reporteGuardadoFullPath(), [
        'Content-Type' => 'application/pdf',
        'Content-Disposition' => 'inline; filename="' . $orden->reporteGuardadoFileName() . '"',
        'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
        'Pragma' => 'no-cache',
        'Expires' => '0',
    ]);
})->name('orden.reporte.guardado')->middleware('auth');


Route::get('/sign-message', function() {
    $message = request('request');

    $privateKey = openssl_pkey_get_private(file_get_contents(storage_path('app/private-key.pem')));

    openssl_sign($message, $signature, $privateKey, OPENSSL_ALGO_SHA256);

    return base64_encode($signature); // ✔ QZ solo acepta Base64
});


Route::get('/test-key', function () {
    $privateKey = storage_path('app/private-key.pem');

    $pkeyid = openssl_pkey_get_private(file_get_contents($privateKey));
    if ($pkeyid === false) {
        return "❌ Clave privada NO válida";
    }

    return "✔ Clave privada cargada correctamente";
});



Route::post('/ordenes/ordenar', function (Request $request) {
    foreach ($request->ids as $index => $id) {
        DetalleOrden::where('id', $id)
            ->where('orden_id', $request->orden_id)
            ->update(['orden_en_recipiente' => $index]);
    }

    return response()->json(['status' => 'ok']);
})->middleware('auth');
