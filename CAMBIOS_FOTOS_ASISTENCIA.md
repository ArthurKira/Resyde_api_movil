# 📸 Cambios Implementados: Fotos de Asistencia

## ✅ Cambios Realizados

### 1. **Modelo `RegistroAsistencia`**
- ✅ Agregados `foto_entrada` y `foto_salida` a `$fillable`

### 2. **Controlador `MobileAsistenciaController`**
- ✅ Agregado método helper `guardarFotoAsistencia()` para guardar fotos con nombre cifrado
- ✅ Modificado `marcarEntrada()` para validar y guardar foto
- ✅ Modificado `marcarSalida()` para validar y guardar foto
- ✅ Actualizado `estado()` para incluir URLs de fotos
- ✅ Actualizado `historial()` para incluir URLs de fotos
- ✅ Respuestas JSON actualizadas con URLs de fotos

---

## 🔧 Configuración en `.env`

Agrega estas variables a tu archivo `.env`:

```env
# Ruta base para guardar fotos de asistencia (relativa al storage del ERP)
ASISTENCIA_FOTOS_PATH=asistencia/fotos

# URL pública para acceder a las fotos (opcional, se genera automáticamente desde ERP_STORAGE_URL)
# ASISTENCIA_FOTOS_URL=https://erp.tudominio.com/storage/asistencia/fotos
```

**Nota:** Si no defines `ASISTENCIA_FOTOS_PATH`, se usará por defecto `asistencia/fotos`.

---

## 📁 Estructura de Almacenamiento

Las fotos se guardan en:
```
{ERP_STORAGE_PATH}/asistencia/fotos/{año}/{mes}/entrada_{hash}.{extension}
{ERP_STORAGE_PATH}/asistencia/fotos/{año}/{mes}/salida_{hash}.{extension}
```

**Ejemplo:**
```
../resyde_erp/storage/app/public/asistencia/fotos/2025/01/entrada_a1b2c3d4e5f6g7.jpg
../resyde_erp/storage/app/public/asistencia/fotos/2025/01/salida_x9y8z7w6v5u4t3.jpg
```

---

## 🔐 Cifrado del Nombre de Archivo

El nombre del archivo se genera usando un hash SHA256 único:
- **Combinación:** `id_registro` + `timestamp` + `random_bytes(16)` + `personal_id`
- **Hash:** Primeros 16 caracteres del SHA256
- **Formato:** `{tipo}_{hash}.{extension}`

**Ejemplo:**
- `entrada_a1b2c3d4e5f6g7.jpg`
- `salida_x9y8z7w6v5u4t3.png`

Esto garantiza nombres únicos y evita colisiones.

---

## 📤 Nuevos Parámetros en los Endpoints

### Marcar Entrada
**Endpoint:** `POST /api/mobile/asistencia/marcar-entrada`

**Body (multipart/form-data):**
```
latitud: -12.046374 (requerido)
longitud: -77.042793 (requerido)
foto: [archivo imagen] (requerido)
dni_ce: "12345678" (opcional)
```

**Validaciones de la foto:**
- Tipo: `image`
- Formatos permitidos: `jpeg`, `jpg`, `png`, `webp`
- Tamaño máximo: `5120 KB` (5 MB)

### Marcar Salida
**Endpoint:** `POST /api/mobile/asistencia/marcar-salida`

**Body (multipart/form-data):**
```
latitud: -12.046374 (requerido)
longitud: -77.042793 (requerido)
foto: [archivo imagen] (requerido)
dni_ce: "12345678" (opcional)
```

**Validaciones de la foto:**
- Tipo: `image`
- Formatos permitidos: `jpeg`, `jpg`, `png`, `webp`
- Tamaño máximo: `5120 KB` (5 MB)

---

## 📥 Respuestas Actualizadas

### Marcar Entrada - Respuesta
```json
{
  "success": true,
  "message": "Entrada marcada correctamente",
  "registro": {
    "id_registro": 123,
    "fecha_entrada": "2025-01-15",
    "hora_entrada": "08:05",
    "latitud_entrada": "-12.046374",
    "longitud_entrada": "-77.042793",
    "foto_entrada": "asistencia/fotos/2025/01/entrada_a1b2c3d4e5f6g7.jpg",
    "foto_entrada_url": "https://erp.tudominio.com/storage/asistencia/fotos/2025/01/entrada_a1b2c3d4e5f6g7.jpg",
    "estado": "Presente"
  }
}
```

### Marcar Salida - Respuesta
```json
{
  "success": true,
  "message": "Salida marcada correctamente",
  "registro": {
    "id_registro": 123,
    "fecha_entrada": "2025-01-15",
    "hora_entrada": "08:05",
    "latitud_entrada": "-12.046374",
    "longitud_entrada": "-77.042793",
    "foto_entrada": "asistencia/fotos/2025/01/entrada_a1b2c3d4e5f6g7.jpg",
    "foto_entrada_url": "https://erp.tudominio.com/storage/asistencia/fotos/2025/01/entrada_a1b2c3d4e5f6g7.jpg",
    "fecha_salida": "2025-01-15",
    "hora_salida": "17:30",
    "latitud_salida": "-12.046374",
    "longitud_salida": "-77.042793",
    "foto_salida": "asistencia/fotos/2025/01/salida_x9y8z7w6v5u4t3.jpg",
    "foto_salida_url": "https://erp.tudominio.com/storage/asistencia/fotos/2025/01/salida_x9y8z7w6v5u4t3.jpg",
    "estado": "Presente"
  }
}
```

### Estado - Respuesta Actualizada
```json
{
  "success": true,
  "fecha": "2025-01-15",
  "tiene_entrada": true,
  "tiene_salida": false,
  "registro": {
    "id_registro": 123,
    "foto_entrada": "asistencia/fotos/2025/01/entrada_a1b2c3d4e5f6g7.jpg",
    "foto_entrada_url": "https://erp.tudominio.com/storage/asistencia/fotos/2025/01/entrada_a1b2c3d4e5f6g7.jpg",
    "foto_salida": null,
    "foto_salida_url": null,
    // ... otros campos
  }
}
```

### Historial - Respuesta Actualizada
```json
{
  "success": true,
  "total": 25,
  "historial": [
    {
      "id_registro": 123,
      "foto_entrada": "asistencia/fotos/2025/01/entrada_a1b2c3d4e5f6g7.jpg",
      "foto_entrada_url": "https://erp.tudominio.com/storage/asistencia/fotos/2025/01/entrada_a1b2c3d4e5f6g7.jpg",
      "foto_salida": "asistencia/fotos/2025/01/salida_x9y8z7w6v5u4t3.jpg",
      "foto_salida_url": "https://erp.tudominio.com/storage/asistencia/fotos/2025/01/salida_x9y8z7w6v5u4t3.jpg",
      // ... otros campos
    }
  ]
}
```

---

## 💻 Ejemplo de Uso (cURL)

### Marcar Entrada con Foto
```bash
curl -X POST http://127.0.0.1:8000/api/mobile/asistencia/marcar-entrada \
  -H "Authorization: Bearer {token}" \
  -F "latitud=-12.046374" \
  -F "longitud=-77.042793" \
  -F "foto=@/ruta/a/imagen.jpg"
```

### Marcar Salida con Foto
```bash
curl -X POST http://127.0.0.1:8000/api/mobile/asistencia/marcar-salida \
  -H "Authorization: Bearer {token}" \
  -F "latitud=-12.046374" \
  -F "longitud=-77.042793" \
  -F "foto=@/ruta/a/imagen.jpg"
```

---

## 💻 Ejemplo de Uso (JavaScript/Fetch)

### Marcar Entrada con Foto
```javascript
const formData = new FormData();
formData.append('latitud', -12.046374);
formData.append('longitud', -77.042793);
formData.append('foto', fileInput.files[0]); // fileInput es un input type="file"

const response = await fetch('http://127.0.0.1:8000/api/mobile/asistencia/marcar-entrada', {
  method: 'POST',
  headers: {
    'Authorization': 'Bearer ' + token
    // NO incluir 'Content-Type': fetch lo hace automáticamente para FormData
  },
  body: formData
});

const data = await response.json();
console.log(data);
```

---

## ⚠️ Errores Posibles

### Error: Foto requerida
```json
{
  "message": "The foto field is required.",
  "errors": {
    "foto": ["The foto field is required."]
  }
}
```

### Error: Formato inválido
```json
{
  "message": "The foto must be an image.",
  "errors": {
    "foto": ["The foto must be an image."]
  }
}
```

### Error: Tamaño excedido
```json
{
  "message": "The foto may not be greater than 5120 kilobytes.",
  "errors": {
    "foto": ["The foto may not be greater than 5120 kilobytes."]
  }
}
```

---

## ✅ Checklist de Implementación

- [x] Modelo actualizado con `foto_entrada` y `foto_salida` en `$fillable`
- [x] Método helper `guardarFotoAsistencia()` implementado
- [x] Validación de foto en `marcarEntrada()`
- [x] Validación de foto en `marcarSalida()`
- [x] Guardado de foto con nombre cifrado
- [x] URLs públicas generadas correctamente
- [x] Respuestas JSON actualizadas
- [x] Método `estado()` actualizado
- [x] Método `historial()` actualizado
- [ ] Variables agregadas en `.env` (debes hacerlo manualmente)

---

## 📝 Notas Importantes

1. **Multipart/form-data:** Los endpoints ahora requieren `multipart/form-data` en lugar de `application/json` para enviar la foto
2. **Nombre cifrado:** Los nombres de archivo son únicos y no se repiten gracias al hash
3. **Storage compartido:** Las fotos se guardan en el mismo storage del ERP (`erp_storage`)
4. **Ruta configurable:** La ruta base se puede cambiar desde `.env` con `ASISTENCIA_FOTOS_PATH`
5. **URLs automáticas:** Las URLs se generan automáticamente desde `ERP_STORAGE_URL`

---

## 🔄 Migración de Código Frontend

Si ya tienes código frontend que marca asistencia, necesitas actualizar:

1. **Cambiar Content-Type:** De `application/json` a `multipart/form-data`
2. **Usar FormData:** En lugar de JSON, usar `FormData` para enviar datos
3. **Agregar campo foto:** Incluir el archivo de imagen en el FormData

**Antes:**
```javascript
fetch(url, {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json',
    'Authorization': 'Bearer ' + token
  },
  body: JSON.stringify({
    latitud: -12.046374,
    longitud: -77.042793
  })
});
```

**Después:**
```javascript
const formData = new FormData();
formData.append('latitud', -12.046374);
formData.append('longitud', -77.042793);
formData.append('foto', fotoFile); // Archivo de imagen

fetch(url, {
  method: 'POST',
  headers: {
    'Authorization': 'Bearer ' + token
    // NO incluir Content-Type, fetch lo hace automáticamente
  },
  body: formData
});
```
