# 📱 Guía de Implementación Frontend - Sistema de Asistencia

## 🎯 Resumen

Esta guía explica cómo implementar el sistema de asistencia en tu aplicación móvil (React Native, Flutter, etc.).

---

## 🔗 Base URL

```
http://127.0.0.1:8000/api
```
O en producción:
```
https://tu-dominio.com/api
```

---

## 📋 Flujo Completo

### **1. LOGIN (Autenticación)**

#### Endpoint:
```
POST /api/auth/login
```

#### Request:
```json
{
  "email": "usuario@ejemplo.com",
  "password": "password123"
}
```

#### Response Exitosa (200):
```json
{
  "status": "success",
  "message": "Inicio de sesión exitoso",
  "user": {
    "id": 1,
    "name": "Juan García",
    "email": "usuario@ejemplo.com",
    "perfil": "personal",
    "residencia_id": 2,
    "personal_id": 5
  },
  "token": "51|WMkCpG8H1P8Us1itSBnzfgxCLoiHxU9MFotynyO2179418cf"
}
```

#### Response Error (401):
```json
{
  "message": "Las credenciales proporcionadas son incorrectas.",
  "errors": {
    "email": ["Las credenciales proporcionadas son incorrectas."]
  }
}
```

#### Código de Ejemplo (JavaScript/TypeScript):
```javascript
async function login(email, password) {
  try {
    const response = await fetch('http://127.0.0.1:8000/api/auth/login', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
      },
      body: JSON.stringify({
        email: email,
        password: password,
      }),
    });

    const data = await response.json();

    if (response.ok) {
      // Guardar token en AsyncStorage/SecureStore
      await AsyncStorage.setItem('auth_token', data.token);
      await AsyncStorage.setItem('user', JSON.stringify(data.user));
      
      return {
        success: true,
        token: data.token,
        user: data.user,
      };
    } else {
      return {
        success: false,
        message: data.message || 'Error al iniciar sesión',
      };
    }
  } catch (error) {
    return {
      success: false,
      message: 'Error de conexión: ' + error.message,
    };
  }
}
```

---

### **2. OBTENER DATOS DEL USUARIO (Opcional - con info de personal)**

#### Endpoint:
```
GET /api/mobile/user
```

#### Headers:
```
Authorization: Bearer TU_TOKEN
Accept: application/json
```

#### Response Exitosa (200):
```json
{
  "success": true,
  "user": {
    "id": 1,
    "name": "Juan García",
    "email": "usuario@ejemplo.com",
    "personal": {
      "id_personal": 5,
      "nombres": "Juan",
      "apellidos": "García",
      "dni_ce": "12345678",
      "estado": "Activo"
    },
    "personal_residencia": {
      "id": 10,
      "cargo": "Conserje",
      "residencia": {
        "id_residencia": 2,
        "nombre": "Residencia A"
      }
    },
    "tiene_horario": true,
    "horario_hoy": {
      "hora_entrada": "08:00",
      "hora_salida": "17:00",
      "dias_semana": ["Monday", "Tuesday", "Wednesday", "Thursday", "Friday"],
      "fecha_inicio": "2024-01-01",
      "fecha_fin": null
    }
  }
}
```

#### Código de Ejemplo:
```javascript
async function obtenerDatosUsuario() {
  try {
    const token = await AsyncStorage.getItem('auth_token');
    
    const response = await fetch('http://127.0.0.1:8000/api/mobile/user', {
      method: 'GET',
      headers: {
        'Authorization': `Bearer ${token}`,
        'Accept': 'application/json',
      },
    });

    const data = await response.json();

    if (response.ok) {
      return {
        success: true,
        user: data.user,
      };
    } else {
      return {
        success: false,
        message: data.message || 'Error al obtener datos',
      };
    }
  } catch (error) {
    return {
      success: false,
      message: 'Error de conexión: ' + error.message,
    };
  }
}
```

---

### **3. VERIFICAR ESTADO DE ASISTENCIA**

#### Endpoint:
```
GET /api/mobile/asistencia/estado
```

#### Headers:
```
Authorization: Bearer TU_TOKEN
Accept: application/json
```

#### Response Exitosa (200):
```json
{
  "success": true,
  "fecha": "2024-12-14",
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

#### Response con Entrada Marcada:
```json
{
  "success": true,
  "fecha": "2024-12-14",
  "tiene_entrada": true,
  "tiene_salida": false,
  "puede_marcar_entrada": false,
  "puede_marcar_salida": true,
  "tiene_horario": true,
  "en_vacaciones": false,
  "en_licencia": false,
  "registro": {
    "id_registro": 123,
    "fecha_entrada": "2024-12-14",
    "hora_entrada": "08:15",
    "latitud_entrada": "-12.04637400",
    "longitud_entrada": "-77.04279300",
    "fecha_salida": null,
    "hora_salida": null,
    "latitud_salida": null,
    "longitud_salida": null,
    "estado": "Presente"
  },
  "mensaje": "Puede marcar su salida"
}
```

#### Código de Ejemplo:
```javascript
async function obtenerEstadoAsistencia() {
  try {
    const token = await AsyncStorage.getItem('auth_token');
    
    const response = await fetch('http://127.0.0.1:8000/api/mobile/asistencia/estado', {
      method: 'GET',
      headers: {
        'Authorization': `Bearer ${token}`,
        'Accept': 'application/json',
      },
    });

    const data = await response.json();

    if (response.ok) {
      return {
        success: true,
        estado: data,
      };
    } else {
      return {
        success: false,
        message: data.message || 'Error al obtener estado',
      };
    }
  } catch (error) {
    return {
      success: false,
      message: 'Error de conexión: ' + error.message,
    };
  }
}
```

---

### **4. MARCAR ENTRADA**

#### Endpoint:
```
POST /api/mobile/asistencia/marcar-entrada
```

#### Headers:
```
Authorization: Bearer TU_TOKEN
Accept: application/json
Content-Type: application/json
```

#### Request Body:
```json
{
  "latitud": -12.046374,
  "longitud": -77.042793
}
```

#### Response Exitosa (201):
```json
{
  "success": true,
  "message": "Entrada marcada correctamente",
  "registro": {
    "id_registro": 123,
    "fecha_entrada": "2024-12-14",
    "hora_entrada": "08:15",
    "latitud_entrada": "-12.04637400",
    "longitud_entrada": "-77.04279300",
    "estado": "Presente"
  }
}
```

#### Response Error (400):
```json
{
  "success": false,
  "message": "Ya tiene entrada marcada para hoy"
}
```

#### Código de Ejemplo:
```javascript
async function marcarEntrada(latitud, longitud) {
  try {
    const token = await AsyncStorage.getItem('auth_token');
    
    const response = await fetch('http://127.0.0.1:8000/api/mobile/asistencia/marcar-entrada', {
      method: 'POST',
      headers: {
        'Authorization': `Bearer ${token}`,
        'Accept': 'application/json',
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({
        latitud: latitud,
        longitud: longitud,
      }),
    });

    const data = await response.json();

    if (response.ok) {
      return {
        success: true,
        registro: data.registro,
        message: data.message,
      };
    } else {
      return {
        success: false,
        message: data.message || 'Error al marcar entrada',
      };
    }
  } catch (error) {
    return {
      success: false,
      message: 'Error de conexión: ' + error.message,
    };
  }
}

// Ejemplo de uso con Geolocation (React Native)
import Geolocation from '@react-native-community/geolocation';

async function marcarEntradaConUbicacion() {
  try {
    // Obtener ubicación actual
    const position = await new Promise((resolve, reject) => {
      Geolocation.getCurrentPosition(
        (position) => resolve(position),
        (error) => reject(error),
        { enableHighAccuracy: true, timeout: 15000, maximumAge: 10000 }
      );
    });

    const latitud = position.coords.latitude;
    const longitud = position.coords.longitude;

    // Marcar entrada con coordenadas
    const resultado = await marcarEntrada(latitud, longitud);
    
    return resultado;
  } catch (error) {
    return {
      success: false,
      message: 'Error al obtener ubicación: ' + error.message,
    };
  }
}
```

---

### **5. MARCAR SALIDA**

#### Endpoint:
```
POST /api/mobile/asistencia/marcar-salida
```

#### Headers:
```
Authorization: Bearer TU_TOKEN
Accept: application/json
Content-Type: application/json
```

#### Request Body:
```json
{
  "latitud": -12.046374,
  "longitud": -77.042793
}
```

#### Response Exitosa (200):
```json
{
  "success": true,
  "message": "Salida marcada correctamente",
  "registro": {
    "id_registro": 123,
    "fecha_entrada": "2024-12-14",
    "hora_entrada": "08:15",
    "latitud_entrada": "-12.04637400",
    "longitud_entrada": "-77.04279300",
    "fecha_salida": "2024-12-14",
    "hora_salida": "17:30",
    "latitud_salida": "-12.04637400",
    "longitud_salida": "-77.04279300",
    "estado": "Presente"
  }
}
```

#### Response Error (400):
```json
{
  "success": false,
  "message": "No tiene entrada marcada para hoy"
}
```

#### Código de Ejemplo:
```javascript
async function marcarSalida(latitud, longitud) {
  try {
    const token = await AsyncStorage.getItem('auth_token');
    
    const response = await fetch('http://127.0.0.1:8000/api/mobile/asistencia/marcar-salida', {
      method: 'POST',
      headers: {
        'Authorization': `Bearer ${token}`,
        'Accept': 'application/json',
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({
        latitud: latitud,
        longitud: longitud,
      }),
    });

    const data = await response.json();

    if (response.ok) {
      return {
        success: true,
        registro: data.registro,
        message: data.message,
      };
    } else {
      return {
        success: false,
        message: data.message || 'Error al marcar salida',
      };
    }
  } catch (error) {
    return {
      success: false,
      message: 'Error de conexión: ' + error.message,
    };
  }
}
```

---

### **6. OBTENER HISTORIAL**

#### Endpoint:
```
GET /api/mobile/asistencia/historial?limite=30&desde=2024-01-01&hasta=2024-12-31
```

#### Parámetros de Query (Opcionales):
- `limite`: Número de días (default: 30)
- `desde`: Fecha inicio (formato: YYYY-MM-DD)
- `hasta`: Fecha fin (formato: YYYY-MM-DD)

#### Headers:
```
Authorization: Bearer TU_TOKEN
Accept: application/json
```

#### Response Exitosa (200):
```json
{
  "success": true,
  "total": 25,
  "historial": [
    {
      "id_registro": 123,
      "fecha_entrada": "2024-12-14",
      "hora_entrada": "08:15",
      "latitud_entrada": "-12.04637400",
      "longitud_entrada": "-77.04279300",
      "fecha_salida": "2024-12-14",
      "hora_salida": "17:30",
      "latitud_salida": "-12.04637400",
      "longitud_salida": "-77.04279300",
      "estado": "Presente",
      "observaciones": "Marcado desde app móvil"
    },
    {
      "id_registro": 122,
      "fecha_entrada": "2024-12-13",
      "hora_entrada": "08:00",
      "latitud_entrada": "-12.04637400",
      "longitud_entrada": "-77.04279300",
      "fecha_salida": "2024-12-13",
      "hora_salida": "17:00",
      "latitud_salida": "-12.04637400",
      "longitud_salida": "-77.04279300",
      "estado": "Presente",
      "observaciones": null
    }
  ]
}
```

#### Código de Ejemplo:
```javascript
async function obtenerHistorial(limite = 30, desde = null, hasta = null) {
  try {
    const token = await AsyncStorage.getItem('auth_token');
    
    let url = 'http://127.0.0.1:8000/api/mobile/asistencia/historial?limite=' + limite;
    if (desde) url += '&desde=' + desde;
    if (hasta) url += '&hasta=' + hasta;
    
    const response = await fetch(url, {
      method: 'GET',
      headers: {
        'Authorization': `Bearer ${token}`,
        'Accept': 'application/json',
      },
    });

    const data = await response.json();

    if (response.ok) {
      return {
        success: true,
        historial: data.historial,
        total: data.total,
      };
    } else {
      return {
        success: false,
        message: data.message || 'Error al obtener historial',
      };
    }
  } catch (error) {
    return {
      success: false,
      message: 'Error de conexión: ' + error.message,
    };
  }
}
```

---

### **7. LOGOUT**

#### Endpoint:
```
POST /api/auth/logout
```

#### Headers:
```
Authorization: Bearer TU_TOKEN
Accept: application/json
```

#### Response Exitosa (200):
```json
{
  "message": "Sesión cerrada exitosamente"
}
```

#### Código de Ejemplo:
```javascript
async function logout() {
  try {
    const token = await AsyncStorage.getItem('auth_token');
    
    const response = await fetch('http://127.0.0.1:8000/api/auth/logout', {
      method: 'POST',
      headers: {
        'Authorization': `Bearer ${token}`,
        'Accept': 'application/json',
      },
    });

    // Limpiar datos locales
    await AsyncStorage.removeItem('auth_token');
    await AsyncStorage.removeItem('user');

    return {
      success: true,
      message: 'Sesión cerrada exitosamente',
    };
  } catch (error) {
    return {
      success: false,
      message: 'Error al cerrar sesión: ' + error.message,
    };
  }
}
```

---

## 🔄 Flujo Completo de Uso

### **Pantalla de Login:**
```javascript
// 1. Usuario ingresa email y password
const handleLogin = async (email, password) => {
  const resultado = await login(email, password);
  
  if (resultado.success) {
    // Navegar a pantalla principal
    navigation.navigate('Home');
  } else {
    // Mostrar error
    Alert.alert('Error', resultado.message);
  }
};
```

### **Pantalla Principal de Asistencia:**
```javascript
// 2. Al cargar la pantalla, verificar estado
useEffect(() => {
  cargarEstado();
}, []);

const cargarEstado = async () => {
  const resultado = await obtenerEstadoAsistencia();
  
  if (resultado.success) {
    const estado = resultado.estado;
    
    // Actualizar UI según el estado
    setPuedeMarcarEntrada(estado.puede_marcar_entrada);
    setPuedeMarcarSalida(estado.puede_marcar_salida);
    setMensaje(estado.mensaje);
    setRegistro(estado.registro);
  }
};

// 3. Botón "Marcar Entrada"
const handleMarcarEntrada = async () => {
  // Obtener ubicación
  const position = await obtenerUbicacion();
  
  if (!position) {
    Alert.alert('Error', 'No se pudo obtener la ubicación');
    return;
  }
  
  // Mostrar loading
  setLoading(true);
  
  // Marcar entrada
  const resultado = await marcarEntrada(
    position.coords.latitude,
    position.coords.longitude
  );
  
  setLoading(false);
  
  if (resultado.success) {
    Alert.alert('Éxito', 'Entrada marcada correctamente');
    // Recargar estado
    cargarEstado();
  } else {
    Alert.alert('Error', resultado.message);
  }
};

// 4. Botón "Marcar Salida"
const handleMarcarSalida = async () => {
  // Similar a marcar entrada
  const position = await obtenerUbicacion();
  
  if (!position) {
    Alert.alert('Error', 'No se pudo obtener la ubicación');
    return;
  }
  
  setLoading(true);
  
  const resultado = await marcarSalida(
    position.coords.latitude,
    position.coords.longitude
  );
  
  setLoading(false);
  
  if (resultado.success) {
    Alert.alert('Éxito', 'Salida marcada correctamente');
    cargarEstado();
  } else {
    Alert.alert('Error', resultado.message);
  }
};
```

---

## 📱 Ejemplo de Componente React Native

```javascript
import React, { useState, useEffect } from 'react';
import { View, Text, Button, Alert, ActivityIndicator } from 'react-native';
import Geolocation from '@react-native-community/geolocation';
import AsyncStorage from '@react-native-async-storage/async-storage';

const AsistenciaScreen = ({ navigation }) => {
  const [loading, setLoading] = useState(false);
  const [estado, setEstado] = useState(null);
  const [puedeMarcarEntrada, setPuedeMarcarEntrada] = useState(false);
  const [puedeMarcarSalida, setPuedeMarcarSalida] = useState(false);

  useEffect(() => {
    cargarEstado();
  }, []);

  const cargarEstado = async () => {
    const resultado = await obtenerEstadoAsistencia();
    if (resultado.success) {
      setEstado(resultado.estado);
      setPuedeMarcarEntrada(resultado.estado.puede_marcar_entrada);
      setPuedeMarcarSalida(resultado.estado.puede_marcar_salida);
    }
  };

  const obtenerUbicacion = () => {
    return new Promise((resolve, reject) => {
      Geolocation.getCurrentPosition(
        (position) => resolve(position),
        (error) => reject(error),
        { enableHighAccuracy: true, timeout: 15000, maximumAge: 10000 }
      );
    });
  };

  const handleMarcarEntrada = async () => {
    try {
      setLoading(true);
      const position = await obtenerUbicacion();
      const resultado = await marcarEntrada(
        position.coords.latitude,
        position.coords.longitude
      );
      
      if (resultado.success) {
        Alert.alert('Éxito', 'Entrada marcada correctamente');
        cargarEstado();
      } else {
        Alert.alert('Error', resultado.message);
      }
    } catch (error) {
      Alert.alert('Error', 'No se pudo obtener la ubicación');
    } finally {
      setLoading(false);
    }
  };

  const handleMarcarSalida = async () => {
    try {
      setLoading(true);
      const position = await obtenerUbicacion();
      const resultado = await marcarSalida(
        position.coords.latitude,
        position.coords.longitude
      );
      
      if (resultado.success) {
        Alert.alert('Éxito', 'Salida marcada correctamente');
        cargarEstado();
      } else {
        Alert.alert('Error', resultado.message);
      }
    } catch (error) {
      Alert.alert('Error', 'No se pudo obtener la ubicación');
    } finally {
      setLoading(false);
    }
  };

  return (
    <View style={{ padding: 20 }}>
      <Text style={{ fontSize: 20, marginBottom: 20 }}>
        {estado?.mensaje || 'Cargando...'}
      </Text>

      {estado?.registro && (
        <View style={{ marginBottom: 20 }}>
          <Text>Entrada: {estado.registro.hora_entrada}</Text>
          {estado.registro.hora_salida && (
            <Text>Salida: {estado.registro.hora_salida}</Text>
          )}
        </View>
      )}

      {loading && <ActivityIndicator size="large" />}

      {puedeMarcarEntrada && (
        <Button
          title="Marcar Entrada"
          onPress={handleMarcarEntrada}
          disabled={loading}
        />
      )}

      {puedeMarcarSalida && (
        <Button
          title="Marcar Salida"
          onPress={handleMarcarSalida}
          disabled={loading}
        />
      )}
    </View>
  );
};

export default AsistenciaScreen;
```

---

## ⚠️ Manejo de Errores

### Códigos HTTP:
- **200/201**: Éxito
- **400**: Error de validación (ya marcado, sin horario, etc.)
- **401**: No autenticado (token inválido o expirado)
- **403**: Sin permisos (usuario sin personal_id, etc.)
- **422**: Error de validación (coordenadas inválidas)
- **500**: Error del servidor

### Manejo de Token Expirado:
```javascript
// Interceptor para refrescar token o redirigir a login
const fetchWithAuth = async (url, options = {}) => {
  const token = await AsyncStorage.getItem('auth_token');
  
  const response = await fetch(url, {
    ...options,
    headers: {
      ...options.headers,
      'Authorization': `Bearer ${token}`,
      'Accept': 'application/json',
    },
  });

  // Si el token expiró (401), redirigir a login
  if (response.status === 401) {
    await AsyncStorage.removeItem('auth_token');
    await AsyncStorage.removeItem('user');
    navigation.navigate('Login');
    return null;
  }

  return response;
};
```

---

## 📦 Dependencias Necesarias (React Native)

```json
{
  "dependencies": {
    "@react-native-async-storage/async-storage": "^1.19.0",
    "@react-native-community/geolocation": "^3.0.0"
  }
}
```

### Permisos (Android - AndroidManifest.xml):
```xml
<uses-permission android:name="android.permission.ACCESS_FINE_LOCATION" />
<uses-permission android:name="android.permission.ACCESS_COARSE_LOCATION" />
```

### Permisos (iOS - Info.plist):
```xml
<key>NSLocationWhenInUseUsageDescription</key>
<string>Necesitamos tu ubicación para registrar tu asistencia</string>
```

---

## ✅ Checklist de Implementación

- [ ] Configurar base URL de la API
- [ ] Implementar función de login
- [ ] Guardar token en almacenamiento seguro
- [ ] Implementar función para obtener estado
- [ ] Implementar función para marcar entrada
- [ ] Implementar función para marcar salida
- [ ] Implementar función para obtener historial
- [ ] Configurar permisos de geolocalización
- [ ] Manejar errores de conexión
- [ ] Manejar token expirado
- [ ] Crear UI para mostrar estado
- [ ] Crear botones de acción
- [ ] Probar flujo completo

---

## 🎨 Ejemplo de UI Sugerida

```
┌─────────────────────────────┐
│   Sistema de Asistencia     │
├─────────────────────────────┤
│                             │
│  📅 Fecha: 2024-12-14      │
│                             │
│  Estado: Puede marcar       │
│         su entrada          │
│                             │
│  ┌─────────────────────┐   │
│  │  Marcar Entrada     │   │
│  └─────────────────────┘   │
│                             │
│  ┌─────────────────────┐   │
│  │    Ver Historial    │   │
│  └─────────────────────┘   │
│                             │
└─────────────────────────────┘
```

---

¡Listo para implementar! 🚀
