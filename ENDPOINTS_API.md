# 📋 Documentación Completa de Endpoints - API Resyde

Este documento lista todos los endpoints disponibles en la API, organizados por categorías.

---

## 🔐 1. AUTENTICACIÓN

### Rutas Públicas (No requieren autenticación)

| Método | Endpoint | Descripción | Controlador |
|--------|----------|-------------|-------------|
| `POST` | `/api/auth/register` | Registrar nuevo usuario | `AuthController@register` |
| `POST` | `/api/auth/login` | Iniciar sesión | `AuthController@login` |

### Rutas Protegidas (Requieren token Sanctum)

| Método | Endpoint | Descripción | Controlador |
|--------|----------|-------------|-------------|
| `POST` | `/api/auth/logout` | Cerrar sesión | `AuthController@logout` |
| `GET` | `/api/auth/me` | Obtener usuario autenticado | `AuthController@me` |

---

## 👥 2. USUARIOS

**Base URL:** `/api/users`  
**Autenticación:** ✅ Requerida (Sanctum)  
**Controlador:** `UserController`

| Método | Endpoint | Descripción | Parámetros Query |
|--------|----------|-------------|-----------------|
| `GET` | `/api/users` | Listar usuarios (paginado) | `residencia_id`, `perfil`, `page`, `per_page` |
| `GET` | `/api/users/{id}` | Obtener usuario específico | - |
| `PUT/PATCH` | `/api/users/{id}` | Actualizar usuario | - |
| `DELETE` | `/api/users/{id}` | Eliminar usuario | - |

**Nota:** `POST /api/users` (crear) no está implementado, solo se puede crear mediante `/api/auth/register`.

---

## 🏢 3. RESIDENCIAS

**Base URL:** `/api/residencias`  
**Autenticación:** ✅ Requerida (Sanctum)  
**Controlador:** `ResidenciaController`

| Método | Endpoint | Descripción | Parámetros Query |
|--------|----------|-------------|-----------------|
| `GET` | `/api/residencias` | Listar residencias (paginado) | `search`, `schema`, `con_schema`, `sort_by`, `sort_order`, `page`, `per_page` |
| `POST` | `/api/residencias` | Crear residencia | - |
| `GET` | `/api/residencias/{id}` | Obtener residencia específica | - |
| `PUT/PATCH` | `/api/residencias/{id}` | Actualizar residencia | - |
| `DELETE` | `/api/residencias/{id}` | Eliminar residencia | - |

---

## 📄 4. RECIBOS (INVOICES)

**Base URL:** `/api/recibos`  
**Autenticación:** ✅ Requerida (Sanctum)  
**Controlador:** `InvoiceController`

| Método | Endpoint | Descripción | Parámetros Query/Body |
|--------|----------|-------------|----------------------|
| `GET` | `/api/recibos` | Listar recibos (paginado) | `schema`, `tenant`, `house`, `year`, `month`, `status`, `page`, `per_page` |
| `GET` | `/api/recibos/{id}` | Obtener recibo específico | `schema` (query) |
| `POST` | `/api/recibos/{id}/medidor` | Subir imagen medidor y actualizar lectura | `imagen` (file), `lectura_actual` (string), `schema` (query) |

**Notas:**
- Usuarios con `residencia_id = 0` (admin) pueden especificar `schema` para acceder a cualquier schema
- Usuarios normales usan el schema de su residencia asociada
- La imagen se guarda en el storage del ERP (`erp_storage`)

---

## 🏠 5. DEPARTAMENTOS (HOUSES)

**Base URL:** `/api/departamentos`  
**Autenticación:** ✅ Requerida (Sanctum)  
**Controlador:** `HouseController`

| Método | Endpoint | Descripción | Parámetros Query |
|--------|----------|-------------|-----------------|
| `GET` | `/api/departamentos` | Listar departamentos (paginado) | `schema` (requerido si `residencia_id = 0`), `house_number`, `features`, `status`, `idresidencia`, `sort_by`, `sort_order`, `page`, `per_page` |
| `GET` | `/api/departamentos/{id}` | Obtener departamento específico | `schema` (requerido si `residencia_id = 0`) |

**Notas:**
- Los datos se obtienen de la base de datos del ERP (schema dinámico)
- Usuarios admin deben especificar `schema` en query params

---

## 👤 6. RESIDENTES (TENANTS)

**Base URL:** `/api/residentes`  
**Autenticación:** ✅ Requerida (Sanctum)  
**Controlador:** `TenantController`

| Método | Endpoint | Descripción | Parámetros Query |
|--------|----------|-------------|-----------------|
| `GET` | `/api/residentes` | Listar residentes (paginado) | `schema` (requerido si `residencia_id = 0`), `fullname`, `email`, `phone_number`, `house`, `status`, `sort_by`, `sort_order`, `page`, `per_page` |
| `GET` | `/api/residentes/{id}` | Obtener residente específico | `schema` (requerido si `residencia_id = 0`) |

**Notas:**
- Los datos se obtienen de la base de datos del ERP (schema dinámico)
- Usuarios admin deben especificar `schema` en query params

---

## 📱 7. API MÓVIL - SISTEMA DE ASISTENCIA

**Base URL:** `/api/mobile`  
**Autenticación:** ✅ Requerida (Sanctum)  
**Nota:** Usa el mismo login que el resto de la API (`/api/auth/login`)

### 7.1. Datos del Usuario Móvil

| Método | Endpoint | Descripción | Controlador |
|--------|----------|-------------|-------------|
| `GET` | `/api/mobile/user` | Obtener datos del usuario con info de personal y horario | `MobileAuthController@user` |

**Response incluye:**
- Datos del usuario
- Información del personal asociado
- Personal_residencia activa
- Horario actual (asignación recurrente)

---

### 7.2. Asistencia

| Método | Endpoint | Descripción | Parámetros |
|--------|----------|-------------|-----------|
| `GET` | `/api/mobile/asistencia/estado` | Estado de asistencia del día actual | `dni_ce` (query, opcional) |
| `POST` | `/api/mobile/asistencia/marcar-entrada` | Marcar entrada | `latitud`, `longitud` (body, requeridos), `dni_ce` (body, opcional) |
| `POST` | `/api/mobile/asistencia/marcar-salida` | Marcar salida | `latitud`, `longitud` (body, requeridos), `dni_ce` (body, opcional) |
| `GET` | `/api/mobile/asistencia/historial` | Historial de asistencia | `dni_ce` (query, opcional), `limite`, `desde`, `hasta` |

**Notas:**
- Sin `dni_ce`: Marca/ve asistencia propia
- Con `dni_ce`: Marca/ve asistencia de otro empleado (cualquier usuario autenticado puede hacerlo)
- Las coordenadas GPS son obligatorias para entrada y salida
- El sistema valida horarios, vacaciones y licencias automáticamente

---

## 📊 Resumen por Categoría

| Categoría | Endpoints Públicos | Endpoints Protegidos | Total |
|-----------|-------------------|---------------------|-------|
| Autenticación | 2 | 2 | 4 |
| Usuarios | 0 | 4 | 4 |
| Residencias | 0 | 5 | 5 |
| Recibos | 0 | 3 | 3 |
| Departamentos | 0 | 2 | 2 |
| Residentes | 0 | 2 | 2 |
| API Móvil - Asistencia | 0 | 5 | 5 |
| **TOTAL** | **2** | **23** | **25** |

---

## 🔑 Autenticación

### Cómo autenticarse:

1. **Login:**
   ```bash
   POST /api/auth/login
   Body: {
     "email": "usuario@example.com",
     "password": "password123"
   }
   ```

2. **Obtener token:**
   ```json
   {
     "token": "1|WMkCpG8H1P8Us1itSBnzfgxCLoiHxU9MFotynyO2179418cf"
   }
   ```

3. **Usar token en peticiones:**
   ```
   Authorization: Bearer {token}
   ```

---

## 📝 Notas Importantes

### Multi-tenancy (Schemas)
- Los endpoints de **Recibos**, **Departamentos** y **Residentes** usan schemas dinámicos
- Usuarios con `residencia_id = 0` (admin) pueden especificar `schema` en query params
- Usuarios normales usan automáticamente el schema de su residencia

### Paginación
- La mayoría de endpoints de listado soportan paginación
- Parámetros: `page` (default: 1), `per_page` (default: 15)

### Filtros
- Muchos endpoints soportan filtros mediante query parameters
- Consulta la documentación específica de cada endpoint para ver filtros disponibles

### Respuestas
- Todas las respuestas son en formato JSON
- Códigos HTTP estándar: 200 (OK), 201 (Created), 400 (Bad Request), 401 (Unauthorized), 404 (Not Found), 422 (Validation Error), 500 (Server Error)

---

## 🗺️ Mapa de Endpoints

```
/api
├── /auth
│   ├── POST   /register          [Público]
│   ├── POST   /login             [Público]
│   ├── POST   /logout            [Protegido]
│   └── GET    /me               [Protegido]
│
├── /users
│   ├── GET    /                  [Protegido]
│   ├── GET    /{id}              [Protegido]
│   ├── PUT    /{id}              [Protegido]
│   └── DELETE /{id}              [Protegido]
│
├── /residencias
│   ├── GET    /                  [Protegido]
│   ├── POST   /                  [Protegido]
│   ├── GET    /{id}              [Protegido]
│   ├── PUT    /{id}              [Protegido]
│   └── DELETE /{id}              [Protegido]
│
├── /recibos
│   ├── GET    /                  [Protegido]
│   ├── GET    /{id}              [Protegido]
│   └── POST   /{id}/medidor      [Protegido]
│
├── /departamentos
│   ├── GET    /                  [Protegido]
│   └── GET    /{id}              [Protegido]
│
├── /residentes
│   ├── GET    /                  [Protegido]
│   └── GET    /{id}              [Protegido]
│
└── /mobile
    ├── GET    /user              [Protegido]
    └── /asistencia
        ├── GET    /estado        [Protegido]
        ├── POST   /marcar-entrada [Protegido]
        ├── POST   /marcar-salida  [Protegido]
        └── GET    /historial      [Protegido]
```

---

## 📚 Documentación Adicional

- **Swagger/OpenAPI:** Disponible en `/api/documentation` (si está configurado)
- **Flujo de Asistencia Propia:** Ver `FLUJO_ASISTENCIA_PROPIA.md`
- **Guía de Cambios Web:** Ver `GUIA_CAMBIOS_WEB.md`

---

## 🔄 Versión

- **API Version:** 1.0.0
- **Laravel:** 10.10
- **PHP:** ^8.1
- **Autenticación:** Laravel Sanctum 3.2
