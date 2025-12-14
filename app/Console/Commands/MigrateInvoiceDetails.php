<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;

class MigrateInvoiceDetails extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'invoices:migrate-details {schema=uclubperu_rentalmanagement_pruebasdev}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrar datos de invoices a invoice_detalles para un schema específico';

    /**
     * Mapeo de campos del invoice a códigos de conceptos
     */
    protected $fieldToConceptMap = [
        'total_part' => 'total_part',
        'total_agua' => 'total_agua',
        'total_asen' => 'total_asen',
        'total_mant2' => 'total_mant2',
        'total_mant3' => 'total_mant3',
        'monto_extra' => 'monto_extra',
        'lectura_actual' => 'lectura_actual',
        'lectura_pasada' => 'lectura_pasada',
        'diferencia' => 'diferencia',
        'total_mant' => 'total_mant',
        'total_luzssgg' => 'total_luzssgg',
        'total_luzbci' => 'total_luzbci',
        'total_aguacomun' => 'total_aguacomun',
        'total_extraordinaria' => 'total_extraordinario', // Nota: el concepto es 'total_extraordinario' pero el campo es 'total_extraordinaria'
    ];

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $schema = $this->argument('schema');
        
        $this->info("Iniciando migración de detalles para el schema: {$schema}");

        // Crear conexión dinámica
        $connectionName = 'invoices_' . md5($schema);
        
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

        try {
            // Verificar que las tablas existan
            $tables = DB::connection($connectionName)->select("SHOW TABLES LIKE 'invoice_detalles'");
            if (empty($tables)) {
                $this->error("La tabla invoice_detalles no existe en el schema {$schema}");
                return 1;
            }

            $tables = DB::connection($connectionName)->select("SHOW TABLES LIKE 'conceptos'");
            if (empty($tables)) {
                $this->error("La tabla conceptos no existe en el schema {$schema}");
                return 1;
            }

            // Obtener todos los conceptos y crear un mapa por código
            $conceptos = DB::connection($connectionName)
                ->table('conceptos')
                ->get()
                ->keyBy('codigo');

            $this->info("Conceptos encontrados: " . $conceptos->count());

            // Obtener todos los invoices
            $invoices = DB::connection($connectionName)
                ->table('invoices')
                ->get();

            $this->info("Invoices encontrados: " . $invoices->count());

            $bar = $this->output->createProgressBar($invoices->count());
            $bar->start();

            $totalInserted = 0;
            $totalSkipped = 0;

            foreach ($invoices as $invoice) {
                $inserted = 0;
                $skipped = 0;

                // Verificar si ya tiene detalles
                $existingDetails = DB::connection($connectionName)
                    ->table('invoice_detalles')
                    ->where('invoice_id', $invoice->id)
                    ->count();

                if ($existingDetails > 0) {
                    // Ya tiene detalles, saltar
                    $totalSkipped++;
                    $bar->advance();
                    continue;
                }

                // Procesar cada campo mapeado
                foreach ($this->fieldToConceptMap as $invoiceField => $conceptCode) {
                    $value = $invoice->$invoiceField ?? null;

                    // Saltar si el valor es null, vacío, 0, o "0"
                    if ($value === null || $value === '' || $value === '0' || $value === 0 || $value === '0.00' || $value === '0.000') {
                        continue;
                    }

                    // Buscar el concepto
                    $concepto = $conceptos->get($conceptCode);
                    if (!$concepto) {
                        $this->warn("Concepto no encontrado: {$conceptCode} para invoice {$invoice->id}");
                        continue;
                    }

                    // Preparar datos para insertar (estructura simplificada)
                    $detalleData = [
                        'invoice_id' => $invoice->id,
                        'concepto_id' => $concepto->id,
                        'monto' => (float) $value,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];

                    // Insertar el detalle
                    DB::connection($connectionName)
                        ->table('invoice_detalles')
                        ->insert($detalleData);

                    $inserted++;
                }

                if ($inserted > 0) {
                    $totalInserted++;
                } else {
                    $totalSkipped++;
                }

                $bar->advance();
            }

            $bar->finish();
            $this->newLine(2);
            $this->info("Migración completada!");
            $this->info("Invoices procesados con detalles: {$totalInserted}");
            $this->info("Invoices saltados (ya tenían detalles o sin valores): {$totalSkipped}");

            return 0;

        } catch (\Exception $e) {
            $this->error("Error durante la migración: " . $e->getMessage());
            $this->error($e->getTraceAsString());
            return 1;
        }
    }
}

