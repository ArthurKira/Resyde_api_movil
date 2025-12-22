# 📱 Flujo Completo: Marcar Asistencia Propia

Esta guía detalla el flujo completo para que un empleado marque su propia asistencia usando la API móvil.

---

## 🔐 Paso 1: Autenticación (Login)

**Endpoint:** `POST /api/auth/login`

**Request:**
```json
{
  "email": "empleado@example.com",
  "password": "password123"
}
```

**Response (200 OK):**
```json
{
  "message": "Inicio de sesión exitoso",
  "user": {
    "id": 1,
    "name": "Juan Pérez",
    "email": "empleado@example.com",
    "perfil": "personal",
    "personal_id": 5
  },
  "token": "1|WMkCpG8H1P8Us1itSBnzfgxCLoiHxU9MFotynyO2179418cf"
}
```

**⚠️ IMPORTANTE:** Guarda el `token` para usarlo en todas las peticiones siguientes.

---

## 👤 Paso 2: Obtener Datos del Usuario (Opcional pero Recomendado)

**Endpoint:** `GET /api/mobile/user`

**Headers:**
```
Authorization: Bearer {token}
Accept: application/json
```

**Response (200 OK):**
```json
{
  "success": true,
  "user": {
    "id": 1,
    "name": "Juan Pérez",
    "email": "empleado@example.com",
    "perfil": "personal"
  },
  "personal": {
    "id_personal": 5,
    "nombres": "Juan",
    "apellidos": "Pérez",
    "dni_ce": "12345678",
    "estado": "Activo"
  },
  "personal_residencia": {
    "id": 10,
    "id_residencia": 2,
    "cargo": "Portero",
    "activo": true
  },
  "horario_actual": {
    "hora_entrada": "08:00:00",
    "hora_salida": "17:00:00",
    "dias_semana": "Monday,Tuesday,Wednesday,Thursday,Friday"
  }
}
```

**Propósito:** Verificar que el usuario tiene `personal_id` y está asociado a un empleado activo.

---

## 📊 Paso 3: Verificar Estado de Asistencia del Día

**Endpoint:** `GET /api/mobile/asistencia/estado`

**Headers:**
```
Authorization: Bearer {token}
Accept: application/json
```

**Response (200 OK):**
```json
{
  "success": true,
  "fecha": "2025-01-15",
  "tiene_entrada": false,
  "tiene_salida": false,
  "puede_marcar_entrada": true,
  "puede_marcar_salida": false,
  "tiene_horario": true,
  "en_vacaciones": false,
  "en_licencia": false,
  "registro": null,
  "mensaje": "Puede marcar su entrada"
}
```

**Estados posibles:**
- `puede_marcar_entrada: true` → Puede marcar entrada
- `puede_marcar_salida: true` → Puede marcar salida
- `tiene_entrada: true` y `tiene_salida: true` → Asistencia completa del día
- `en_vacaciones: true` → No puede marcar asistencia (está en vacaciones)
- `en_licencia: true` → No puede marcar asistencia (está en licencia)
- `tiene_horario: false` → No tiene horario asignado para hoy

---

## ✅ Paso 4: Marcar Entrada

**Endpoint:** `POST /api/mobile/asistencia/marcar-entrada`

**Headers:**
```
Authorization: Bearer {token}
Accept: application/json
Content-Type: application/json
```

**Request Body:**
```json
{
  "latitud": -12.046374,
  "longitud": -77.042793
}
```

**⚠️ IMPORTANTE:** 
- Las coordenadas son **obligatorias**
- Deben obtenerse del GPS del dispositivo
- Latitud: entre -90 y 90
- Longitud: entre -180 y 180

**Response (201 Created):**
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
    "estado": "Presente"
  }
}
```

**Estados posibles:**
- `"Presente"` → Llegó a tiempo o antes de la hora de entrada
- `"Tardanza"` → Llegó después de la hora de entrada del horario

**Errores posibles:**
- `400` → Ya tiene entrada marcada para hoy
- `400` → No tiene horario asignado para hoy
- `400` → Está en vacaciones/licencia aprobadas
- `403` → Usuario no asociado a un empleado
- `422` → Coordenadas inválidas o faltantes

---

## 🚪 Paso 5: Marcar Salida

**Endpoint:** `POST /api/mobile/asistencia/marcar-salida`

**Headers:**
```
Authorization: Bearer {token}
Accept: application/json
Content-Type: application/json
```

**Request Body:**
```json
{
  "latitud": -12.046374,
  "longitud": -77.042793
}
```

**⚠️ IMPORTANTE:** 
- Debe tener entrada marcada previamente
- Las coordenadas son **obligatorias**
- La hora de salida debe ser posterior a la hora de entrada

**Response (200 OK):**
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
    "fecha_salida": "2025-01-15",
    "hora_salida": "17:30",
    "latitud_salida": "-12.046374",
    "longitud_salida": "-77.042793",
    "estado": "Presente"
  }
}
```

**Errores posibles:**
- `400` → No tiene entrada marcada para hoy
- `400` → Ya tiene salida marcada para hoy
- `400` → La hora de salida debe ser posterior a la entrada
- `403` → Usuario no asociado a un empleado
- `422` → Coordenadas inválidas o faltantes

---

## 📜 Paso 6: Ver Historial de Asistencia

**Endpoint:** `GET /api/mobile/asistencia/historial`

**Headers:**
```
Authorization: Bearer {token}
Accept: application/json
```

**Query Params (Opcionales):**
- `limite`: Número de días a mostrar (default: 30)
- `desde`: Fecha inicio en formato `Y-m-d` (ej: `2025-01-01`)
- `hasta`: Fecha fin en formato `Y-m-d` (ej: `2025-01-31`)

**Ejemplo:**
```
GET /api/mobile/asistencia/historial?limite=30
GET /api/mobile/asistencia/historial?desde=2025-01-01&hasta=2025-01-31
```

**Response (200 OK):**
```json
{
  "success": true,
  "total": 25,
  "historial": [
    {
      "id_registro": 123,
      "fecha_entrada": "2025-01-15",
      "hora_entrada": "08:05",
      "latitud_entrada": "-12.046374",
      "longitud_entrada": "-77.042793",
      "fecha_salida": "2025-01-15",
      "hora_salida": "17:30",
      "latitud_salida": "-12.046374",
      "longitud_salida": "-77.042793",
      "estado": "Presente",
      "observaciones": "Marcado desde app móvil"
    },
    {
      "id_registro": 122,
      "fecha_entrada": "2025-01-14",
      "hora_entrada": "08:10",
      "latitud_entrada": "-12.046374",
      "longitud_entrada": "-77.042793",
      "fecha_salida": "2025-01-14",
      "hora_salida": "17:25",
      "latitud_salida": "-12.046374",
      "longitud_salida": "-77.042793",
      "estado": "Tardanza",
      "observaciones": "Marcado desde app móvil"
    }
  ]
}
```

---

## 🔄 Flujo Completo en Flutter (Ejemplo)

```dart
import 'package:http/http.dart' as http;
import 'dart:convert';
import 'package:geolocator/geolocator.dart';

class AsistenciaService {
  final String baseUrl = 'http://127.0.0.1:8000/api';
  String? token;

  // 1. Login
  Future<bool> login(String email, String password) async {
    final response = await http.post(
      Uri.parse('$baseUrl/auth/login'),
      headers: {'Content-Type': 'application/json'},
      body: jsonEncode({
        'email': email,
        'password': password,
      }),
    );

    if (response.statusCode == 200) {
      final data = jsonDecode(response.body);
      token = data['token'];
      return true;
    }
    return false;
  }

  // 2. Obtener estado de asistencia
  Future<Map<String, dynamic>?> obtenerEstado() async {
    final response = await http.get(
      Uri.parse('$baseUrl/mobile/asistencia/estado'),
      headers: {
        'Authorization': 'Bearer $token',
        'Accept': 'application/json',
      },
    );

    if (response.statusCode == 200) {
      return jsonDecode(response.body);
    }
    return null;
  }

  // 3. Obtener coordenadas GPS
  Future<Map<String, double>?> obtenerCoordenadas() async {
    try {
      Position position = await Geolocator.getCurrentPosition(
        desiredAccuracy: LocationAccuracy.high,
      );
      return {
        'latitud': position.latitude,
        'longitud': position.longitude,
      };
    } catch (e) {
      print('Error obteniendo coordenadas: $e');
      return null;
    }
  }

  // 4. Marcar entrada
  Future<Map<String, dynamic>?> marcarEntrada() async {
    // Obtener coordenadas
    final coordenadas = await obtenerCoordenadas();
    if (coordenadas == null) {
      return {'error': 'No se pudieron obtener las coordenadas'};
    }

    final response = await http.post(
      Uri.parse('$baseUrl/mobile/asistencia/marcar-entrada'),
      headers: {
        'Authorization': 'Bearer $token',
        'Accept': 'application/json',
        'Content-Type': 'application/json',
      },
      body: jsonEncode({
        'latitud': coordenadas['latitud'],
        'longitud': coordenadas['longitud'],
      }),
    );

    if (response.statusCode == 201) {
      return jsonDecode(response.body);
    } else {
      return jsonDecode(response.body);
    }
  }

  // 5. Marcar salida
  Future<Map<String, dynamic>?> marcarSalida() async {
    // Obtener coordenadas
    final coordenadas = await obtenerCoordenadas();
    if (coordenadas == null) {
      return {'error': 'No se pudieron obtener las coordenadas'};
    }

    final response = await http.post(
      Uri.parse('$baseUrl/mobile/asistencia/marcar-salida'),
      headers: {
        'Authorization': 'Bearer $token',
        'Accept': 'application/json',
        'Content-Type': 'application/json',
      },
      body: jsonEncode({
        'latitud': coordenadas['latitud'],
        'longitud': coordenadas['longitud'],
      }),
    );

    if (response.statusCode == 200) {
      return jsonDecode(response.body);
    } else {
      return jsonDecode(response.body);
    }
  }

  // 6. Obtener historial
  Future<Map<String, dynamic>?> obtenerHistorial({int? limite, String? desde, String? hasta}) async {
    final queryParams = <String, String>{};
    if (limite != null) queryParams['limite'] = limite.toString();
    if (desde != null) queryParams['desde'] = desde;
    if (hasta != null) queryParams['hasta'] = hasta;

    final uri = Uri.parse('$baseUrl/mobile/asistencia/historial').replace(queryParameters: queryParams);
    
    final response = await http.get(
      uri,
      headers: {
        'Authorization': 'Bearer $token',
        'Accept': 'application/json',
      },
    );

    if (response.statusCode == 200) {
      return jsonDecode(response.body);
    }
    return null;
  }
}
```

---

## 📋 Resumen del Flujo

1. **Login** → Obtener token de autenticación
2. **Verificar estado** → Ver si puede marcar entrada/salida
3. **Obtener GPS** → Obtener coordenadas del dispositivo
4. **Marcar entrada** → Enviar entrada con coordenadas
5. **Marcar salida** → Enviar salida con coordenadas (después de entrada)
6. **Ver historial** → Consultar registros anteriores

---

## ⚠️ Notas Importantes

1. **Token de autenticación:** Debe incluirse en todas las peticiones como `Authorization: Bearer {token}`
2. **Coordenadas obligatorias:** Tanto entrada como salida requieren latitud y longitud
3. **Orden lógico:** Debe marcar entrada antes de salida
4. **Zona horaria:** El servidor usa `America/Lima` (UTC-5)
5. **Validaciones:** El sistema valida horarios, vacaciones y licencias automáticamente
6. **Sin dni_ce:** Para marcar asistencia propia, NO se debe enviar el parámetro `dni_ce`

---

## 🔗 Endpoints Resumen

| Método | Endpoint | Descripción |
|--------|----------|-------------|
| POST | `/api/auth/login` | Autenticación |
| GET | `/api/mobile/user` | Datos del usuario |
| GET | `/api/mobile/asistencia/estado` | Estado del día |
| POST | `/api/mobile/asistencia/marcar-entrada` | Marcar entrada |
| POST | `/api/mobile/asistencia/marcar-salida` | Marcar salida |
| GET | `/api/mobile/asistencia/historial` | Historial |

---

## ✅ Checklist de Implementación

- [ ] Implementar login y guardar token
- [ ] Solicitar permisos de ubicación (GPS) en Flutter
- [ ] Implementar obtención de coordenadas GPS
- [ ] Implementar verificación de estado antes de marcar
- [ ] Implementar marcado de entrada con coordenadas
- [ ] Implementar marcado de salida con coordenadas
- [ ] Implementar visualización de historial
- [ ] Manejar errores (sin GPS, sin conexión, etc.)
- [ ] Mostrar mensajes de éxito/error al usuario
