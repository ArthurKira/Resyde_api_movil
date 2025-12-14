<?php
// setup_migrate.php - Ejecutar migraciones
// ELIMINA ESTE ARCHIVO DESPUÉS DE USARLO

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Ejecutar migraciones
try {
    Artisan::call('migrate', ['--force' => true]);
    echo "✅ Migraciones ejecutadas exitosamente.<br>";
    echo Artisan::output();
} catch (Exception $e) {
    echo "⚠️ Error: " . $e->getMessage() . "<br>";
}

echo "⚠️ IMPORTANTE: Elimina este archivo (setup_migrate.php) ahora por seguridad.";
