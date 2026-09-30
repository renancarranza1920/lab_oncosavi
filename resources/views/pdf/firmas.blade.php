@if ($sello_registro_b64 || $grupo['sello_b64'] || $grupo['firma_b64'])
                    <tfoot>
                        <tr>
                            <td class="firma-cell" colspan="{{ $esTablaConRef ? 3 : 2 }}">
                                <div class="firma-container">
                                    <table class="firma-table" style="width: 100%; border-collapse: collapse;">
                                        <tr>
                                            {{-- 1. Celda vacía a la izquierda (30%) --}}
                                            <td style="width: 30%;"></td>

                                            {{-- 2. Celda derecha (70%) con los dos sellos alineados --}}
                                            <td style="width: 70%; text-align: center; vertical-align: bottom;">

                                                {{-- Sello Registro (Izquierda) --}}
                                                <div style="display: inline-block; vertical-align: bottom; margin-right: 30px;">
                                                    @if ($sello_registro_b64)
                                                        {{-- AGREGADO: top: 40px para bajarlo al mismo nivel que el otro --}}
                                                        <img src="{{ $sello_registro_b64 }}"
                                                            style="width: 130px; position: relative; top: -26.5px; left: 40px">
                                                    @endif
                                                </div>

                                                {{-- Sello y Firma (Derecha) --}}
                                                <div class="firma-wrapper"
                                                    style="display: inline-block; vertical-align: bottom; margin-left: 10px;">
                                                    @if ($grupo['sello_b64'])
                                                        <img class="firma-img-sello" src="{{ $grupo['sello_b64'] }}">
                                                    @endif
                                                    @if ($grupo['firma_b64'])
                                                        <img class="firma-img-rubrica" src="{{ $grupo['firma_b64'] }}">
                                                    @endif
                                                </div>

                                            </td>
                                        </tr>
                                    </table>
                                </div>
                            </td>
                        </tr>
                    </tfoot>
@endif
