<?php
// setup_key.php - Generar APP_KEY
// ELIMINA ESTE ARCHIVO DESPUÉS DE USARLO

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Generar APP_KEY
Artisan::call('key:generate');

echo "✅ APP_KEY generado exitosamente.<br>";
echo "⚠️ IMPORTANTE: Elimina este archivo (setup_key.php) ahora por seguridad.";
