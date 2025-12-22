# 🔧 Guía: Cambios Necesarios en el Proyecto Web

Esta guía detalla los cambios que debes hacer en tu proyecto **web** para que guarde las imágenes de medidores de la misma forma que el API.

---

## 📋 Cambios Requeridos

### 1. **Configurar el disco `erp_storage` en `config/filesystems.php`**

Abre el archivo `config/filesystems.php` de tu proyecto web y agrega el disco `erp_storage`:

```php
<?php

return [
    'default' => env('FILESYSTEM_DISK', 'local'),

    'disks' => [
        'local' => [
            'driver' => 'local',
            'root' => storage_path('app'),
            'throw' => false,
        ],

        'public' => [
            'driver' => 'local',
            'root' => storage_path('app/public'),
            'url' => env('APP_URL').'/storage',
            'visibility' => 'public',
            'throw' => false,
        ],

        // ⬇️ AGREGAR ESTE DISCO ⬇️
        'erp_storage' => [
            'driver' => 'local',
            'root' => env('ERP_STORAGE_PATH', base_path('../resyde_erp/storage/app/public')),
            'url' => env('ERP_STORAGE_URL', env('ERP_URL', 'https://erp.tudominio.com').'/storage'),
            'visibility' => 'public',
            'throw' => false,
        ],

        // ... otros discos (s3, etc.)
    ],

    'links' => [
        public_path('storage') => storage_path('app/public'),
    ],
];
```

---

### 2. **Actualizar el Controlador Web**

En tu controlador web (donde subes las imágenes de medidores), cambia el código para usar el disco `erp_storage`:

#### **ANTES (probablemente así):**
```php
// ❌ Guardado en storage local del proyecto web
$rutaCompleta = $imagen->storeAs('medidores/agua/' . $year . '/' . $month, $nombreArchivo);
$urlImagen = Storage::url($rutaCompleta);
```

#### **DESPUÉS (igual que el API):**
```php
// ✅ Guardado en storage del ERP
$rutaCompleta = $imagen->storeAs('medidores/agua/' . $year . '/' . $month, $nombreArchivo, 'erp_storage');
$urlImagen = Storage::disk('erp_storage')->url($rutaCompleta);
```

#### **Código Completo del Método (ejemplo):**

```php
use Illuminate\Support\Facades\Storage;

public function updateMedidor(Request $request, int $id)
{
    try {
        // Validar imagen
        $request->validate([
            'imagen' => 'required|image|mimes:jpeg,jpg,png,webp|max:5120',
            'lectura_actual' => 'required|string',
        ]);

        // Obtener el recibo (invoice)
        $invoice = // ... tu lógica para obtener el invoice
        
        // Obtener año y mes del recibo
        $year = $invoice->year ?? date('Y');
        $month = $invoice->month ?? date('m');
        
        // Crear nombre del archivo
        $nombreArchivo = 'invoice_' . $id . '_' . time() . '.' . $request->file('imagen')->getClientOriginalExtension();
        
        // ⬇️ CAMBIO PRINCIPAL: Usar disco 'erp_storage' ⬇️
        $rutaCompleta = $request->file('imagen')->storeAs(
            'medidores/agua/' . $year . '/' . $month, 
            $nombreArchivo, 
            'erp_storage'  // ← Especificar el disco
        );
        
        // Obtener URL pública
        $urlImagen = Storage::disk('erp_storage')->url($rutaCompleta);
        
        // Actualizar el recibo en la base de datos
        // ... tu lógica para actualizar el invoice con $rutaCompleta
        
        return response()->json([
            'message' => 'Imagen del medidor actualizada exitosamente.',
            'imagen_url' => $urlImagen,
        ]);
        
    } catch (\Exception $e) {
        return response()->json([
            'message' => 'Error al actualizar el medidor: ' . $e->getMessage(),
        ], 500);
    }
}
```

---

### 3. **Configurar Variables de Entorno (.env)**

Agrega estas variables en el archivo `.env` de tu proyecto web:

```env
# Ruta física donde se guardan las imágenes del ERP
ERP_STORAGE_PATH=../resyde_erp/storage/app/public

# URL pública para acceder a las imágenes del ERP
ERP_STORAGE_URL=https://erp.tudominio.com/storage

# URL base del ERP (opcional, usado como fallback)
ERP_URL=https://erp.tudominio.com
```

**Nota:** Ajusta las rutas y URLs según tu configuración del servidor.

---

### 4. **Estructura de Carpetas**

Asegúrate de que la estructura de carpetas sea la misma:

```
medidores/
  └── agua/
      └── {año}/
          └── {mes}/
              └── invoice_{id}_{timestamp}.{extension}
```

**Ejemplo:**
```
medidores/agua/2025/01/invoice_123_1705123456.jpg
```

---

## 🔍 Verificación de Cambios

### Checklist:

- [ ] ✅ Disco `erp_storage` agregado en `config/filesystems.php`
- [ ] ✅ Controlador web usa `'erp_storage'` como tercer parámetro en `storeAs()`
- [ ] ✅ URL generada con `Storage::disk('erp_storage')->url()`
- [ ] ✅ Variables de entorno configuradas en `.env`
- [ ] ✅ Estructura de carpetas correcta (`medidores/agua/{año}/{mes}/`)
- [ ] ✅ Nombre de archivo con formato: `invoice_{id}_{timestamp}.{extension}`

---

## 📝 Comparación: Antes vs Después

### **ANTES (Proyecto Web - Incorrecto):**
```php
// Guarda en: storage/app/public/medidores/agua/...
$rutaCompleta = $imagen->storeAs('medidores/agua/' . $year . '/' . $month, $nombreArchivo);
$urlImagen = Storage::url($rutaCompleta);
// URL: https://web.tudominio.com/storage/medidores/agua/...
```

### **DESPUÉS (Proyecto Web - Correcto):**
```php
// Guarda en: ../resyde_erp/storage/app/public/medidores/agua/...
$rutaCompleta = $imagen->storeAs('medidores/agua/' . $year . '/' . $month, $nombreArchivo, 'erp_storage');
$urlImagen = Storage::disk('erp_storage')->url($rutaCompleta);
// URL: https://erp.tudominio.com/storage/medidores/agua/...
```

---

## 🎯 Puntos Clave

1. **Disco `erp_storage`**: Debe estar configurado en `config/filesystems.php`
2. **Tercer parámetro**: Siempre especificar `'erp_storage'` en `storeAs()`
3. **URL pública**: Usar `Storage::disk('erp_storage')->url()` para generar la URL
4. **Ruta relativa**: Guardar solo la ruta relativa en la BD (ej: `medidores/agua/2025/01/invoice_123.jpg`)
5. **Variables .env**: Configurar `ERP_STORAGE_PATH` y `ERP_STORAGE_URL`

---

## 🔧 Ejemplo Completo de Controlador Web

```php
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

class InvoiceController extends Controller
{
    public function updateMedidor(Request $request, int $id)
    {
        try {
            // Validación
            $request->validate([
                'imagen' => 'required|image|mimes:jpeg,jpg,png,webp|max:5120',
                'lectura_actual' => 'required|string',
            ]);

            // Obtener el recibo
            $invoice = DB::connection('invoices')
                ->table('invoices')
                ->where('id', $id)
                ->first();

            if (!$invoice) {
                return response()->json([
                    'message' => 'Recibo no encontrado.',
                ], 404);
            }

            // Obtener año y mes
            $year = $invoice->year ?? date('Y');
            $month = $invoice->month ?? date('m');
            
            // Crear nombre del archivo
            $imagen = $request->file('imagen');
            $nombreArchivo = 'invoice_' . $id . '_' . time() . '.' . $imagen->getClientOriginalExtension();
            
            // Guardar en el storage del ERP
            $rutaCompleta = $imagen->storeAs(
                'medidores/agua/' . $year . '/' . $month, 
                $nombreArchivo, 
                'erp_storage'  // ← IMPORTANTE: Especificar el disco
            );
            
            // Obtener URL pública
            $urlImagen = Storage::disk('erp_storage')->url($rutaCompleta);
            
            // Calcular diferencia (si aplica)
            $lecturaPasada = $invoice->lectura_pasada ?? null;
            $lecturaActual = $request->lectura_actual;
            $diferencia = null;
            
            if ($lecturaPasada !== null && $lecturaActual !== null) {
                $diferencia = number_format((float)$lecturaActual - (float)$lecturaPasada, 2, '.', '');
            }
            
            // Actualizar el recibo
            $updateData = [
                'medidor_image' => $rutaCompleta,  // Ruta relativa
                'lectura_actual' => $lecturaActual,
            ];
            
            if ($diferencia !== null) {
                $updateData['diferencia'] = $diferencia;
            }
            
            DB::connection('invoices')
                ->table('invoices')
                ->where('id', $id)
                ->update($updateData);
            
            return response()->json([
                'message' => 'Imagen del medidor y lectura actualizadas exitosamente.',
                'imagen_url' => $urlImagen,
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al actualizar el medidor: ' . $e->getMessage(),
            ], 500);
        }
    }
}
```

---

## ⚠️ Notas Importantes

1. **Ruta física**: Las imágenes se guardan en el storage del ERP, no en el proyecto web
2. **URL pública**: La URL apunta al ERP, no al proyecto web
3. **Base de datos**: Guarda solo la ruta relativa (ej: `medidores/agua/2025/01/invoice_123.jpg`)
4. **Permisos**: Asegúrate de que el servidor web tenga permisos de escritura en la carpeta del ERP
5. **Sincronización**: Ambos proyectos (API y Web) guardan en el mismo lugar, por lo que las imágenes son compartidas

---

## ✅ Resultado Final

Después de estos cambios:

- ✅ Las imágenes se guardan en el mismo lugar que el API
- ✅ Las URLs apuntan al mismo dominio (ERP)
- ✅ La estructura de carpetas es idéntica
- ✅ Los nombres de archivo siguen el mismo formato
- ✅ Ambos proyectos (API y Web) comparten las mismas imágenes

---

## 🆘 Troubleshooting

### Error: "Disk [erp_storage] not found"
**Solución:** Verifica que el disco `erp_storage` esté configurado en `config/filesystems.php`

### Error: "Permission denied"
**Solución:** Verifica los permisos de escritura en la carpeta del ERP:
```bash
chmod -R 775 ../resyde_erp/storage/app/public/medidores
```

### Error: "Path does not exist"
**Solución:** Verifica que la ruta `ERP_STORAGE_PATH` en `.env` sea correcta y que la carpeta exista

### Imágenes no se ven
**Solución:** Verifica que `ERP_STORAGE_URL` en `.env` apunte a la URL correcta del ERP
