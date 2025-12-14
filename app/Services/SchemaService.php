<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;

class SchemaService
{
    /**
     * Obtener la conexión dinámica a la base de datos
     * basada en el schema relacionado de la residencia
     */
    public function getConnectionForSchema(?string $schema): string
    {
        if (empty($schema)) {
            throw new \Exception('El schema relacionado no está configurado para esta residencia.');
        }

        // Crear una conexión dinámica usando el schema
        $connectionName = 'schema_' . md5($schema);
        
        // Si la conexión no existe, crearla
        if (!Config::has("database.connections.{$connectionName}")) {
            Config::set("database.connections.{$connectionName}", [
                'driver' => 'mysql',
                'host' => env('INVOICES_DB_HOST', env('DB_HOST', '127.0.0.1')),
                'port' => env('INVOICES_DB_PORT', env('DB_PORT', '3306')),
                'database' => $schema,
                'username' => env('INVOICES_DB_USERNAME', env('DB_USERNAME', 'root')),
                'password' => env('INVOICES_DB_PASSWORD', env('DB_PASSWORD', '')),
                'charset' => env('DB_CHARSET', 'utf8mb4'),
                'collation' => env('DB_COLLATION', 'utf8mb4_unicode_ci'),
                'prefix' => '',
                'prefix_indexes' => true,
                'strict' => true,
                'engine' => null,
            ]);
        }

        return $connectionName;
    }
}

