-- =====================================================
-- Sistema de Control de Asistencia - Database Schema
-- Alpe Fresh Mexico
-- =====================================================

-- Create database
CREATE DATABASE IF NOT EXISTS asistencia_db
CHARACTER SET utf8mb4
COLLATE utf8mb4_spanish_ci;

USE asistencia_db;

-- =====================================================
-- Table: usuarios
-- =====================================================
CREATE TABLE IF NOT EXISTS usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(100) UNIQUE NOT NULL,
    nombre VARCHAR(100) NOT NULL,
    apellidos VARCHAR(100) NOT NULL,
    pin CHAR(60) NOT NULL, -- Hashed PIN
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
    FOREIGN KEY (creado_por) REFERENCES usuarios(id) ON DELETE SET NULL,
    INDEX idx_email (email),
    INDEX idx_numero_empleado (numero_empleado),
    INDEX idx_activo (activo),
    INDEX idx_rol (rol)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- =====================================================
-- Table: ubicaciones
-- =====================================================
CREATE TABLE IF NOT EXISTS ubicaciones (
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
    dias_laborales VARCHAR(20) DEFAULT '1,2,3,4,5', -- 1=Lunes, 7=Domingo
    requiere_foto BOOLEAN DEFAULT FALSE,
    activa BOOLEAN DEFAULT TRUE,
    creado_por INT,
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    actualizado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (creado_por) REFERENCES usuarios(id) ON DELETE SET NULL,
    INDEX idx_codigo (codigo),
    INDEX idx_activa (activa)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- =====================================================
-- Table: usuarios_ubicaciones
-- =====================================================
CREATE TABLE IF NOT EXISTS usuarios_ubicaciones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    ubicacion_id INT NOT NULL,
    es_principal BOOLEAN DEFAULT FALSE,
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (ubicacion_id) REFERENCES ubicaciones(id) ON DELETE CASCADE,
    UNIQUE KEY unique_usuario_ubicacion (usuario_id, ubicacion_id),
    INDEX idx_usuario (usuario_id),
    INDEX idx_ubicacion (ubicacion_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- =====================================================
-- Table: registros_asistencia
-- =====================================================
CREATE TABLE IF NOT EXISTS registros_asistencia (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    ubicacion_id INT,
    tipo ENUM('entrada', 'salida') NOT NULL,
    fecha_hora DATETIME NOT NULL,
    fecha_local DATE GENERATED ALWAYS AS (DATE(CONVERT_TZ(fecha_hora, '+00:00', '-06:00'))) STORED,
    latitud_registro DECIMAL(10, 8),
    longitud_registro DECIMAL(11, 8),
    precision_gps DECIMAL(6, 2),
    dentro_geofence BOOLEAN DEFAULT TRUE,
    distancia_ubicacion INT, -- Distancia en metros
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
    FOREIGN KEY (editado_por) REFERENCES usuarios(id) ON DELETE SET NULL,
    INDEX idx_usuario_fecha (usuario_id, fecha_hora),
    INDEX idx_tipo (tipo),
    INDEX idx_fecha_local (fecha_local),
    INDEX idx_dentro_geofence (dentro_geofence)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- =====================================================
-- Table: sesiones_trabajo
-- =====================================================
CREATE TABLE IF NOT EXISTS sesiones_trabajo (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    entrada_id INT NOT NULL,
    salida_id INT,
    ubicacion_id INT,
    fecha_inicio DATE NOT NULL,
    hora_entrada DATETIME NOT NULL,
    hora_salida DATETIME,
    duracion_minutos INT,
    duracion_efectiva_minutos INT, -- Descontando descansos
    tiempo_extra_minutos INT DEFAULT 0,
    estado ENUM('activa', 'completada', 'anormal', 'editada') DEFAULT 'activa',
    tipo_jornada ENUM('normal', 'extra', 'festivo', 'descanso') DEFAULT 'normal',
    observaciones TEXT,
    aprobado_por INT,
    aprobado_en DATETIME,
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    actualizado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (entrada_id) REFERENCES registros_asistencia(id) ON DELETE RESTRICT,
    FOREIGN KEY (salida_id) REFERENCES registros_asistencia(id) ON DELETE SET NULL,
    FOREIGN KEY (ubicacion_id) REFERENCES ubicaciones(id) ON DELETE SET NULL,
    FOREIGN KEY (aprobado_por) REFERENCES usuarios(id) ON DELETE SET NULL,
    INDEX idx_usuario_estado (usuario_id, estado),
    INDEX idx_fecha (fecha_inicio),
    INDEX idx_estado (estado)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- =====================================================
-- Table: configuracion_sistema
-- =====================================================
CREATE TABLE IF NOT EXISTS configuracion_sistema (
    id INT AUTO_INCREMENT PRIMARY KEY,
    clave VARCHAR(100) UNIQUE NOT NULL,
    valor TEXT,
    tipo ENUM('string', 'integer', 'boolean', 'json') DEFAULT 'string',
    descripcion TEXT,
    categoria VARCHAR(50),
    editable BOOLEAN DEFAULT TRUE,
    actualizado_por INT,
    actualizado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (actualizado_por) REFERENCES usuarios(id) ON DELETE SET NULL,
    INDEX idx_categoria (categoria),
    INDEX idx_clave (clave)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- =====================================================
-- Table: logs_sistema
-- =====================================================
CREATE TABLE IF NOT EXISTS logs_sistema (
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
    INDEX idx_fecha (creado_en),
    INDEX idx_usuario (usuario_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- =====================================================
-- Table: notificaciones_email
-- =====================================================
CREATE TABLE IF NOT EXISTS notificaciones_email (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT,
    tipo VARCHAR(50) NOT NULL,
    destinatario VARCHAR(100) NOT NULL,
    asunto VARCHAR(255) NOT NULL,
    contenido TEXT,
    estado ENUM('pendiente', 'enviado', 'fallido') DEFAULT 'pendiente',
    intentos INT DEFAULT 0,
    enviado_en DATETIME,
    error_mensaje TEXT,
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL,
    INDEX idx_estado (estado),
    INDEX idx_tipo (tipo),
    INDEX idx_usuario (usuario_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- =====================================================
-- Initial Data: Default Configuration
-- =====================================================
INSERT INTO configuracion_sistema (clave, valor, tipo, descripcion, categoria) VALUES
('pin_length', '6', 'integer', 'Longitud del PIN', 'seguridad'),
('max_login_attempts', '5', 'integer', 'Intentos máximos de login', 'seguridad'),
('lockout_time', '900', 'integer', 'Tiempo de bloqueo en segundos', 'seguridad'),
('session_timeout', '1800', 'integer', 'Timeout de sesión en segundos', 'seguridad'),
('geofence_tolerance', '10', 'integer', 'Tolerancia de geofence en metros', 'geolocalización'),
('default_radius', '100', 'integer', 'Radio por defecto en metros', 'geolocalización'),
('max_radius', '500', 'integer', 'Radio máximo en metros', 'geolocalización'),
('min_radius', '50', 'integer', 'Radio mínimo en metros', 'geolocalización'),
('require_photo_clock_in', 'false', 'boolean', 'Requerir foto al registrar entrada', 'asistencia'),
('allow_manual_edit', 'true', 'boolean', 'Permitir edición manual de registros', 'asistencia'),
('email_notifications', 'true', 'boolean', 'Enviar notificaciones por email', 'notificaciones'),
('daily_report_time', '18:00', 'string', 'Hora de envío de reporte diario', 'reportes'),
('timezone', 'America/Mexico_City', 'string', 'Zona horaria del sistema', 'sistema'),
('company_name', 'Alpe Fresh Mexico', 'string', 'Nombre de la empresa', 'sistema'),
('system_email', 'notificaciones@alpefresh.app', 'string', 'Email del sistema', 'sistema');

-- =====================================================
-- Initial Data: Default Superadmin
-- =====================================================
INSERT INTO usuarios (
    email,
    nombre,
    apellidos,
    pin,
    rol,
    departamento,
    numero_empleado,
    activo,
    email_verificado
) VALUES (
    'admin@alpefresh.app',
    'Administrador',
    'Sistema',
    '$2y$10$X5Vpr9jW5tGXe5B/yFPFbOTjmq1x9dYfGQzMbNgQo8DcT7Jk2zj/W', -- PIN: 123456 (change immediately)
    'superadmin',
    'Sistemas',
    'ADMIN001',
    TRUE,
    TRUE
);

-- =====================================================
-- Initial Data: Test Locations
-- =====================================================
INSERT INTO ubicaciones (
    nombre,
    codigo,
    direccion,
    ciudad,
    estado,
    codigo_postal,
    latitud,
    longitud,
    radio_metros,
    tipo_ubicacion,
    horario_apertura,
    horario_cierre,
    dias_laborales,
    activa,
    creado_por
) VALUES
(
    'Oficina Principal',
    'OF001',
    'Av. Principal #123',
    'Ciudad de México',
    'CDMX',
    '06000',
    19.4326018,  -- CDMX coordinates
    -99.1332049,
    100,
    'oficina',
    '08:00:00',
    '18:00:00',
    '1,2,3,4,5',
    TRUE,
    1
),
(
    'Almacén Central',
    'AL001',
    'Parque Industrial #456',
    'Ciudad de México',
    'CDMX',
    '06050',
    19.4284700,
    -99.1276600,
    150,
    'almacen',
    '07:00:00',
    '19:00:00',
    '1,2,3,4,5,6',
    TRUE,
    1
);

-- =====================================================
-- Views for reporting
-- =====================================================
CREATE OR REPLACE VIEW vista_asistencia_diaria AS
SELECT
    ra.id,
    ra.usuario_id,
    CONCAT(u.nombre, ' ', u.apellidos) AS nombre_completo,
    u.numero_empleado,
    u.departamento,
    ra.tipo,
    ra.fecha_hora,
    ra.fecha_local,
    ub.nombre AS ubicacion,
    ra.dentro_geofence,
    ra.distancia_ubicacion,
    ra.metodo_registro
FROM registros_asistencia ra
JOIN usuarios u ON ra.usuario_id = u.id
LEFT JOIN ubicaciones ub ON ra.ubicacion_id = ub.id
WHERE u.activo = TRUE
ORDER BY ra.fecha_hora DESC;

CREATE OR REPLACE VIEW vista_sesiones_activas AS
SELECT
    st.id,
    st.usuario_id,
    CONCAT(u.nombre, ' ', u.apellidos) AS nombre_completo,
    u.numero_empleado,
    u.departamento,
    st.hora_entrada,
    ub.nombre AS ubicacion,
    TIMESTAMPDIFF(MINUTE, st.hora_entrada, NOW()) AS minutos_trabajados
FROM sesiones_trabajo st
JOIN usuarios u ON st.usuario_id = u.id
LEFT JOIN ubicaciones ub ON st.ubicacion_id = ub.id
WHERE st.estado = 'activa' AND u.activo = TRUE
ORDER BY st.hora_entrada DESC;

-- =====================================================
-- Stored Procedures
-- =====================================================
DELIMITER //

CREATE PROCEDURE sp_registrar_entrada(
    IN p_usuario_id INT,
    IN p_ubicacion_id INT,
    IN p_latitud DECIMAL(10,8),
    IN p_longitud DECIMAL(11,8),
    IN p_precision_gps DECIMAL(6,2),
    IN p_dentro_geofence BOOLEAN,
    IN p_distancia INT,
    IN p_ip VARCHAR(45),
    IN p_user_agent TEXT
)
BEGIN
    DECLARE v_entrada_id INT;
    DECLARE v_sesion_activa INT;

    -- Check if user has active session
    SELECT COUNT(*) INTO v_sesion_activa
    FROM sesiones_trabajo
    WHERE usuario_id = p_usuario_id AND estado = 'activa';

    IF v_sesion_activa > 0 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Usuario ya tiene una sesión activa';
    END IF;

    START TRANSACTION;

    -- Insert attendance record
    INSERT INTO registros_asistencia (
        usuario_id, ubicacion_id, tipo, fecha_hora,
        latitud_registro, longitud_registro, precision_gps,
        dentro_geofence, distancia_ubicacion,
        metodo_registro, direccion_ip, user_agent
    ) VALUES (
        p_usuario_id, p_ubicacion_id, 'entrada', NOW(),
        p_latitud, p_longitud, p_precision_gps,
        p_dentro_geofence, p_distancia,
        'web', p_ip, p_user_agent
    );

    SET v_entrada_id = LAST_INSERT_ID();

    -- Create work session
    INSERT INTO sesiones_trabajo (
        usuario_id, entrada_id, ubicacion_id,
        fecha_inicio, hora_entrada, estado
    ) VALUES (
        p_usuario_id, v_entrada_id, p_ubicacion_id,
        CURDATE(), NOW(), 'activa'
    );

    COMMIT;

    SELECT v_entrada_id AS entrada_id;
END//

CREATE PROCEDURE sp_registrar_salida(
    IN p_usuario_id INT,
    IN p_ubicacion_id INT,
    IN p_latitud DECIMAL(10,8),
    IN p_longitud DECIMAL(11,8),
    IN p_precision_gps DECIMAL(6,2),
    IN p_dentro_geofence BOOLEAN,
    IN p_distancia INT,
    IN p_ip VARCHAR(45),
    IN p_user_agent TEXT
)
BEGIN
    DECLARE v_salida_id INT;
    DECLARE v_sesion_id INT;
    DECLARE v_hora_entrada DATETIME;

    -- Get active session
    SELECT id, hora_entrada INTO v_sesion_id, v_hora_entrada
    FROM sesiones_trabajo
    WHERE usuario_id = p_usuario_id AND estado = 'activa'
    LIMIT 1;

    IF v_sesion_id IS NULL THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'No hay sesión activa para este usuario';
    END IF;

    START TRANSACTION;

    -- Insert exit record
    INSERT INTO registros_asistencia (
        usuario_id, ubicacion_id, tipo, fecha_hora,
        latitud_registro, longitud_registro, precision_gps,
        dentro_geofence, distancia_ubicacion,
        metodo_registro, direccion_ip, user_agent
    ) VALUES (
        p_usuario_id, p_ubicacion_id, 'salida', NOW(),
        p_latitud, p_longitud, p_precision_gps,
        p_dentro_geofence, p_distancia,
        'web', p_ip, p_user_agent
    );

    SET v_salida_id = LAST_INSERT_ID();

    -- Update work session
    UPDATE sesiones_trabajo
    SET
        salida_id = v_salida_id,
        hora_salida = NOW(),
        duracion_minutos = TIMESTAMPDIFF(MINUTE, hora_entrada, NOW()),
        duracion_efectiva_minutos = TIMESTAMPDIFF(MINUTE, hora_entrada, NOW()),
        estado = 'completada'
    WHERE id = v_sesion_id;

    COMMIT;

    SELECT v_salida_id AS salida_id, v_sesion_id AS sesion_id;
END//

DELIMITER ;

-- =====================================================
-- Create user for application (optional)
-- =====================================================
-- GRANT ALL PRIVILEGES ON asistencia_db.* TO 'ricruiz'@'localhost';
-- FLUSH PRIVILEGES;