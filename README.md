# ONCOSAVI · rama test

Copia de `main` para pruebas en un alojamiento independiente. Incluye tres usuarios
de prueba, soporte identificado con auditoría, pacientes y órdenes ficticias, PDFs,
firma personal y sellos de ejemplo. Los datos se crean explícitamente en una base
nueva; no se conecta a la instalación real de Oracle.

La instalación en Hostinger no requiere Docker. Sigue
[la guía de instalación y accesos](docs/ENTORNO_TEST_HOSTINGER.md).
Las contraseñas se generan en el alojamiento y no están en el repositorio.

Después de configurar `.env`, instalar dependencias y aplicar las migraciones:

```bash
php artisan oncosavi:preparar-test
```

El comando solo funciona con `APP_ENV=staging` y `TEST_DEMO_ENABLED=true`, y se
detiene si encuentra datos existentes. Usa siempre una base y un storage separados
para esta rama. Los resultados de ejemplo no tienen validez clínica.
