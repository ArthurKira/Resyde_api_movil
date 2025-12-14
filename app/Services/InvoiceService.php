<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;

class InvoiceService
{
    /**
     * Obtener la conexión dinámica a la base de datos de recibos
     * basada en el schema relacionado de la residencia
     */
    public function getConnectionForSchema(?string $schema): string
    {
        if (empty($schema)) {
            throw new \Exception('El schema relacionado no está configurado para esta residencia.');
        }

        // Crear una conexión dinámica usando el schema
        $connectionName = 'invoices_' . md5($schema);
        
        // Si la conexión no existe, crearla
        if (!Config::has("database.connections.{$connectionName}")) {
            Config::set("database.connections.{$connectionName}", [
                'driver' => 'mysql',
                'host' => env('INVOICES_DB_HOST', env('DB_HOST', '127.0.0.1')),
                'port' => env('INVOICES_DB_PORT', env('DB_PORT', '3306')),
                'database' => $schema, // El schema_relacionado se usa como nombre de base de datos
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

    /**
     * Obtener los recibos con información del residente y departamento
     */
    public function getInvoices(string $connectionName, array $filters = []): array
    {
        $query = DB::connection($connectionName)
            ->table('invoices')
            ->select([
                'invoices.*',
                'tenants.fullname AS residente_nombre',
                'tenants.phone_number AS residente_telefono',
                'tenants.email AS residente_email',
                'houses.house_number AS departamento_numero',
                'houses.features AS departamento_nombre',
            ])
            ->leftJoin('tenants', 'invoices.tenant', '=', 'tenants.id')
            ->leftJoin('houses', 'tenants.house', '=', 'houses.id');

        // Aplicar filtros si existen
        if (isset($filters['tenant'])) {
            $query->where('invoices.tenant', $filters['tenant']);
        }

        if (isset($filters['house'])) {
            // Usar tenants.house en lugar de invoices.house (que contiene error voluntario)
            $query->where('tenants.house', $filters['house']);
        }

        if (isset($filters['year'])) {
            $query->where('invoices.year', $filters['year']);
        }

        if (isset($filters['month'])) {
            $query->where('invoices.month', $filters['month']);
        }

        if (isset($filters['status'])) {
            $query->where('invoices.status', $filters['status']);
        }

        // Ordenar por ID descendente
        $query->orderBy('invoices.id', 'DESC');

        // Paginación
        $perPage = $filters['per_page'] ?? 15;
        $page = $filters['page'] ?? 1;

        $total = $query->count();
        $invoices = $query->skip(($page - 1) * $perPage)
            ->take($perPage)
            ->get()
            ->toArray();

        // Agregar detalles a cada invoice
        foreach ($invoices as &$invoice) {
            $invoice = (array) $invoice;
            $invoice['detalles'] = $this->getInvoiceDetalles($connectionName, $invoice['id']);
        }

        return [
            'data' => $invoices,
            'meta' => [
                'current_page' => (int) $page,
                'per_page' => (int) $perPage,
                'total' => $total,
                'last_page' => (int) ceil($total / $perPage),
            ],
        ];
    }

    /**
     * Contar el total de recibos sin cargar los datos (solo COUNT)
     */
    public function countInvoices(string $connectionName, array $filters = []): int
    {
        $query = DB::connection($connectionName)
            ->table('invoices');

        // Aplicar filtros si existen
        if (isset($filters['tenant'])) {
            $query->where('invoices.tenant', $filters['tenant']);
        }

        if (isset($filters['house'])) {
            // Necesitamos JOIN con tenants para filtrar por tenants.house
            $query->join('tenants', 'invoices.tenant', '=', 'tenants.id')
                  ->where('tenants.house', $filters['house']);
        }

        if (isset($filters['year'])) {
            $query->where('invoices.year', $filters['year']);
        }

        if (isset($filters['month'])) {
            $query->where('invoices.month', $filters['month']);
        }

        if (isset($filters['status'])) {
            $query->where('invoices.status', $filters['status']);
        }

        return $query->count();
    }

    /**
     * Obtener solo los IDs de los recibos (para ordenar eficientemente)
     */
    public function getInvoiceIds(string $connectionName, array $filters = []): array
    {
        $query = DB::connection($connectionName)
            ->table('invoices')
            ->select('invoices.id');

        // Aplicar filtros si existen
        if (isset($filters['tenant'])) {
            $query->where('invoices.tenant', $filters['tenant']);
        }

        if (isset($filters['house'])) {
            // Necesitamos JOIN con tenants para filtrar por tenants.house
            $query->join('tenants', 'invoices.tenant', '=', 'tenants.id')
                  ->where('tenants.house', $filters['house']);
        }

        if (isset($filters['year'])) {
            $query->where('invoices.year', $filters['year']);
        }

        if (isset($filters['month'])) {
            $query->where('invoices.month', $filters['month']);
        }

        if (isset($filters['status'])) {
            $query->where('invoices.status', $filters['status']);
        }

        // Ordenar por ID descendente
        $query->orderBy('invoices.id', 'DESC');

        return $query->get()->toArray();
    }

    /**
     * Obtener un recibo específico por ID con todos sus datos
     */
    public function getInvoiceById(string $connectionName, int $invoiceId): ?object
    {
        $invoice = DB::connection($connectionName)
            ->table('invoices')
            ->select([
                'invoices.*',
                'tenants.fullname AS residente_nombre',
                'tenants.phone_number AS residente_telefono',
                'tenants.email AS residente_email',
                'houses.house_number AS departamento_numero',
                'houses.features AS departamento_nombre',
            ])
            ->leftJoin('tenants', 'invoices.tenant', '=', 'tenants.id')
            ->leftJoin('houses', 'tenants.house', '=', 'houses.id')
            ->where('invoices.id', $invoiceId)
            ->first();

        if (!$invoice) {
            return null;
        }

        // Agregar detalles al invoice
        $invoice->detalles = $this->getInvoiceDetalles($connectionName, $invoiceId);

        return $invoice;
    }

    /**
     * Obtener los detalles de un invoice con sus conceptos
     */
    public function getInvoiceDetalles(string $connectionName, int $invoiceId): array
    {
        try {
            // Verificar si la tabla invoice_detalles existe
            $tables = DB::connection($connectionName)
                ->select("SHOW TABLES LIKE 'invoice_detalles'");
            
            if (empty($tables)) {
                // La tabla no existe, retornar array vacío
                return [];
            }

            $detalles = DB::connection($connectionName)
                ->table('invoice_detalles')
                ->select([
                    'invoice_detalles.*',
                    'conceptos.codigo AS concepto_codigo',
                    'conceptos.nombre AS concepto_nombre',
                    'conceptos.descripcion AS concepto_descripcion',
                    'conceptos.tipo AS concepto_tipo',
                ])
                ->leftJoin('conceptos', 'invoice_detalles.concepto_id', '=', 'conceptos.id')
                ->where('invoice_detalles.invoice_id', $invoiceId)
                ->orderBy('conceptos.orden', 'ASC')
                ->get()
                ->toArray();

            return $detalles;
        } catch (\Exception $e) {
            // Si hay error (tabla no existe, etc.), retornar array vacío
            \Log::debug("Error al obtener detalles del invoice {$invoiceId}: " . $e->getMessage());
            return [];
        }
    }
}

