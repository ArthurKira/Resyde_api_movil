<?php
// clear_cache.php - Limpiar caché de Laravel
// ELIMINA ESTE ARCHIVO DESPUÉS DE USARLO

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

try {
    Artisan::call('config:clear');
    echo "✅ Config cache limpiado.<br>";
    
    Artisan::call('cache:clear');
    echo "✅ Application cache limpiado.<br>";
    
    Artisan::call('route:clear');
    echo "✅ Route cache limpiado.<br>";
    
    Artisan::call('view:clear');
    echo "✅ View cache limpiado.<br>";
    
    echo "<br>✅ Caché limpiado exitosamente.<br>";
    echo "⚠️ IMPORTANTE: Elimina este archivo (clear_cache.php) ahora por seguridad.";
} catch (Exception $e) {
    echo "⚠️ Error: " . $e->getMessage() . "<br>";
}
