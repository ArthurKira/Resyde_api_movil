<?php
// setup_swagger.php - Generar documentación Swagger
// ELIMINA ESTE ARCHIVO DESPUÉS DE USARLO

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Generar Swagger
try {
    Artisan::call('l5-swagger:generate');
    echo "✅ Documentación Swagger generada exitosamente.<br>";
} catch (Exception $e) {
    echo "⚠️ Error: " . $e->getMessage() . "<br>";
}

echo "⚠️ IMPORTANTE: Elimina este archivo (setup_swagger.php) ahora por seguridad.";
