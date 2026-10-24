(function (window, document) {
    'use strict';

    if (window.lameriQz) return;

    let sdkPromise;
    let readyPromise;
    let connectionPromise;
    let settings;

    function localUrl(value) {
        const url = new URL(value, window.location.href);
        if (url.origin !== window.location.origin) {
            throw new Error('La configuración de impresión debe pertenecer a este sitio.');
        }
        return url.href;
    }

    function loadSdk(url) {
        if (!sdkPromise) {
            sdkPromise = new Promise(function (resolve, reject) {
                if (window.qz?.security && window.qz?.websocket) {
                    resolve(window.qz);
                    return;
                }

                const script = document.createElement('script');
                script.id = 'lameri-qz-sdk';
                script.src = url;
                script.onload = function () {
                    if (window.qz?.security && window.qz?.websocket) {
                        resolve(window.qz);
                    } else {
                        reject(new Error('No se pudo cargar QZ Tray. Recargue la página.'));
                    }
                };
                script.onerror = function () {
                    script.remove();
                    reject(new Error('No se pudo cargar la conexión con QZ Tray.'));
                };
                document.head.appendChild(script);
            }).catch(function (error) {
                sdkPromise = null;
                throw error;
            });
        }

        return sdkPromise;
    }

    async function responseText(response) {
        const text = await response.text();
        if (!response.ok) {
            let message;
            try { message = JSON.parse(text).message; } catch (_) { /* La respuesta puede ser texto. */ }
            if (response.status === 401 || response.status === 419 || response.redirected) {
                throw new Error('La sesión venció. Recargue la página e ingrese nuevamente.');
            }
            throw new Error(message || 'No se pudo obtener la firma de QZ Tray. Revise la configuración de impresión.');
        }
        if (response.redirected || response.headers.get('Content-Type')?.includes('text/html')) {
            throw new Error('La sesión venció. Recargue la página e ingrese nuevamente.');
        }
        return text.trim();
    }

    function init(config) {
        let normalized;
        try {
            normalized = {
                sdkUrl: localUrl(config.sdkUrl),
                certificateUrl: localUrl(config.certificateUrl),
                signatureUrl: localUrl(config.signatureUrl),
            };
        } catch (error) {
            return Promise.reject(error);
        }

        if (settings && JSON.stringify(settings) !== JSON.stringify(normalized)) {
            return Promise.reject(new Error('Cambió la configuración de impresión. Recargue la página.'));
        }
        settings = normalized;

        if (!readyPromise) {
            readyPromise = Promise.all([
                loadSdk(settings.sdkUrl),
                fetch(settings.certificateUrl, {
                    cache: 'no-store',
                    credentials: 'same-origin',
                    headers: { Accept: 'application/json, text/plain' },
                }).then(async function (response) {
                    return {
                        certificate: await responseText(response),
                        fingerprint: response.headers.get('X-QZ-Certificate-SHA256'),
                    };
                }),
            ]).then(function ([sdk, identity]) {
                const { certificate, fingerprint } = identity;
                if (!certificate.includes('-----BEGIN CERTIFICATE-----') || !certificate.includes('-----END CERTIFICATE-----')) {
                    throw new Error('El certificado de impresión no está configurado correctamente.');
                }
                if (!/^[a-f0-9]{64}$/i.test(fingerprint || '')) {
                    throw new Error('No se pudo verificar la configuración de impresión. Recargue la página.');
                }

                // El SDK y sus callbacks se conservan al navegar. No se inicia
                // ninguna conexión si no hay certificado o si falla la firma.
                sdk.security.setCertificatePromise(function (resolve) {
                    resolve(certificate);
                }, { rejectOnFailure: true });
                sdk.security.setSignatureAlgorithm('SHA512');
                sdk.security.setSignaturePromise(function (toSign) {
                    return function (resolve, reject) {
                        const token = document.querySelector('meta[name="csrf-token"]')?.content;
                        if (!token) {
                            reject(new Error('La sesión no permite firmar. Recargue la página.'));
                            return;
                        }
                        fetch(settings.signatureUrl, {
                            method: 'POST',
                            cache: 'no-store',
                            credentials: 'same-origin',
                            headers: {
                                Accept: 'application/json, text/plain',
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': token,
                                'X-QZ-Certificate-SHA256': fingerprint,
                            },
                            body: JSON.stringify({ request: toSign }),
                        }).then(responseText).then(function (signature) {
                            if (!/^[A-Za-z0-9+/]+={0,2}$/.test(signature)) {
                                throw new Error('El servidor devolvió una firma de impresión inválida.');
                            }
                            resolve(signature);
                        }).catch(reject);
                    };
                });
                return sdk;
            }).catch(function (error) {
                readyPromise = null;
                throw error;
            });
        }

        return readyPromise;
    }

    async function print(zpl) {
        if (typeof zpl !== 'string' || !zpl.trim()) {
            throw new Error('No se recibió una etiqueta para imprimir.');
        }
        if (!settings) {
            throw new Error('No se configuró la impresión. Recargue la página.');
        }

        const sdk = await init(settings);
        if (!sdk.websocket.isActive()) {
            if (!connectionPromise) {
                connectionPromise = sdk.websocket.connect().finally(function () {
                    connectionPromise = null;
                });
            }
            await connectionPromise;
        }

        const printerName = 'ZDesigner ZD230-203dpi ZPL';
        await sdk.printers.find(printerName);
        const config = sdk.configs.create(printerName, { raw: true });
        return sdk.print(config, [{ type: 'raw', format: 'plain', data: zpl }]);
    }

    window.lameriQz = Object.freeze({ init, print });
})(window, document);
