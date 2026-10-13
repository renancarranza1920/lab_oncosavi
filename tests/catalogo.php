<?php

declare(strict_types=1);
define('LAMERI_PUBLIC_API', true);
require __DIR__.'/../public_html/api/lib/catalogo.php';
require __DIR__.'/../public_html/api/lib/cache.php';
require __DIR__.'/../public_html/api/lib/configuracion.php';

function comprobar(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$pdo = new PDO('sqlite::memory:', null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$pdo->exec('CREATE TABLE tipo_examens (id INTEGER PRIMARY KEY, nombre TEXT, estado BOOLEAN);
    CREATE TABLE examens (id INTEGER PRIMARY KEY, tipo_examen_id INTEGER, nombre TEXT, precio DECIMAL(10,2), estado BOOLEAN);
    CREATE TABLE perfil (id INTEGER PRIMARY KEY, nombre TEXT, precio DECIMAL(8,2), estado BOOLEAN);
    CREATE TABLE detalle_perfil (id INTEGER PRIMARY KEY, perfil_id INTEGER, examen_id INTEGER, UNIQUE(perfil_id, examen_id));
    INSERT INTO tipo_examens VALUES (1, "Hematología", 1), (2, "Área inactiva", 0);
    INSERT INTO examens VALUES (1,1,"Biometría hemática",10.25,1), (2,1,"Plaquetas",5.5,1),
      (3,1,"Examen inactivo",4,0), (4,2,"Examen de área inactiva",9,1), (5,1,"Biometría hemática",0,1);
    INSERT INTO perfil VALUES (1,"Perfil disponible",12,1), (2,"Perfil inactivo",14,0),
      (3,"Perfil incompleto",15,1), (4,"Perfil con área inactiva",16,1), (5,"Perfil sin costo",0,1);
    INSERT INTO detalle_perfil VALUES (1,1,1),(2,1,2),(3,1,3),(4,2,1),(5,2,2),
      (6,3,1),(7,3,3),(8,4,1),(9,4,4),(10,5,2),(11,5,5);');

$before = [];
foreach (['tipo_examens','examens','perfil','detalle_perfil'] as $table) {
    $before[$table] = $pdo->query('SELECT * FROM '.$table.' ORDER BY id')->fetchAll(PDO::FETCH_ASSOC);
}
$catalogo = (new LameriPublica\RepositorioCatalogo($pdo))->consultar();
comprobar(count($catalogo['examens']) === 3, 'Solo exámenes y categorías activos.');
comprobar(array_column($catalogo['examens'], 'id') === [1,5,2], 'Se preservan IDs aunque coincidan nombres.');
comprobar(array_column($catalogo['examens'], 'precio') === ['10.25','0.00','5.50'], 'Precios decimales exactos.');
comprobar(array_column($catalogo['perfiles'], 'id') === [1,5], 'Perfiles activos con dos exámenes activos en categorías activas.');
comprobar($catalogo['perfiles'][0]['precio'] === '12.00', 'Perfil precio propio, no suma.');
comprobar($catalogo['perfiles'][0]['incluidos'] === ['Biometría hemática','Plaquetas'], 'Exámenes incluidos filtrados y ordenados.');
comprobar($catalogo['moneda'] === 'USD' && strtotime($catalogo['consultado_en']) !== false, 'Moneda y fecha de lectura.');
foreach (['tipo_examens','examens','perfil','detalle_perfil'] as $table) {
    comprobar($before[$table] === $pdo->query('SELECT * FROM '.$table.' ORDER BY id')->fetchAll(PDO::FETCH_ASSOC), 'Lectura sin escrituras en '.$table);
}
$pdo->exec('INSERT INTO examens VALUES (6,1,"Precio incorrecto",-1,1)');
$rejected = false;
try {
    (new LameriPublica\RepositorioCatalogo($pdo))->consultar();
} catch (RuntimeException $error) {
    $rejected = true;
}
comprobar($rejected && !$pdo->inTransaction(), 'Precio inválido rechaza catálogo sin inventar valores y libera transacción.');

$directory = sys_get_temp_dir().'/lameri-api-cache-check-'.bin2hex(random_bytes(6));
$cache = new LameriPublica\CacheCatalogo($directory, ['fixture' => true]);
comprobar($cache->leer() === null, 'Caché ausente no bloquea.');
$cache->guardar($catalogo);
comprobar($cache->leer() === $catalogo, 'Caché fresca preserva DTO.');
$files = glob($directory.'/*.json');
comprobar(count($files) === 1 && (fileperms($files[0]) & 0777) === 0600, 'Caché privada con permisos0600.');
$expired = ['creado_en' => time()-61, 'catalogo' => $catalogo];
file_put_contents($files[0], json_encode($expired));
comprobar($cache->leer() === null, 'Caché vencida no se sirve.');
file_put_contents($files[0], '{broken');
comprobar($cache->leer() === null, 'Caché corrupta se descarta.');
$other = new LameriPublica\CacheCatalogo('/dev/null/cache', ['fixture' => true]);
$other->guardar($catalogo);
comprobar($other->leer() === null, 'Caché no escribible no bloquea lectura real.');
unlink($files[0]);
rmdir($directory);

$example = LameriPublica\cargarConfiguracion(__DIR__.'/../configuracion/lameri-publica.php.example');
comprobar(array_keys($example) === ['mysql','contacto'], 'Configuración MySQL y contacto público.');
comprobar($example['contacto']['correo'] === 'lab.merino20@gmail.com', 'Correo público corroborado.');
echo "OK: consultas legacy, filtros activos, precio propio, IDs, no escrituras, precios inválidos, caché TTL privada/opcional y configuración.\n";
