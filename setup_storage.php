<?php
// setup_storage.php - Crear enlace simbólico
// ELIMINA ESTE ARCHIVO DESPUÉS DE USARLO

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Crear enlace simbólico
try {
    Artisan::call('storage:link');
    echo "✅ Enlace simbólico creado exitosamente.<br>";
} catch (Exception $e) {
    echo "⚠️ Error: " . $e->getMessage() . "<br>";
    echo "Puede que el enlace ya exista, verifica en public/storage<br>";
}

echo "⚠️ IMPORTANTE: Elimina este archivo (setup_storage.php) ahora por seguridad.";
