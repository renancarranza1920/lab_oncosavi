<script src="{{ asset('js/qz-tray.js') }}"></script>
<script src="{{ asset('js/sha256.min.js') }}"></script>
<script src="{{ asset('js/sign-message.js') }}"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jsrsasign/10.9.0/jsrsasign-all-min.js"></script>
<script>
    function onStart() {
        setTimeout(() => document.body.classList.add("grabbing"))
    }

    function onEnd() {
        document.body.classList.remove("grabbing")
    }

    function setData(dataTransfer, el) {
        dataTransfer.setData('id', el.id)
    }

    function onAdd(e) {
        const recordId = e.item.id
        const status = e.to.dataset.statusId
        const fromOrderedIds = [].slice.call(e.from.children).map(child => child.id)
        const toOrderedIds = [].slice.call(e.to.children).map(child => child.id)

        Livewire.dispatch('status-changed', {recordId, status, fromOrderedIds, toOrderedIds})
    }

    function onUpdate(e) {
        const recordId = e.item.id
        const status = e.from.dataset.statusId
        const orderedIds = [].slice.call(e.from.children).map(child => child.id)

        Livewire.dispatch('sort-changed', {recordId, status, orderedIds})
    }

    document.addEventListener('livewire:navigated', () => {
        const statuses = @js($statuses->pluck('id')->values()->toArray());

        statuses.forEach(status => {
            const container = document.querySelector(`[data-status-id='${status}']`);
            if (container) {
                Sortable.create(container, {
                    group: 'filament-kanban',
                    ghostClass: 'opacity-50',
                    animation: 150,
                    onStart,
                    onEnd,
                    onUpdate,
                    setData,
                    onAdd,
                });
            }
        });
    });
</script>

<script>
// Función para esperar a que la librería QZ cargue
function waitForQZ(callback) {
    if (window.qz) {
        callback();
    } else {
        setTimeout(() => waitForQZ(callback), 100);
    }
}

waitForQZ(() => {
    console.log("🔥 Librería QZ Tray cargada. Configurando certificado...");

 qz.security.setCertificatePromise(function(resolve, reject) {
        //Preferred method - from server
//        fetch("assets/signing/digital-certificate.txt", {cache: 'no-store', headers: {'Content-Type': 'text/plain'}})
//          .then(function(data) { data.ok ? resolve(data.text()) : reject(data.text()); });

        //Alternate method 1 - anonymous
//        resolve();  // remove this line in live environment

        //Alternate method 2 - direct
       // Alternate method 2 - direct
resolve("-----BEGIN CERTIFICATE-----\n" +
"MIIECzCCAvOgAwIBAgIGAZstp8QSMA0GCSqGSIb3DQEBCwUAMIGiMQswCQYDVQQG\n" +
"EwJVUzELMAkGA1UECAwCTlkxEjAQBgNVBAcMCUNhbmFzdG90YTEbMBkGA1UECgwS\n" +
"UVogSW5kdXN0cmllcywgTExDMRswGQYDVQQLDBJRWiBJbmR1c3RyaWVzLCBMTEMx\n" +
"HDAaBgkqhkiG9w0BCQEWDXN1cHBvcnRAcXouaW8xGjAYBgNVBAMMEVFaIFRyYXkg\n" +
"RGVtbyBDZXJ0MB4XDTI1MTIxNjE4NTIwOFoXDTQ1MTIxNjE4NTIwOFowgaIxCzAJ\n" +
"BgNVBAYTAlVTMQswCQYDVQQIDAJOWTESMBAGA1UEBwwJQ2FuYXN0b3RhMRswGQYD\n" +
"VQQKDBJRWiBJbmR1c3RyaWVzLCBMTEMxGzAZBgNVBAsMElFaIEluZHVzdHJpZXMs\n" +
"IExMQzEcMBoGCSqGSIb3DQEJARYNc3VwcG9ydEBxei5pbzEaMBgGA1UEAwwRUVog\n" +
"VHJheSBEZW1vIENlcnQwggEiMA0GCSqGSIb3DQEBAQUAA4IBDwAwggEKAoIBAQC7\n" +
"07oFBeJZmihUsesYmJhwBsI2gbAsqZ73y/7kYimyDVVLDjAm9vprXHUBHEiKqpCM\n" +
"S0yukIl7XQYoUX55wVxsqpZ6lf1hjwf9bm/i09Q2sUqz5Vo+5O8M1AL1dxTcpHph\n" +
"n8OB58x+4bBEgQMWKMU9jw6kWdzfKyD75UvCEJ1/Keh4fYKqxgPxrsQwJqCQzrZn\n" +
"Vl0ZWjEjnafwZFgN4aj3/2200h60vkk2bwbYxwr7DkvjqXSYeVjs8qrtq7MEcEno\n" +
"MXh3DXvZBcq6UFWe2QNrsjFPYnFsgZ5z8Sq4K8a5BOhDV62XBXxNSb8I3L15VAxc\n" +
"8nY76t8p2Mbnfb4atM4TAgMBAAGjRTBDMBIGA1UdEwEB/wQIMAYBAf8CAQEwDgYD\n" +
"VR0PAQH/BAQDAgEGMB0GA1UdDgQWBBRZzIxcPBX3WYJ6lapv7CUSeueW7jANBgkq\n" +
"hkiG9w0BAQsFAAOCAQEAaXYaKoHAfVN5rREVD9stj9tAPjUeKGuLhUM6uZO2c6os\n" +
"odu+zB+XMgx6yZElWfkNYhCfG1LDQVDHhdHU7kQOWOnPymJ6n9MIE81DkTJjbZC2\n" +
"WiwsGJ+AVo6o+i+v5oRzeKvSVlT8r16G128ks9nBbW7VhmNKLs7k+Cj5XjG5Z7Un\n" +
"QbTkcWO7W5jrgr0lraNCG2d1XCKSyI2pSmodiCJ5ET38On6ke8wBkp+7aOED8GGJ\n" +
"K68yjb7UEhsYra4HYAySIHRAaAATK5VnJlJPkelUc/cxmuuDV0MsnlBRCPMr/ocp\n" +
"xnXVMQu6XGMWAdV+CNAOAlmkIJxbYkXXbC33xrGXCQ==\n" +
"-----END CERTIFICATE-----");




    });

    qz.security.setSignatureAlgorithm("SHA512"); // Since 2.1




    // 3. IMPRESIÓN (esto está OK)
    document.addEventListener('livewire:init', () => {
        Livewire.on('imprimirZplFrontend', (eventData) => {
            let zplCode = eventData.zpl || eventData[0]?.zpl;

            if (!zplCode) {
                alert("Error: No se recibió código ZPL.");
                return;
            }

            let connectionPromise = qz.websocket.isActive()
                ? Promise.resolve()
                : qz.websocket.connect();

            connectionPromise.then(() => {
                const printerName = "ZDesigner ZD230-203dpi ZPL";

                return qz.printers.find(printerName).then(() => {
                    let config = qz.configs.create(printerName, { raw: true });
                    return qz.print(config, [{
                        type: 'raw',
                        format: 'plain',
                        data: zplCode
                    }]);
                });
            })
            .catch(err => alert("Error: " + err));
        });
    });
});

</script>
