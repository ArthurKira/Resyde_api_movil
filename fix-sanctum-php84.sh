#!/bin/bash

# Script para aplicar compatibilidad PHP 8.4 a Laravel Sanctum
# Ejecutar después de: composer install o composer update

echo "🔧 Aplicando compatibilidad PHP 8.4 a Laravel Sanctum..."

# Archivo 1: HasApiTokens.php
if [ -f "vendor/laravel/sanctum/src/HasApiTokens.php" ]; then
    # Reemplazar solo si no tiene el ? ya
    sed -i '' 's/DateTimeInterface $expiresAt = null/?DateTimeInterface $expiresAt = null/g' vendor/laravel/sanctum/src/HasApiTokens.php
    # Corregir doble ??
    sed -i '' 's/??DateTimeInterface/?DateTimeInterface/g' vendor/laravel/sanctum/src/HasApiTokens.php
    echo "✅ HasApiTokens.php actualizado"
else
    echo "⚠️  HasApiTokens.php no encontrado"
fi

# Archivo 2: Guard.php
if [ -f "vendor/laravel/sanctum/src/Guard.php" ]; then
    # Reemplazar solo si no tiene el ? ya
    sed -i '' 's/string $token = null/?string $token = null/g' vendor/laravel/sanctum/src/Guard.php
    # Corregir doble ??
    sed -i '' 's/??string/?string/g' vendor/laravel/sanctum/src/Guard.php
    echo "✅ Guard.php actualizado"
else
    echo "⚠️  Guard.php no encontrado"
fi

echo "✨ Compatibilidad PHP 8.4 aplicada correctamente"

