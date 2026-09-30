<?php

namespace App\Http\Controllers;


use App\Models\DetalleOrden;
use App\Services\ZebraLabelService;
use Filament\Notifications\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ZplController extends Controller
{
  // private $printerName = '\\\\localhost\\ZebraZD230'; // Nombre del recurso compartido

   

public function single($id)
{
    $detalle = DetalleOrden::with('orden.cliente')->findOrFail($id);

    $service = new ZebraLabelService();
    $zpl = $service->generarZpl($detalle);

    return response()->json([
        'zpl' => $zpl
    ]);
}


public function group($status, $ordenId)
{
    $detalles = DetalleOrden::with('orden.cliente')
        ->where('status', $status)
        ->where('orden_id', $ordenId)
        ->get();

    $service = new ZebraLabelService();
    $zpl = $service->generarZplMultiple($detalles);

    return response()->json([
        'zpl' => $zpl
    ]);
}


}
