<?php

declare(strict_types=1);

namespace LameriPublica;

use PDO;
use RuntimeException;
use Throwable;

if (!defined('LAMERI_PUBLIC_API')) {
    http_response_code(404);
    exit;
}

final class RepositorioCatalogo
{
    public function __construct(private PDO $pdo)
    {
    }

    public function consultar(): array
    {
        // Tres lecturas coherentes; no se modifican filas ni se consulta otra tabla.
        $this->pdo->beginTransaction();
        try {
            $examens = $this->seleccionar(
                'SELECT e.id, e.nombre, e.precio, t.nombre AS categoria
                 FROM examens e
                 JOIN tipo_examens t ON t.id = e.tipo_examen_id
                 WHERE e.estado = :examen_activo AND t.estado = :categoria_activa
                 ORDER BY t.nombre, e.nombre, e.id',
                ['examen_activo' => 1, 'categoria_activa' => 1]
            );
            $perfiles = $this->seleccionar(
                'SELECT p.id, p.nombre, p.precio
                 FROM perfil p
                 WHERE p.estado = :perfil_activo
                   AND (
                     SELECT COUNT(*)
                     FROM detalle_perfil dp
                     JOIN examens e ON e.id = dp.examen_id
                     JOIN tipo_examens t ON t.id = e.tipo_examen_id
                     WHERE dp.perfil_id = p.id
                       AND e.estado = :examen_activo AND t.estado = :categoria_activa
                   ) >= :minimo_examenes
                 ORDER BY p.nombre, p.id',
                ['perfil_activo' => 1, 'examen_activo' => 1, 'categoria_activa' => 1, 'minimo_examenes' => 2]
            );
            $incluidos = $this->seleccionar(
                'SELECT dp.perfil_id, e.id AS examen_id, e.nombre
                 FROM detalle_perfil dp
                 JOIN perfil p ON p.id = dp.perfil_id
                 JOIN examens e ON e.id = dp.examen_id
                 JOIN tipo_examens t ON t.id = e.tipo_examen_id
                 WHERE p.estado = :perfil_activo
                   AND e.estado = :examen_activo AND t.estado = :categoria_activa
                 ORDER BY dp.perfil_id, e.nombre, e.id',
                ['perfil_activo' => 1, 'examen_activo' => 1, 'categoria_activa' => 1]
            );

            $examenPublico = array_map(fn (array $examen): array => [
                'id' => $this->id($examen['id']),
                'nombre' => $this->nombre($examen['nombre']),
                'precio' => $this->precio($examen['precio']),
                'categoria' => $this->nombre($examen['categoria']),
            ], $examens);

            $nombresPorPerfil = [];
            foreach ($incluidos as $examen) {
                $nombresPorPerfil[$this->id($examen['perfil_id'])][] = $this->nombre($examen['nombre']);
            }
            $perfilPublico = array_map(fn (array $perfil): array => [
                'id' => $this->id($perfil['id']),
                'nombre' => $this->nombre($perfil['nombre']),
                // El precio propio del perfil no es la suma de sus exámenes.
                'precio' => $this->precio($perfil['precio']),
                'incluidos' => $nombresPorPerfil[$this->id($perfil['id'])] ?? [],
            ], $perfiles);

            $this->pdo->commit();

            return [
                'moneda' => 'USD',
                'consultado_en' => gmdate('c'),
                'examens' => $examenPublico,
                'perfiles' => $perfilPublico,
            ];
        } catch (Throwable $error) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $error;
        }
    }

    private function seleccionar(string $sql, array $bindings): array
    {
        $statement = $this->pdo->prepare($sql);
        foreach ($bindings as $name => $value) {
            $statement->bindValue(':'.$name, $value, PDO::PARAM_INT);
        }
        $statement->execute();

        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    private function id(mixed $value): int
    {
        $id = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($id === false) {
            throw new RuntimeException('Identificador de catálogo no válido.');
        }

        return $id;
    }

    private function nombre(mixed $value): string
    {
        if (!is_string($value) || trim($value) === '') {
            throw new RuntimeException('Nombre de catálogo no válido.');
        }

        return trim($value);
    }

    private function precio(mixed $value): string
    {
        if (!is_scalar($value) || !preg_match('/^([0-9]+)(?:\.([0-9]{1,2}))?$/D', (string) $value, $parts)) {
            // Un precio ausente o incorrecto no se sustituye por cero ni por ejemplos.
            throw new RuntimeException('Precio de catálogo no válido.');
        }

        $entero = ltrim($parts[1], '0');

        return ($entero === '' ? '0' : $entero).'.'.str_pad($parts[2] ?? '', 2, '0');
    }
}
