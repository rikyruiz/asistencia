# Sistema de Control de Asistencia con Geolocalización
## Documento de Requerimientos y Funcionalidades Completas

---

## 📌 VISIÓN GENERAL DEL PROYECTO

### Objetivo Principal
Desarrollar un sistema de control de asistencia para empleados de Alpe Fresh Mexico que permita registrar entradas y salidas mediante geolocalización, asegurando que los empleados estén físicamente presentes en las ubicaciones autorizadas al momento de iniciar su jornada laboral.

### Problemática a Resolver
- Control manual de asistencia propenso a errores
- Falta de verificación de presencia física
- Dificultad para gestionar múltiples ubicaciones
- Necesidad de reportes automatizados
- Control de turnos nocturnos que cruzan días

### Alcance del Sistema
- Gestión de usuarios con diferentes roles
- Control de entrada/salida con geolocalización
- Administración de múltiples ubicaciones
- Generación de reportes
- Sistema de notificaciones por email

---

## 🛠️ REQUERIMIENTOS TÉCNICOS

### Stack de Desarrollo
| Componente | Tecnología | Versión/Detalles |
|------------|-----------|------------------|
| Backend | PHP | Puro, sin framework |
| Arquitectura | MVC | Implementación propia |
| Base de Datos | MySQL | 5.7+ |
| Frontend | HTML5, CSS3, JavaScript | Vanilla JS |
| Servidor Email | SMTP Hostinger | smtp.hostinger.com |
| Timezone | America/Mexico_City | CDMX |
| Estilos | CSS existente | /var/www/marketplace |

### Credenciales del Sistema

#### Base de Datos
```
Host: localhost
Database: asistencia_db
Usuario: ricruiz
Contraseña: Ruor7708028L8+
```

#### Servidor SMTP
```
Servidor: smtp.hostinger.com
Puerto: 587 (TLS) / 465 (SSL)
Email: notificaciones@alpefresh.app
Contraseña: Alpe25879*
```

---

## 👥 REQUERIMIENTOS FUNCIONALES

### 1. GESTIÓN DE USUARIOS

#### 1.1 Registro de Usuarios
- **Campo requeridos:**
  - Email (único)
  - Nombre completo
  - Apellidos
  - PIN de 6 dígitos (configurado durante registro)
  - Rol asignado
  
- **Proceso de registro:**
  1. Formulario de registro con validación
  2. Envío de email de confirmación
  3. Activación de cuenta mediante link
  4. Configuración inicial de PIN
  5. Asignación de rol por admin

#### 1.2 Autenticación
- **Login con:**
  - Email
  - PIN de 6 dígitos
  
- **Características:**
  - Sesión segura con tokens
  - Timeout después de 30 minutos de inactividad
  - Remember me opcional
  - Bloqueo después de 5 intentos fallidos
  
#### 1.3 Recuperación de Acceso
- **Proceso:**
  1. Solicitud con email
  2. Envío de token temporal por correo
  3. Link válido por 24 horas
  4. Formulario para nuevo PIN
  5. Confirmación por email

#### 1.4 Roles y Permisos

| Rol | Permisos | Descripción |
|-----|----------|-------------|
| **Superadmin** | - Acceso total al sistema<br>- Gestión de admins<br>- Configuraciones globales<br>- Todos los permisos de admin | Control total |
| **Admin** | - CRUD de usuarios<br>- CRUD de ubicaciones<br>- Ver todos los reportes<br>- Exportar datos<br>- Gestión de empleados | Gestión operativa |
| **Inspector** | - Solo lectura de todo<br>- Ver reportes<br>- Ver ubicaciones<br>- Ver usuarios<br>- No puede modificar | Auditoría y supervisión |
| **Empleado** | - Clock in/out<br>- Ver historial propio<br>- Cambiar PIN propio<br>- Ver estadísticas propias | Usuario final |

### 2. CONTROL DE ASISTENCIA

#### 2.1 Clock In (Entrada)
- **Requisitos:**
  - Empleado debe estar dentro del geofence
  - GPS activado en dispositivo
  - No tener entrada activa sin salida
  
- **Datos registrados:**
  - Timestamp exacto
  - Coordenadas GPS
  - Ubicación asignada
  - IP del dispositivo
  - User agent
  
- **Validaciones:**
  - Verificar radio de geofence (Haversine)
  - Prevenir entradas duplicadas
  - Validar horario permitido (opcional)

#### 2.2 Clock Out (Salida)
- **Características:**
  - Permitido desde cualquier ubicación
  - Si está fuera: "Fuera de Centro Autorizado"
  - Cálculo automático de horas
  
- **Datos registrados:**
  - Timestamp de salida
  - Coordenadas actuales
  - Estado de geofence
  - Duración de jornada

#### 2.3 Lógica de Turnos Nocturnos
- **Reglas especiales:**
  - Entrada antes de 00:00 puede tener salida después
  - No se corta la sesión a medianoche
  - Mantiene continuidad de la jornada
  - Asignación correcta del día laboral

### 3. GESTIÓN DE UBICACIONES

#### 3.1 CRUD de Ubicaciones
- **Campos:**
  - Nombre de ubicación
  - Dirección completa
  - Coordenadas (lat, lng)
  - Radio en metros
  - Estado (activa/inactiva)
  
- **Funcionalidades:**
  - Mapa interactivo para selección
  - Visualización de radio
  - Múltiples ubicaciones activas
  - Histórico de cambios

#### 3.2 Configuración de Geofence
- **Parámetros:**
  - Radio personalizable (50-500m)
  - Forma circular
  - Tolerancia GPS configurable
  - Modo de prueba sin restricción

### 4. REPORTES Y ESTADÍSTICAS

#### 4.1 Reportes Individuales
- **Contenido:**
  - Historial completo con filtros
  - Total horas por período
  - Promedio de puntualidad
  - Días trabajados vs programados
  - Incidencias (salidas tempranas, etc.)
  
- **Filtros disponibles:**
  - Rango de fechas
  - Ubicación específica
  - Tipo de registro

#### 4.2 Reportes Administrativos
- **Tipos de reportes:**
  - Consolidado general
  - Por ubicación
  - Por departamento/área
  - Horas extra
  - Ausentismo
  
- **Formatos de exportación:**
  - PDF
  - Excel
  - CSV
  - Impresión directa

#### 4.3 Dashboard Analítico
- **Métricas en tiempo real:**
  - Empleados activos actualmente
  - Distribución por ubicación
  - Tendencias de asistencia
  - Alertas de anomalías

### 5. NOTIFICACIONES

#### 5.1 Notificaciones por Email
- **Eventos que generan notificación:**
  - Confirmación de registro
  - Recuperación de PIN
  - Confirmación de entrada/salida (opcional)
  - Alertas administrativas
  - Reportes programados

#### 5.2 Alertas del Sistema
- **Tipos de alertas:**
  - Empleado sin salida registrada
  - Intentos de acceso fuera de geofence
  - Múltiples intentos de login fallidos
  - Ubicaciones sin actividad

---

## 🗄️ REQUERIMIENTOS DE BASE DE DATOS

### Esquema de Tablas

#### Tabla: usuarios
```sql
CREATE TABLE usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(100) UNIQUE NOT NULL,
    nombre VARCHAR(100) NOT NULL,
    apellidos VARCHAR(100) NOT NULL,
    pin CHAR(6) NOT NULL,
    rol ENUM('superadmin', 'admin', 'inspector', 'empleado') DEFAULT 'empleado',
    departamento VARCHAR(100),
    numero_empleado VARCHAR(50),
    activo BOOLEAN DEFAULT TRUE,
    email_verificado BOOLEAN DEFAULT FALSE,
    intentos_login INT DEFAULT 0,
    bloqueado_hasta DATETIME,
    ultimo_login DATETIME,
    token_verificacion VARCHAR(100),
    token_recuperacion VARCHAR(100),
    token_expiracion DATETIME,
    foto_perfil VARCHAR(255),
    telefono VARCHAR(20),
    direccion TEXT,
    fecha_ingreso DATE,
    creado_por INT,
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    actualizado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (creado_por) REFERENCES usuarios(id),
    INDEX idx_email (email),
    INDEX idx_pin (pin),
    INDEX idx_numero_empleado (numero_empleado)
);
```

#### Tabla: ubicaciones
```sql
CREATE TABLE ubicaciones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    codigo VARCHAR(50) UNIQUE,
    direccion TEXT,
    ciudad VARCHAR(100),
    estado VARCHAR(100),
    codigo_postal VARCHAR(10),
    latitud DECIMAL(10, 8) NOT NULL,
    longitud DECIMAL(11, 8) NOT NULL,
    radio_metros INT DEFAULT 100,
    tipo_ubicacion ENUM('oficina', 'campo', 'almacen', 'otro') DEFAULT 'oficina',
    horario_apertura TIME,
    horario_cierre TIME,
    dias_laborales VARCHAR(20) DEFAULT '1,2,3,4,5',
    requiere_foto BOOLEAN DEFAULT FALSE,
    activa BOOLEAN DEFAULT TRUE,
    creado_por INT,
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    actualizado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (creado_por) REFERENCES usuarios(id),
    INDEX idx_codigo (codigo),
    INDEX idx_activa (activa)
);
```

#### Tabla: registros_asistencia
```sql
CREATE TABLE registros_asistencia (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    ubicacion_id INT,
    tipo ENUM('entrada', 'salida') NOT NULL,
    fecha_hora DATETIME NOT NULL,
    fecha_local DATE GENERATED ALWAYS AS (DATE(CONVERT_TZ(fecha_hora, 'UTC', 'America/Mexico_City'))) STORED,
    latitud_registro DECIMAL(10, 8),
    longitud_registro DECIMAL(11, 8),
    precision_gps DECIMAL(6, 2),
    dentro_geofence BOOLEAN DEFAULT TRUE,
    distancia_ubicacion INT,
    metodo_registro ENUM('web', 'app', 'manual', 'sistema') DEFAULT 'web',
    direccion_ip VARCHAR(45),
    user_agent TEXT,
    dispositivo_id VARCHAR(100),
    foto_registro VARCHAR(255),
    notas TEXT,
    editado BOOLEAN DEFAULT FALSE,
    editado_por INT,
    editado_en DATETIME,
    razon_edicion TEXT,
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (ubicacion_id) REFERENCES ubicaciones(id) ON DELETE SET NULL,
    FOREIGN KEY (editado_por) REFERENCES usuarios(id),
    INDEX idx_usuario_fecha (usuario_id, fecha_hora),
    INDEX idx_tipo (tipo),
    INDEX idx_fecha_local (fecha_local)
);
```

#### Tabla: sesiones_trabajo
```sql
CREATE TABLE sesiones_trabajo (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    entrada_id INT NOT NULL,
    salida_id INT,
    ubicacion_id INT,
    fecha_inicio DATE NOT NULL,
    hora_entrada DATETIME NOT NULL,
    hora_salida DATETIME,
    duracion_minutos INT,
    duracion_efectiva_minutos INT,
    tiempo_extra_minutos INT DEFAULT 0,
    estado ENUM('activa', 'completada', 'anormal', 'editada') DEFAULT 'activa',
    tipo_jornada ENUM('normal', 'extra', 'festivo', 'descanso') DEFAULT 'normal',
    observaciones TEXT,
    aprobado_por INT,
    aprobado_en DATETIME,
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    actualizado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (entrada_id) REFERENCES registros_asistencia(id),
    FOREIGN KEY (salida_id) REFERENCES registros_asistencia(id),
    FOREIGN KEY (ubicacion_id) REFERENCES ubicaciones(id),
    FOREIGN KEY (aprobado_por) REFERENCES usuarios(id),
    INDEX idx_usuario_estado (usuario_id, estado),
    INDEX idx_fecha (fecha_inicio),
    INDEX idx_estado (estado)
);
```

#### Tabla: configuracion_sistema
```sql
CREATE TABLE configuracion_sistema (
    id INT AUTO_INCREMENT PRIMARY KEY,
    clave VARCHAR(100) UNIQUE NOT NULL,
    valor TEXT,
    tipo ENUM('string', 'integer', 'boolean', 'json') DEFAULT 'string',
    descripcion TEXT,
    categoria VARCHAR(50),
    editable BOOLEAN DEFAULT TRUE,
    actualizado_por INT,
    actualizado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (actualizado_por) REFERENCES usuarios(id),
    INDEX idx_categoria (categoria)
);
```

#### Tabla: logs_sistema
```sql
CREATE TABLE logs_sistema (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT,
    tipo_evento VARCHAR(50) NOT NULL,
    descripcion TEXT,
    datos_json JSON,
    ip_address VARCHAR(45),
    user_agent TEXT,
    nivel ENUM('info', 'warning', 'error', 'critical') DEFAULT 'info',
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL,
    INDEX idx_tipo (tipo_evento),
    INDEX idx_nivel (nivel),
    INDEX idx_fecha (creado_en)
);
```

---

## 🎨 REQUERIMIENTOS DE INTERFAZ DE USUARIO

### Diseño General
- **Framework CSS:** Reutilizar estilos de /var/www/marketplace
- **Responsive:** Mobile-first approach
- **Idioma:** 100% en español
- **Tema:** Colores corporativos de Alpe Fresh

### Pantallas Principales

#### 1. Pantalla de Login
- Logo de empresa centrado
- Campo de email con validación
- Campo PIN (6 dígitos, teclado numérico en móvil)
- Checkbox "Recordarme"
- Links: "¿Olvidaste tu PIN?" y "Registrarse"
- Mensajes de error claros

#### 2. Dashboard Empleado
- **Sección superior:**
  - Saludo personalizado con nombre
  - Fecha y hora actual (actualización en tiempo real)
  - Estado actual (Trabajando/No trabajando)
  
- **Botón principal central:**
  - Grande y prominente
  - Verde para entrada, Rojo para salida
  - Icono de reloj
  - Texto claro: "REGISTRAR ENTRADA" / "REGISTRAR SALIDA"
  
- **Información de sesión activa:**
  - Hora de entrada
  - Tiempo transcurrido (contador en vivo)
  - Ubicación de entrada
  
- **Historial reciente:**
  - Últimos 7 días
  - Formato de tabla simple
  - Total de horas de la semana

#### 3. Dashboard Administrativo
- **Cards de estadísticas:**
  - Empleados activos ahora
  - Total empleados
  - Promedio horas día
  - Incidencias del día
  
- **Gráficas:**
  - Asistencia semanal (barras)
  - Distribución por ubicación (pie)
  - Tendencia mensual (línea)
  
- **Tabla de actividad en tiempo real:**
  - Últimas 10 entradas/salidas
  - Auto-actualización cada 30 segundos
  
- **Accesos rápidos:**
  - Botones grandes a secciones principales
  - Notificaciones pendientes

#### 4. Gestión de Usuarios
- **Lista principal:**
  - Tabla con paginación (25 por página)
  - Columnas: Foto, Nombre, Email, Rol, Departamento, Estado, Acciones
  - Indicador visual de estado (activo/inactivo)
  
- **Filtros superiores:**
  - Por rol
  - Por estado
  - Por departamento
  - Por ubicación asignada
  - Búsqueda por texto
  
- **Acciones masivas:**
  - Selección múltiple
  - Activar/Desactivar seleccionados
  - Exportar seleccionados
  
- **Modal de creación/edición:**
  - Formulario completo
  - Validación en tiempo real
  - Preview de foto
  - Asignación de ubicaciones permitidas

#### 5. Gestión de Ubicaciones
- **Vista de mapa:**
  - Mapa interactivo con marcadores
  - Círculos mostrando radio de geofence
  - Diferentes colores por tipo
  - Popup con información al hacer clic
  
- **Lista lateral:**
  - Todas las ubicaciones
  - Búsqueda rápida
  - Filtro por estado
  - Botón de agregar nueva
  
- **Formulario de ubicación:**
  - Selector de coordenadas en mapa
  - Búsqueda por dirección
  - Slider para radio (50-500m)
  - Horarios de operación
  - Días laborales

#### 6. Pantalla de Clock In/Out
- **Información de contexto:**
  - Ubicación detectada actual
  - Precisión del GPS
  - Distancia a ubicación más cercana
  
- **Validación visual:**
  - ✅ Dentro del área (verde)
  - ❌ Fuera del área (rojo)
  - ⏳ Obteniendo ubicación (amarillo)
  
- **Botón de acción:**
  - Habilitado/Deshabilitado según ubicación
  - Confirmación antes de procesar
  - Animación de procesamiento
  
- **Resultado:**
  - Mensaje de éxito/error
  - Detalles del registro
  - Opción de cancelar (30 segundos)

#### 7. Reportes
- **Selector de tipo de reporte:**
  - Individual
  - Por ubicación
  - Por departamento
  - General
  
- **Filtros dinámicos:**
  - Rango de fechas (date pickers)
  - Empleados (multi-select)
  - Ubicaciones (multi-select)
  - Estado de jornada
  
- **Vista previa:**
  - Tabla con datos
  - Resumen estadístico
  - Gráficas relevantes
  
- **Opciones de exportación:**
  - PDF (con logo y formato)
  - Excel (datos crudos)
  - CSV (para sistemas externos)
  - Imprimir

### Elementos UI Comunes

#### Navegación
- **Menú lateral (desktop):**
  - Colapso/expansión
  - Iconos y texto
  - Indicador de sección activa
  - Sub-menús desplegables
  
- **Menú inferior (móvil):**
  - 4-5 opciones principales
  - Iconos grandes
  - Badge de notificaciones

#### Notificaciones
- **Toast messages:**
  - Éxito (verde)
  - Error (rojo)
  - Advertencia (amarillo)
  - Info (azul)
  - Auto-dismiss después de 5 segundos

#### Modales
- **Confirmación:**
  - Título claro
  - Mensaje descriptivo
  - Botones Cancelar/Confirmar
  - Overlay oscuro

#### Formularios
- **Validación:**
  - En tiempo real
  - Mensajes bajo campos
  - Indicadores visuales (bordes)
  - Resumen de errores arriba

---

## 🔒 REQUERIMIENTOS DE SEGURIDAD

### Autenticación y Autorización
1. **Hashing de PINs:** bcrypt con salt
2. **Tokens CSRF:** En todos los formularios
3. **Sesiones seguras:** HttpOnly, Secure cookies
4. **Rate limiting:** 5 intentos máximo
5. **Bloqueo temporal:** 15 minutos después de intentos fallidos

### Protección de Datos
1. **SQL Injection:** Prepared statements
2. **XSS:** Sanitización de outputs
3. **HTTPS:** Obligatorio en producción
4. **Logs:** Sin datos sensibles
5. **Backup:** Encriptado y offsite

### Validaciones
1. **Server-side:** Toda validación crítica
2. **Client-side:** Solo para UX
3. **Tipos de datos:** Casting estricto
4. **Tamaños:** Límites en uploads
5. **Formatos:** Regex para emails, etc.

### Auditoría
1. **Log de accesos:** Todos los logins
2. **Log de cambios:** CRUD en entidades críticas
3. **Log de errores:** Excepciones y warnings
4. **Retención:** 90 días mínimo
5. **Acceso a logs:** Solo superadmin

---

## ⚡ REQUERIMIENTOS DE RENDIMIENTO

### Tiempos de Respuesta
- **Páginas estáticas:** < 200ms
- **Operaciones CRUD:** < 500ms
- **Reportes simples:** < 2 segundos
- **Reportes complejos:** < 10 segundos
- **Geolocalización:** < 3 segundos

### Optimizaciones
1. **Caché:**
   - Ubicaciones en localStorage
   - Sesiones en Redis (futuro)
   - Queries frecuentes

2. **Base de datos:**
   - Índices optimizados
   - Queries paginadas
   - Lazy loading

3. **Assets:**
   - Minificación CSS/JS
   - Compresión gzip
   - CDN para librerías

4. **Imágenes:**
   - Lazy loading
   - Formatos optimizados
   - Thumbnails automáticos

### Escalabilidad
- **Usuarios concurrentes:** 100+
- **Registros de asistencia:** 1M+
- **Ubicaciones:** 50+
- **Reportes simultáneos:** 10+

---

## 📱 REQUERIMIENTOS MÓVILES

### Compatibilidad
- **Navegadores:** Chrome, Safari, Firefox, Edge
- **Dispositivos:** iOS 12+, Android 8+
- **Orientación:** Portrait y Landscape
- **Resolución mínima:** 320px ancho

### Funcionalidades Móviles
1. **GPS nativo:** API de geolocalización HTML5
2. **Cámara:** Para foto opcional en clock in
3. **Notificaciones push:** PWA (futuro)
4. **Modo offline:** Cache de última ubicación
5. **Teclado numérico:** Para PIN

### Progressive Web App (Fase 2)
- **Instalable:** Add to homescreen
- **Offline básico:** Service worker
- **Sincronización:** Background sync
- **Push notifications:** Para alertas

---

## 🚀 PLAN DE IMPLEMENTACIÓN

### Fase 1: Fundación (Semana 1)
- [ ] Estructura de proyecto MVC
- [ ] Configuración de base de datos
- [ ] Sistema de routing
- [ ] Modelos base
- [ ] Autenticación con PIN
- [ ] Layouts y templates base

### Fase 2: Core Features (Semana 2)
- [ ] Clock In/Out con geolocalización
- [ ] Validación de geofence
- [ ] CRUD de usuarios
- [ ] CRUD de ubicaciones
- [ ] Dashboards por rol
- [ ] Lógica de turnos nocturnos

### Fase 3: Features Avanzadas (Semana 3)
- [ ] Sistema de reportes
- [ ] Exportación de datos
- [ ] Notificaciones por email
- [ ] Logs y auditoría
- [ ] Configuración del sistema
- [ ] Filtros y búsquedas

### Fase 4: Optimización (Semana 4)
- [ ] Testing completo
- [ ] Optimización de queries
- [ ] Caché implementation
- [ ] Documentación de código
- [ ] Manual de usuario
- [ ] Preparación para deployment

### Fase 5: Deployment (Semana 5)
- [ ] Configuración de servidor
- [ ] SSL/HTTPS
- [ ] Migración de datos
- [ ] Testing en producción
- [ ] Capacitación de usuarios
- [ ] Go-live

---

## 📊 MÉTRICAS DE ÉXITO

### KPIs del Sistema
1. **Adopción:** 95% de empleados usando el sistema en 30 días
2. **Precisión:** 99% de registros correctos
3. **Disponibilidad:** 99.9% uptime
4. **Performance:** 90% de operaciones < 1 segundo
5. **Satisfacción:** 4+ estrellas de usuarios

### Beneficios Esperados
1. **Reducción de errores:** 90% menos errores manuales
2. **Ahorro de tiempo:** 2 horas/semana en procesamiento
3. **Compliance:** 100% cumplimiento normativo
4. **Visibilidad:** Reportes en tiempo real
5. **Control:** Prevención de fraude de tiempo

---

## 🔧 CONFIGURACIONES ESPECIALES

### Parametrización del Sistema
```php
// config/app.php
return [
    'timezone' => 'America/Mexico_City',
    'locale' => 'es_MX',
    'pin_length' => 6,
    'session_lifetime' => 30, // minutos
    'max_login_attempts' => 5,
    'lockout_time' => 15, // minutos
    'geofence_tolerance' => 10, // metros
    'clock_out_grace_period' => 30, // segundos para cancelar
    'report_max_days' => 90,
    'photo_max_size' => 5, // MB
    'export_chunk_size' => 1000,
    'api_rate_limit' => 60, // requests per minute
];
```

### Variables de Entorno
```env
# Base de datos
DB_HOST=localhost
DB_NAME=asistencia_db
DB_USER=ricruiz
DB_PASS=Ruor7708028L8+
DB_CHARSET=utf8mb4

# Email
MAIL_HOST=smtp.hostinger.com
MAIL_PORT=587
MAIL_ENCRYPTION=tls
MAIL_USERNAME=notificaciones@alpefresh.app
MAIL_PASSWORD=Alpe25879*
MAIL_FROM_ADDRESS=notificaciones@alpefresh.app
MAIL_FROM_NAME="Sistema de Asistencia - Alpe Fresh"

# Aplicación
APP_NAME="Control de Asistencia"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://asistencia.alpefresh.app
APP_KEY=base64:generated_key_here

# Seguridad
CSRF_ENABLED=true
SECURE_COOKIES=true
SESSION_SECURE=true

# APIs externas (futuro)
GOOGLE_MAPS_API_KEY=
TWILIO_SID=
TWILIO_TOKEN=
```

---

## 📝 CASOS DE USO DETALLADOS

### CU-01: Registro de Entrada
**Actor:** Empleado
**Precondiciones:** 
- Usuario autenticado
- GPS habilitado
- Sin entrada activa

**Flujo Principal:**
1. Empleado accede al dashboard
2. Sistema obtiene ubicación GPS
3. Sistema valida geofence
4. Empleado presiona "Registrar Entrada"
5. Sistema confirma acción
6. Sistema registra entrada
7. Sistema muestra confirmación

**Flujos Alternos:**
- 3a. Fuera de geofence → Mostrar error
- 3b. GPS no disponible → Solicitar activación
- 6a. Error al registrar → Reintentar o contactar admin

### CU-02: Registro de Salida Nocturna
**Actor:** Empleado con turno nocturno
**Precondiciones:**
- Entrada registrada antes de medianoche
- Ahora es después de medianoche

**Flujo Principal:**
1. Empleado accede al dashboard (nuevo día)
2. Sistema detecta entrada activa del día anterior
3. Sistema mantiene sesión activa
4. Empleado registra salida
5. Sistema calcula horas correctamente
6. Sistema asigna jornada al día de entrada

### CU-03: Gestión de Ubicación
**Actor:** Administrador
**Precondiciones:**
- Rol de admin
- Acceso a sección de ubicaciones

**Flujo Principal:**
1. Admin accede a ubicaciones
2. Admin selecciona "Nueva Ubicación"
3. Admin completa formulario
4. Admin selecciona punto en mapa
5. Admin configura radio
6. Sistema valida datos
7. Sistema guarda ubicación
8. Sistema actualiza mapa

### CU-04: Generación de Reporte Mensual
**Actor:** Inspector/Admin
**Precondiciones:**
- Permisos de reporte
- Datos disponibles del período

**Flujo Principal:**
1. Usuario accede a reportes
2. Selecciona tipo "Mensual"
3. Selecciona mes y año
4. Selecciona empleados/ubicaciones
5. Sistema genera reporte
6. Usuario visualiza en pantalla
7. Usuario exporta a Excel/PDF

---

## 🐛 MANEJO DE ERRORES Y EXCEPCIONES

### Tipos de Errores

#### Errores de Usuario
- PIN incorrecto → "PIN incorrecto, intentos restantes: X"
- Fuera de geofence → "Debes estar en una ubicación autorizada"
- Sesión expirada → "Tu sesión ha expirado, ingresa nuevamente"

#### Errores de Sistema
- DB connection → "Error de conexión, intenta más tarde"
- GPS timeout → "No se pudo obtener ubicación, verifica GPS"
- Email failed → "Error al enviar email, contacta soporte"

#### Errores de Validación
- Campo requerido → "Este campo es obligatorio"
- Formato inválido → "Formato inválido, ejemplo: usuario@dominio.com"
- Duplicado → "Este email ya está registrado"

### Logging de Errores
```php
// Niveles de log
ERROR: Errores críticos que requieren atención inmediata
WARNING: Situaciones anormales pero manejables  
INFO: Eventos informativos normales
DEBUG: Información detallada para debugging
```

---

## 🔄 PROCESOS BATCH Y AUTOMATIZACIONES

### Tareas Programadas (Cron Jobs)

#### Diarias
- 00:00 - Cerrar sesiones abiertas del día anterior
- 02:00 - Backup de base de datos
- 06:00 - Limpiar logs antiguos (>90 días)

#### Semanales
- Lunes 00:00 - Generar reporte semanal
- Domingo 23:00 - Optimización de tablas

#### Mensuales
- Día 1, 00:00 - Generar reportes mensuales
- Día 1, 01:00 - Archivar registros antiguos

### Notificaciones Automáticas
1. **Sin salida registrada:** 2 horas después del horario normal
2. **Reporte diario:** A las 18:00 a administradores
3. **Alertas de sistema:** Inmediatas por email a IT

---

## 🌐 INTEGRACIONES FUTURAS

### Fase 2 - Integraciones Básicas
- API REST para aplicaciones externas
- Webhook para eventos
- Integración con Active Directory/LDAP
- Sincronización con sistema de nómina

### Fase 3 - Integraciones Avanzadas
- Reconocimiento facial
- Lector de huella digital
- Tarjetas RFID/NFC
- Integración con ERP
- WhatsApp Business API

### Fase 4 - IA y Analytics
- Predicción de ausentismo
- Detección de patrones anómalos
- Optimización de horarios
- Reportes con IA generativa

---

## 📚 DOCUMENTACIÓN REQUERIDA

### Para Desarrolladores
1. Documentación de API
2. Diagrama de base de datos
3. Diagrama de clases
4. Guía de instalación
5. Guía de contribución

### Para Usuarios
1. Manual de usuario empleado
2. Manual de administrador
3. Videos tutoriales
4. FAQs
5. Guía rápida (PDF)

### Para IT/Soporte
1. Guía de troubleshooting
2. Procedimientos de backup
3. Plan de recuperación
4. Checklist de mantenimiento
5. Escalación de incidentes

---

## ✅ CHECKLIST DE ENTREGA

### Funcionalidades Core
- [ ] Login con PIN funcionando
- [ ] Clock in/out con geolocalización
- [ ] Validación de geofence precisa
- [ ] CRUD usuarios completo
- [ ] CRUD ubicaciones completo
- [ ] Roles y permisos implementados
- [ ] Turnos nocturnos funcionando
- [ ] Reportes básicos generando
- [ ] Exportación a Excel/PDF
- [ ] Notificaciones por email

### Calidad
- [ ] Sin errores críticos
- [ ] Performance aceptable
- [ ] Responsive en móviles
- [ ] Cross-browser testing
- [ ] Seguridad validada
- [ ] Código documentado
- [ ] Manual de usuario

### Deployment
- [ ] Servidor configurado
- [ ] HTTPS activo
- [ ] Backups configurados
- [ ] Monitoreo activo
- [ ] Logs funcionando
- [ ] Cron jobs programados

---

## 💡 NOTAS IMPORTANTES

1. **Prioridad en geolocalización:** Es crítico que funcione bien en móviles
2. **PIN vs Password:** Decisión tomada por facilidad de uso en campo
3. **Fuera de geofence:** Solo restricción en entrada, no en salida
4. **Turnos nocturnos:** Caso especial que debe manejarse correctamente
5. **Idioma:** Todo en español, incluyendo mensajes de error
6. **Timezone:** Crítico mantener CDMX para evitar problemas
7. **Fotos:** Opcional para fase 1, requerido para fase 2

---

**Documento preparado por:** Ricardo Ruiz - Alpe Fresh Mexico  
**Fecha de creación:** Noviembre 2025  
**Última actualización:** Noviembre 2025  
**Versión:** 2.0  
**Estado:** En Desarrollo

---

*Este documento representa los requerimientos completos del sistema de control de asistencia. Cualquier cambio debe ser documentado y aprobado antes de su implementación.*
