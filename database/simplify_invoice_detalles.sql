-- Script para simplificar la estructura de la tabla invoice_detalles
-- Estructura final: id, invoice_id, concepto_id, monto

-- IMPORTANTE: Ejecutar este script en cada schema que tenga la tabla invoice_detalles
-- Ejemplo: USE uclubperu_rentalmanagement_pruebasdev;

-- Paso 1: Eliminar columnas innecesarias (si existen)
ALTER TABLE `invoice_detalles` 
    DROP COLUMN IF EXISTS `lectura_actual`,
    DROP COLUMN IF EXISTS `lectura_pasada`,
    DROP COLUMN IF EXISTS `diferencia`,
    DROP COLUMN IF EXISTS `observaciones`;

-- Paso 2: Verificar estructura final
-- La tabla debería tener solo:
-- - id (PRIMARY KEY, AUTO_INCREMENT)
-- - invoice_id (INT, FOREIGN KEY a invoices.id)
-- - concepto_id (INT, FOREIGN KEY a conceptos.id)
-- - monto (DECIMAL(10,2))
-- - created_at (TIMESTAMP, nullable)
-- - updated_at (TIMESTAMP, nullable)

-- Si la tabla no existe, crearla con la estructura correcta:
CREATE TABLE IF NOT EXISTS `invoice_detalles` (
    `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
    `invoice_id` int NOT NULL,
    `concepto_id` int NOT NULL,
    `monto` decimal(10,2) NOT NULL DEFAULT 0.00,
    `created_at` timestamp NULL DEFAULT NULL,
    `updated_at` timestamp NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `invoice_detalles_invoice_id_index` (`invoice_id`),
    KEY `invoice_detalles_concepto_id_index` (`concepto_id`),
    CONSTRAINT `invoice_detalles_invoice_id_foreign` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`) ON DELETE CASCADE,
    CONSTRAINT `invoice_detalles_concepto_id_foreign` FOREIGN KEY (`concepto_id`) REFERENCES `conceptos` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

