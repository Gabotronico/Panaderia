<?php

namespace App\Support\Respaldos;

use Illuminate\Database\Connection;

/**
 * Reemplaza el contenido de las tablas de una base con el de otra, aunque
 * sean de motores distintos (el SQLite de un respaldo y el MySQL de la web).
 * Ambas bases tienen que estar migradas al mismo esquema.
 */
final class CopiadorDeTablas
{
    private const FILAS_POR_LOTE = 500;

    /**
     * Las tablas de infraestructura no viajan: el registro de migraciones es
     * de cada base, y sesiones, caché y colas son estado del momento.
     */
    public const OMITIDAS = [
        'migrations', 'sessions', 'cache', 'cache_locks',
        'jobs', 'job_batches', 'failed_jobs', 'password_reset_tokens',
    ];

    /**
     * Borra los datos de destino y copia los de origen, en una transacción:
     * si una fila falla, destino queda como estaba.
     *
     * @return array<string, int> filas copiadas por tabla
     */
    public static function copiar(Connection $origen, Connection $destino): array
    {
        $tablas = array_values(array_diff(
            array_intersect(self::tablas($destino), self::tablas($origen)),
            self::OMITIDAS
        ));

        $esquemaOrigen = $origen->getSchemaBuilder();
        $esquemaDestino = $destino->getSchemaBuilder();
        $copiadas = [];

        // Fuera de la transacción: en SQLite el PRAGMA foreign_keys no tiene
        // efecto dentro de una. Sin esto habría que borrar e insertar en el
        // orden exacto de las claves foráneas.
        $esquemaDestino->disableForeignKeyConstraints();

        try {
            $destino->transaction(function () use ($tablas, $origen, $destino, $esquemaOrigen, $esquemaDestino, &$copiadas) {
                foreach ($tablas as $tabla) {
                    $destino->table($tabla)->delete();
                }

                foreach ($tablas as $tabla) {
                    $columnas = array_values(array_intersect(
                        $esquemaOrigen->getColumnListing($tabla),
                        $esquemaDestino->getColumnListing($tabla)
                    ));

                    $copiadas[$tabla] = 0;
                    $lote = [];

                    foreach ($origen->table($tabla)->select($columnas)->cursor() as $fila) {
                        $lote[] = (array) $fila;

                        if (\count($lote) === self::FILAS_POR_LOTE) {
                            $destino->table($tabla)->insert($lote);
                            $copiadas[$tabla] += \count($lote);
                            $lote = [];
                        }
                    }

                    if ($lote) {
                        $destino->table($tabla)->insert($lote);
                        $copiadas[$tabla] += \count($lote);
                    }
                }
            });
        } finally {
            $esquemaDestino->enableForeignKeyConstraints();
        }

        return $copiadas;
    }

    /**
     * Solo las del esquema de la conexión: en MySQL, getTables() sin esquema
     * lista las tablas de todas las bases del servidor, es decir, las de
     * todas las panaderías.
     *
     * @return list<string>
     */
    private static function tablas(Connection $conexion): array
    {
        $esquema = $conexion->getSchemaBuilder();

        return array_values(array_filter(
            array_column($esquema->getTables($esquema->getCurrentSchemaName()), 'name'),
            fn (string $tabla) => ! str_starts_with($tabla, 'sqlite_')
        ));
    }
}
