-- ============================================================================
-- MIGRATION: Checkpoint System for Location Tracking
-- Date: 2025-11-01
-- Description: Enable multiple check-ins per day with location transfers
-- ============================================================================

-- Step 1: Add session_type to registros_asistencia
-- This allows us to differentiate between normal day sessions and checkpoint sessions
ALTER TABLE registros_asistencia
ADD COLUMN session_type ENUM('normal', 'checkpoint') DEFAULT 'normal' AFTER estado,
ADD COLUMN checkpoint_sequence INT DEFAULT 1 COMMENT 'Order of checkpoint in the day' AFTER session_type,
ADD COLUMN is_active TINYINT(1) DEFAULT 1 COMMENT 'Is this checkpoint currently active' AFTER checkpoint_sequence,
ADD INDEX idx_session_type (session_type),
ADD INDEX idx_active_checkpoints (usuario_id, fecha, is_active);

-- Step 2: Create location_transfers table
-- Track when users move from one authorized location to another
CREATE TABLE IF NOT EXISTS location_transfers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    from_ubicacion_id INT NULL COMMENT 'NULL if starting the day',
    to_ubicacion_id INT NOT NULL,
    from_registro_id INT NULL COMMENT 'Previous checkpoint registro ID',
    to_registro_id INT NOT NULL COMMENT 'New checkpoint registro ID',
    transfer_time DATETIME NOT NULL,

    -- GPS data at transfer point
    lat DECIMAL(10,8) NOT NULL,
    lon DECIMAL(11,8) NOT NULL,
    precision_metros FLOAT,

    -- Metadata
    transfer_reason VARCHAR(255) NULL COMMENT 'Optional reason for transfer',
    ip_address VARCHAR(45),
    dispositivo_id VARCHAR(255),

    -- Validation
    validated TINYINT(1) DEFAULT 1,
    distance_from_previous INT COMMENT 'Distance in meters from previous location',

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_usuario_transfers (usuario_id, transfer_time),
    INDEX idx_location_from (from_ubicacion_id),
    INDEX idx_location_to (to_ubicacion_id),
    INDEX idx_transfer_date (transfer_time),

    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (from_ubicacion_id) REFERENCES ubicaciones(id) ON DELETE SET NULL,
    FOREIGN KEY (to_ubicacion_id) REFERENCES ubicaciones(id) ON DELETE CASCADE,
    FOREIGN KEY (from_registro_id) REFERENCES registros_asistencia(id) ON DELETE SET NULL,
    FOREIGN KEY (to_registro_id) REFERENCES registros_asistencia(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci
COMMENT='Track location transfers between authorized locations during work hours';

-- Step 3: Create checkpoint_summary view for easier reporting
CREATE OR REPLACE VIEW v_checkpoint_summary AS
SELECT
    ra.usuario_id,
    u.nombre,
    u.apellidos,
    ra.fecha,
    ra.session_type,
    COUNT(*) as total_checkpoints,
    MIN(ra.hora_entrada) as first_checkin,
    MAX(COALESCE(ra.hora_salida, NOW())) as last_checkout,
    SUM(ra.horas_trabajadas) as total_hours_worked,
    GROUP_CONCAT(
        CONCAT(ub.nombre, ' (', TIME_FORMAT(ra.hora_entrada, '%H:%i'), '-',
               COALESCE(TIME_FORMAT(ra.hora_salida, '%H:%i'), 'En curso'), ')')
        ORDER BY ra.hora_entrada
        SEPARATOR ' → '
    ) as checkpoint_route
FROM registros_asistencia ra
JOIN usuarios u ON ra.usuario_id = u.id
LEFT JOIN ubicaciones ub ON ra.ubicacion_id = ub.id
WHERE ra.session_type = 'checkpoint'
GROUP BY ra.usuario_id, ra.fecha;

-- Step 4: Create function to calculate total daily hours across checkpoints
DELIMITER $$

CREATE FUNCTION IF NOT EXISTS calculate_checkpoint_hours(
    p_usuario_id INT,
    p_fecha DATE
) RETURNS DECIMAL(5,2)
DETERMINISTIC
READS SQL DATA
BEGIN
    DECLARE total_hours DECIMAL(5,2);

    SELECT COALESCE(SUM(horas_trabajadas), 0)
    INTO total_hours
    FROM registros_asistencia
    WHERE usuario_id = p_usuario_id
    AND fecha = p_fecha
    AND session_type = 'checkpoint';

    RETURN total_hours;
END$$

DELIMITER ;

-- Step 5: Add trigger to auto-close previous checkpoint when new one starts
DELIMITER $$

CREATE TRIGGER before_checkpoint_insert
BEFORE INSERT ON registros_asistencia
FOR EACH ROW
BEGIN
    -- If this is a checkpoint session
    IF NEW.session_type = 'checkpoint' THEN

        -- Auto-close any active checkpoint for this user today
        UPDATE registros_asistencia
        SET
            is_active = 0,
            hora_salida = NEW.hora_entrada,
            horas_trabajadas = TIMESTAMPDIFF(MINUTE, hora_entrada, NEW.hora_entrada) / 60.0
        WHERE usuario_id = NEW.usuario_id
        AND fecha = NEW.fecha
        AND session_type = 'checkpoint'
        AND is_active = 1
        AND hora_salida IS NULL;

        -- Set the sequence number
        SET NEW.checkpoint_sequence = (
            SELECT COALESCE(MAX(checkpoint_sequence), 0) + 1
            FROM registros_asistencia
            WHERE usuario_id = NEW.usuario_id
            AND fecha = NEW.fecha
        );

        -- Mark this checkpoint as active
        SET NEW.is_active = 1;
    END IF;
END$$

DELIMITER ;

-- Step 6: Create stored procedure for quick location transfer
DELIMITER $$

CREATE PROCEDURE sp_transfer_location(
    IN p_usuario_id INT,
    IN p_new_ubicacion_id INT,
    IN p_lat DECIMAL(10,8),
    IN p_lon DECIMAL(11,8),
    IN p_precision FLOAT,
    IN p_reason VARCHAR(255),
    OUT p_success BOOLEAN,
    OUT p_message VARCHAR(255),
    OUT p_new_registro_id INT
)
BEGIN
    DECLARE v_old_registro_id INT;
    DECLARE v_old_ubicacion_id INT;
    DECLARE v_fecha DATE;
    DECLARE v_distance INT;

    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        SET p_success = FALSE;
        SET p_message = 'Error al procesar transferencia';
        ROLLBACK;
    END;

    START TRANSACTION;

    SET v_fecha = CURDATE();

    -- Get current active checkpoint
    SELECT id, ubicacion_id
    INTO v_old_registro_id, v_old_ubicacion_id
    FROM registros_asistencia
    WHERE usuario_id = p_usuario_id
    AND fecha = v_fecha
    AND is_active = 1
    AND hora_salida IS NULL
    ORDER BY hora_entrada DESC
    LIMIT 1;

    -- Create new checkpoint (this will auto-close the previous one via trigger)
    INSERT INTO registros_asistencia (
        usuario_id, ubicacion_id, fecha, hora_entrada,
        lat_entrada, lon_entrada, precision_entrada,
        session_type, estado, is_active
    ) VALUES (
        p_usuario_id, p_new_ubicacion_id, v_fecha, NOW(),
        p_lat, p_lon, p_precision,
        'checkpoint', 'presente', 1
    );

    SET p_new_registro_id = LAST_INSERT_ID();

    -- Record the transfer
    INSERT INTO location_transfers (
        usuario_id, from_ubicacion_id, to_ubicacion_id,
        from_registro_id, to_registro_id, transfer_time,
        lat, lon, precision_metros, transfer_reason
    ) VALUES (
        p_usuario_id, v_old_ubicacion_id, p_new_ubicacion_id,
        v_old_registro_id, p_new_registro_id, NOW(),
        p_lat, p_lon, p_precision, p_reason
    );

    -- Record marcaje
    INSERT INTO marcajes (
        registro_id, usuario_id, tipo, hora,
        latitud, longitud, precision_metros,
        ubicacion_id, metodo
    ) VALUES (
        p_new_registro_id, p_usuario_id, 'entrada', NOW(),
        p_lat, p_lon, p_precision,
        p_new_ubicacion_id, 'gps'
    );

    SET p_success = TRUE;
    SET p_message = 'Transferencia registrada exitosamente';

    COMMIT;
END$$

DELIMITER ;

-- Step 7: Add configuration for checkpoint system
INSERT INTO configuracion_sistema (clave, valor, descripcion, tipo) VALUES
('checkpoint_system_enabled', '1', 'Enable checkpoint system for multiple check-ins per day', 'boolean'),
('checkpoint_min_interval_minutes', '30', 'Minimum time between checkpoints in minutes', 'integer'),
('checkpoint_max_per_day', '10', 'Maximum checkpoints allowed per day', 'integer'),
('checkpoint_auto_close_enabled', '1', 'Automatically close previous checkpoint when starting new one', 'boolean')
ON DUPLICATE KEY UPDATE valor = valor;

-- Step 8: Create index for better performance on checkpoint queries
ALTER TABLE registros_asistencia
ADD INDEX idx_checkpoint_active (usuario_id, fecha, session_type, is_active);

-- ============================================================================
-- ROLLBACK SCRIPT (if needed)
-- ============================================================================
/*
-- To rollback this migration:

DROP TRIGGER IF EXISTS before_checkpoint_insert;
DROP PROCEDURE IF EXISTS sp_transfer_location;
DROP FUNCTION IF EXISTS calculate_checkpoint_hours;
DROP VIEW IF EXISTS v_checkpoint_summary;
DROP TABLE IF EXISTS location_transfers;

ALTER TABLE registros_asistencia
    DROP COLUMN session_type,
    DROP COLUMN checkpoint_sequence,
    DROP COLUMN is_active,
    DROP INDEX idx_session_type,
    DROP INDEX idx_active_checkpoints,
    DROP INDEX idx_checkpoint_active;

DELETE FROM configuracion_sistema WHERE clave LIKE 'checkpoint%';
*/
