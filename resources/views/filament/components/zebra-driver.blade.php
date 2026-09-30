{{-- 
    DRIVER JAVASCRIPT PARA ZEBRA BROWSER PRINT
    Este script se encarga de hacer el puente entre la Nube (Hostinger) y el USB local.
--}}

{{-- 1. Cargar la librería localmente (requiere que el usuario tenga el software instalado) --}}
<script type="text/javascript" src="https://localhost:9100/BrowserPrint-3.0.21509.10216.js"></script>

<script>
    var selected_device;

    // 2. Función de configuración inicial (Busca la impresora al cargar la página)
    function setupZebraPrinter() {
        if (typeof BrowserPrint === 'undefined') {
            console.warn("El objeto BrowserPrint no está disponible. ¿Está instalado el software?");
            return;
        }

        // Intentar conectar con el servicio local
        BrowserPrint.getDefaultDevice("printer", function(device) {
            selected_device = device;
            console.log("✅ Impresora Zebra conectada: " + device.name);
        }, function(error) {
            console.warn("⚠️ No se detectó impresora Zebra por defecto. Error: " + error);
        });
    }

    // 3. Función Maestra: Recibe una URL de Laravel, baja el ZPL y lo imprime
    window.printZplFromUrl = async function(url) {
        // Validación de seguridad: ¿Tenemos impresora?
        if (!selected_device) {
            // Intentamos reconectar una última vez
            BrowserPrint.getDefaultDevice("printer", 
                function(d){ 
                    selected_device = d; 
                    printZplFromUrl(url); // Reintentar impresión
                }, 
                function(e){
                    alert("❌ ERROR DE IMPRESORA\n\nNo se detecta la Zebra ZD230.\n1. Asegúrate de que el programa 'Zebra Browser Print' esté abierto.\n2. Verifica que el cable USB esté conectado.\n3. Si es la primera vez, abre https://localhost:9100/ y acepta el certificado de seguridad.");
                }
            );
            return;
        }

        // Notificación visual de "Procesando"
        new FilamentNotification()
            .title('Obteniendo etiqueta...')
            .body('Conectando con el servidor...')
            .info()
            .send();

        try {
            // A. Pedir el código ZPL a Laravel (Tu API en Hostinger)
            const response = await fetch(url);
            const data = await response.json();

            if (!data.success) {
                new FilamentNotification()
                    .title('Error')
                    .body(data.message || 'No se pudo generar el ZPL')
                    .danger()
                    .send();
                return;
            }

            // B. Enviar el código ZPL directo al USB local
            selected_device.send(data.zpl, undefined, function(success) {
                new FilamentNotification()
                    .title('🖨️ Enviado a impresión')
                    .success()
                    .send();
            }, function(error) {
                alert("Error de comunicación con la impresora: " + error);
            });

        } catch (err) {
            console.error(err);
            alert("Error de red. No se pudo conectar con el servidor para obtener la etiqueta.");
        }
    }

    // 4. Inicializar al cargar la página
    window.onload = setupZebraPrinter;

    // 5. ESCUCHADOR DE EVENTOS LIVEWIRE (Vital para los botones de las tarjetas)
    document.addEventListener('livewire:initialized', () => {
        Livewire.on('print-zpl', (event) => {
            // El evento viene como un objeto, accedemos a la propiedad url
            // Nota: Dependiendo de la versión de Livewire, puede ser event.url o event[0].url
            const urlToPrint = event.url || event[0].url; 
            
            if (urlToPrint) {
                console.log("Evento Livewire recibido. Imprimiendo: " + urlToPrint);
                window.printZplFromUrl(urlToPrint);
            }
        });
    });
</script>