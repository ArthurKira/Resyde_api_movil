<?php
// setup_optimize.php - Optimizar Laravel
// ELIMINA ESTE ARCHIVO DESPUÉS DE USARLO

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Optimizar
try {
    Artisan::call('config:cache');
    echo "✅ Config cache creado.<br>";
    
    Artisan::call('route:cache');
    echo "✅ Route cache creado.<br>";
    
    Artisan::call('view:cache');
    echo "✅ View cache creado.<br>";
} catch (Exception $e) {
    echo "⚠️ Error: " . $e->getMessage() . "<br>";
}

echo "⚠️ IMPORTANTE: Elimina este archivo (setup_optimize.php) ahora por seguridad.";
